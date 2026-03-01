<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends AbstractController
{
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $params = $request->getParsedBody();

        if ($request->getMethod() === 'POST') {
            return $this->returnWithJson($response, ['name' => 'John Doe']);
        }

        return $this->getTwig()->render($response, 'crud/user/register.html.twig');
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->returnWithJson($response, ['name' => 'John Doe']);
        }

        return $this->getTwig()->render($response, 'crud/user/login.html.twig');
    }
}