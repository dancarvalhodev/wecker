<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Model\User\UserModel;

final class UserRoutesTest extends IntegrationTestCase
{
    public function testLoginWithValidCredentialsStartsTheSession(): void
    {
        $user = $this->container->get(UserModel::class)->createUser([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'secret',
        ]);

        $response = $this->request('POST', '/login', ['email' => ' Ana@Example.com ', 'password' => 'secret']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"success":true}', (string) $response->getBody());
        $this->assertSame($user->getId(), $_SESSION['user_id']);
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $this->expectErrorLog();

        $response = $this->request('POST', '/login', ['email' => 'nobody@example.com', 'password' => 'wrong']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('{"success":false,"error":["Wrong password."]}', (string) $response->getBody());
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testUnknownRouteReturns500(): void
    {
        // Current behavior: HttpErrorHandler checks the wrong HttpException class (plan problem 5).
        $this->expectErrorLog();

        $response = $this->request('GET', '/does-not-exist');

        $this->assertSame(500, $response->getStatusCode());
    }
}
