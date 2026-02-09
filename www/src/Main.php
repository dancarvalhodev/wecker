<?php

namespace App;

use App\util\Routes;
use DI\Bridge\Slim\Bridge;
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
        Routes::register($this->app);
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
        $builder = new ContainerBuilder();
        $builder->useAttributes(true);
        $builder->useAutowiring(true);

        if (Common::isProduction()) {
            $builder->enableCompilation(__DIR__ . '/cache');
            $builder->writeProxiesToFile(true, __DIR__ . '/cache');
        }

        $this->container = $builder->build();
    }

    /**
     * @return void
     */
    private function createApp(): void
    {
        $this->app = Bridge::create($this->container);
    }
}
