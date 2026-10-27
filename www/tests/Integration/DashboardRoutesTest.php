<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Psr7\Response;

final class DashboardRoutesTest extends IntegrationTestCase
{
    private const string CONTAINER_ID = 'abc123abc123';

    public function testDashboardRedirectsGuestsToLogin(): void
    {
        $response = $this->request('GET', '/dashboard');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testDashboardShowsContainerCounts(): void
    {
        $this->actingAs(1);
        $this->fakeDocker([
            new Response(200, [], (string) json_encode([
                ['Id' => 'a', 'Names' => ['/web'], 'State' => 'running', 'Image' => 'nginx:alpine', 'Ports' => []],
                ['Id' => 'b', 'Names' => ['/db'], 'State' => 'exited', 'Image' => 'postgres:18', 'Ports' => []],
                ['Id' => 'c', 'Names' => ['/wecker_php'], 'State' => 'running', 'Image' => 'wecker-php', 'Ports' => []],
            ])),
        ]);

        $response = $this->request('GET', '/dashboard');
        $html = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<h2 class="text-success">1</h2>', $html);
        $this->assertStringContainsString('<h2 class="text-danger">1</h2>', $html);
        $this->assertStringContainsString('<h2>2</h2>', $html);
    }

    public function testStartContainer(): void
    {
        $this->actingAs(1);
        $this->fakeDocker([new Response(204)]);

        $response = $this->request('POST', '/dashboard/start', ['id' => self::CONTAINER_ID]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('["Container started"]', (string) $response->getBody());
        $this->assertDockerCalled('POST', '/v1.54/containers/' . self::CONTAINER_ID . '/start');
    }

    public function testStopContainer(): void
    {
        $this->actingAs(1);
        $this->fakeDocker([new Response(204)]);

        $response = $this->request('POST', '/dashboard/stop', ['id' => self::CONTAINER_ID]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('["Container stopped"]', (string) $response->getBody());
        $this->assertDockerCalled('POST', '/v1.54/containers/' . self::CONTAINER_ID . '/stop');
    }

    public function testContainerLogs(): void
    {
        $this->actingAs(1);
        $this->fakeDocker([new Response(200, [], 'hello from the container')]);

        $response = $this->request('POST', '/dashboard/log', ['id' => self::CONTAINER_ID]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('["hello from the container"]', (string) $response->getBody());
        $this->assertDockerCalled('GET', '/v1.54/containers/' . self::CONTAINER_ID . '/logs');
    }

    public function testInvalidContainerIdNeverReachesDocker(): void
    {
        $this->expectErrorLog();
        $this->actingAs(1);

        $response = $this->request('POST', '/dashboard/stop', ['id' => '../../images/x']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('{"success":false,"error":["Invalid container ID."]}', (string) $response->getBody());
        $this->assertNull($this->docker->getLastRequest());
    }

    private function assertDockerCalled(string $method, string $path): void
    {
        $request = $this->docker->getLastRequest();

        $this->assertNotNull($request);
        $this->assertSame($method . ' ' . $path, $request->getMethod() . ' ' . $request->getUri()->getPath());
    }
}
