<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Bootstrap\AppFactory;
use App\Bootstrap\ContainerFactory;
use App\Model\User\UserModel;
use App\Service\Api\DockerClientFactory;
use DI\Container;
use Doctrine\DBAL\Connection;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Boots the real app against wecker_test and the real Docker daemon.
 * Each test runs inside a transaction that is rolled back, and the containers it creates are removed.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected const string TEST_IMAGE = 'busybox:latest';

    /** @var Container $container */
    protected Container $container;

    /** @var App<Container> $app */
    protected App $app;

    /** @var Connection $connection */
    protected Connection $connection;

    /** @var ClientInterface $docker direct access to the Docker API, for test setup only */
    protected ClientInterface $docker;

    /** @var list<string> $createdContainers */
    private array $createdContainers = [];

    protected function setUp(): void
    {
        session_start();
        $_SESSION = [];

        $this->container = ContainerFactory::create();
        $this->app = AppFactory::create($this->container);

        $this->connection = $this->container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->docker = new DockerClientFactory()->create();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdContainers as $id) {
            $this->docker->request('DELETE', "containers/$id", ['query' => ['force' => 'true']]);
        }

        $this->connection->rollBack();
        session_destroy();
    }

    /**
     * Creates a user and logs in through POST /login, like the browser does.
     *
     * @return void
     */
    protected function login(): void
    {
        $this->container->get(UserModel::class)->createUser([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'secret',
        ]);

        $response = $this->request('POST', '/login', ['email' => 'ana@example.com', 'password' => 'secret']);
        $this->assertSame(200, $response->getStatusCode(), 'login failed');
    }

    /**
     * Starts a throwaway container that prints "hello" and stays running; removed in tearDown().
     *
     * @return string the container id
     */
    protected function createRunningContainer(): string
    {
        $response = $this->docker->request('POST', 'containers/create', [
            'json' => [
                'Image' => self::TEST_IMAGE,
                'Cmd' => ['sh', '-c', 'echo hello; sleep 300'],
                'StopTimeout' => 1,
            ],
        ]);

        $id = json_decode((string) $response->getBody(), true)['Id'];
        $this->createdContainers[] = $id;

        $this->docker->request('POST', "containers/$id/start");

        return $id;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<string, mixed> $body form fields, sent as the parsed body
     * @return ResponseInterface
     */
    protected function request(string $method, string $path, array $body = []): ResponseInterface
    {
        $request = new ServerRequestFactory()->createServerRequest($method, $path);

        if ($body !== []) {
            $request = $request->withParsedBody($body);
        }

        return $this->app->handle($request);
    }
}
