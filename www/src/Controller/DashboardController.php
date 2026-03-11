<?php

namespace App\Controller;

use App\Service\Api\DockerClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DashboardController extends AbstractController
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dockerClient = new DockerClient();

        return $this->returnWithJson($response, ['dockerIs' => $dockerClient->ping()]);
    }
}