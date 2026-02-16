<?php

namespace App\controller\Default;

use Psr\Http\Message\ResponseInterface;

class AbstractController
{
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
}