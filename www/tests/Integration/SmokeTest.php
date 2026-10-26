<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Psr7\Response;

final class SmokeTest extends IntegrationTestCase
{
    public function testDashboardListUsesTheFakeDocker(): void
    {
        $this->actingAs(1);
        $this->fakeDocker([
            new Response(200, [], (string) json_encode([
                ['Id' => 'abc123', 'Names' => ['/web'], 'State' => 'running', 'Image' => 'nginx:alpine', 'Ports' => []],
            ])),
        ]);

        $response = $this->request('POST', '/dashboard/list');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['data' => [['id' => 'abc123', 'name' => '/web', 'status' => 'running', 'image' => 'nginx:alpine', 'ports' => null]]],
            json_decode((string) $response->getBody(), true),
        );
    }
}
