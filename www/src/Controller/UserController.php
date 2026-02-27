<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends AbstractController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->returnWithJson($response, ['name' => 'John Doe']);
    }
}