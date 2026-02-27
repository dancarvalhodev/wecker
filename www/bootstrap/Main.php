<?php

namespace App\Bootstrap;

use DI\Bridge\Slim\Bridge;
use DI\Container;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Exception;
use Slim\App;
use App\Bootstrap\App as Application;
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
        $this->registerRoutes();
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $this->app->run();
    }

    /**
     * @return void
     */
    private function loadConfigs(): void
    {
        Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
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

        if (Application::isProduction()) {
            $builder->enableCompilation(dirname(__DIR__) . '/storage/cache');
            $builder->writeProxiesToFile(true, dirname(__DIR__) . '/storage/cache');
        }

        $builder->addDefinitions(dirname(__DIR__) . '/config/database.php');
        $this->container = $builder->build();
    }

    /**
     * @return void
     */
    private function createApp(): void
    {
        $this->app = Bridge::create($this->container);
    }

    /**
     * @return void
     */
    private function registerRoutes(): void
    {
        (require dirname(__DIR__) . '/routes/web.php')($this->app);
    }
}
