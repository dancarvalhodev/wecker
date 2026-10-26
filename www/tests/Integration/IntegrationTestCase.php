<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Bootstrap\AppFactory;
use App\Bootstrap\ContainerFactory;
use App\Service\Api\DockerClient;
use App\Service\Api\DockerClientFactory;
use DI\Container;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Boots the real app against wecker_test. Each test runs inside a transaction that is rolled back,
 * and Docker is replaced by a Guzzle MockHandler (see fakeDocker()).
 */
abstract class IntegrationTestCase extends TestCase
{
    /** @var Container $container */
    protected Container $container;

    /** @var App<Container> $app */
    protected App $app;

    /** @var Connection $connection */
    protected Connection $connection;

    protected function setUp(): void
    {
        $_SESSION = [];

        $this->container = ContainerFactory::create();
        $this->app = AppFactory::create($this->container);

        $this->connection = $this->container->get(Connection::class);
        $this->connection->beginTransaction();

        // With no queued responses, any unexpected Docker call fails the test.
        $this->fakeDocker([]);
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();
        $_SESSION = [];
    }

    /**
     * Makes the app talk to a fake Docker that answers with $responses, in order.
     *
     * @param list<ResponseInterface> $responses
     * @return void
     */
    protected function fakeDocker(array $responses): void
    {
        $client = new Client([
            'handler' => HandlerStack::create(new MockHandler($responses)),
            'base_uri' => 'http://docker/' . DockerClientFactory::VERSION . '/',
        ]);

        $this->container->set(DockerClient::class, new DockerClient($client));
    }

    /**
     * @param int $userId
     * @return void
     */
    protected function actingAs(int $userId): void
    {
        $_SESSION['user_id'] = $userId;
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
