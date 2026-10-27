<?php

declare(strict_types=1);

namespace Tests\Integration;

final class NotFoundTest extends IntegrationTestCase
{
    public function testUnknownRouteReturns404(): void
    {
        $this->expectErrorLog();

        $response = $this->request('GET', '/does-not-exist');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('{"success":false,"error":["Not found."]}', (string) $response->getBody());
    }
}
