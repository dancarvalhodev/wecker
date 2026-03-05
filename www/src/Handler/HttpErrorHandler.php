<?php

namespace App\Handler;

use App\Exception\ValidationException;
use HttpException;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\ErrorHandler;

class HttpErrorHandler extends ErrorHandler
{
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;

        $status = 500;
        $message = 'Internal Server Error';

        if ($exception instanceof HttpException) {
            $status = $exception->getCode();
            $message = [$exception->getMessage()];
        }

        if ($exception instanceof ValidationException) {
            $status = $exception->getCode();
            $message = $exception->getMessages();
        }

        $payload = [
            'success' => false,
            'error' => $message
        ];

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($payload));

        return $response->withHeader('Content-Type', 'application/json');
    }
}