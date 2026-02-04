<?php

namespace App;

use DI\Container;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Exception;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

# IMPROVE LEARNING ABOUT PHP DI AND HOW WORKS
final class Main
{
    private Container $container;

    /**
     * @var App
     */
    private App $app;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->loadConfigs();
        $this->buildContainer();
        $this->createApp();
    }

    /**
     * @return void
     */
    public function run(): void
    {
        # HERE ENTER ROUTE SYSTEM
        $this->app->get('/', function (Request $request, Response $response, $args) {
            $response->getBody()->write("Hello world!");
            return $response;
        });

        $this->app->run();
    }

    /**
     * @return void
     */
    private function loadConfigs(): void
    {
        Dotenv::createImmutable(__DIR__ . '/config')->safeLoad();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function buildContainer(): void
    {
        $builder = new Container();
        $this->container = $builder;
    }

    /**
     * @return void
     */
    private function createApp(): void
    {
        AppFactory::setContainer($this->container);
        $this->app = AppFactory::create();
    }
}
