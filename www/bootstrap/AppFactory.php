<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Bootstrap\App as Application;
use App\Handler\HttpErrorHandler;
use DI\Bridge\Slim\Bridge;
use DI\Container;
use Slim\App;

final class AppFactory
{
    /**
     * @param Container $container
     * @return App<Container>
     */
    public static function create(Container $container): App
    {
        $app = Bridge::create($container);

        $errorMiddleware = $app->addErrorMiddleware(
            !Application::isProduction(), // displayErrorDetails
            true, // logErrors
            true, // logErrorDetails
        );
        $errorMiddleware->setDefaultErrorHandler($container->get(HttpErrorHandler::class));

        (require dirname(__DIR__) . '/routes/web.php')($app);

        return $app;
    }
}
