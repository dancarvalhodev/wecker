<?php

declare(strict_types=1);

namespace Tests\Integration;

final class UserRoutesTest extends IntegrationTestCase
{
    public function testLoginOpensTheDashboard(): void
    {
        $this->login();

        $response = $this->request('GET', '/dashboard');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Dashboard', (string) $response->getBody());
    }

    public function testWrongPasswordKeepsTheDashboardClosed(): void
    {
        $this->expectErrorLog();

        $login = $this->request('POST', '/login', ['email' => 'nobody@example.com', 'password' => 'wrong']);
        $dashboard = $this->request('GET', '/dashboard');

        $this->assertSame(422, $login->getStatusCode());
        $this->assertSame(302, $dashboard->getStatusCode());
        $this->assertSame('/login', $dashboard->getHeaderLine('Location'));
    }
}
