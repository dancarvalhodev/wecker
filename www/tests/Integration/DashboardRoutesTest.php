<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Psr7\Response;

final class DashboardRoutesTest extends IntegrationTestCase
{
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

        $response = $this->request('POST', '/dashboard/start', ['id' => 'abc123']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('["Container started"]', (string) $response->getBody());
    }

    public function testStopContainer(): void
    {
        $this->actingAs(1);
        $this->fakeDocker([new Response(204)]);

        $response = $this->request('POST', '/dashboard/stop', ['id' => 'abc123']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('["Container stopped"]', (string) $response->getBody());
    }

    public function testContainerLogs(): void
    {
        $this->actingAs(1);
        $this->fakeDocker([new Response(200, [], 'hello from the container')]);

        $response = $this->request('POST', '/dashboard/log', ['id' => 'abc123']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('["hello from the container"]', (string) $response->getBody());
    }
}
