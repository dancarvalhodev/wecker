<?php
declare(strict_types=1);

namespace App\util;

use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

final class Routes
{
    public static function register(App $app): void
    {
        $app->get('/', function (Request $request, Response $response) {
            $response->getBody()->write("Hello world!");
            return $response;
        });
    }
}