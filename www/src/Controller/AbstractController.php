<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class AbstractController
{
    private Twig $twig;
    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param ResponseInterface $response
     * @param array $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    protected function returnWithJson(ResponseInterface $response, array $data, int $statusCode = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }

    protected function returnWithSuccess(ResponseInterface $response)
    {
        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

        $response->getBody()->write(json_encode([
            'success' => true
        ]));

        return $response;
    }

    protected function getTwig(): Twig
    {
        return $this->twig;
    }

    protected function setTwig(Twig $twig): void
    {
        $this->twig = $twig;
    }
}