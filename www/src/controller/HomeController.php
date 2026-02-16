<?php

namespace App\controller;

use App\controller\Default\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends AbstractController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->returnWithJson($response, ['name' => 'John Doe']);
    }
}