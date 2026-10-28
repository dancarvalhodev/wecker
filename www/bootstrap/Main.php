<?php

namespace App\Bootstrap;

use DI\Container;
use Dotenv\Dotenv;
use Exception;
use Slim\App;

final class Main
{
    /**
     * @var App<Container>
     */
    private App $app;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->loadConfigs();
        $this->app = AppFactory::create(ContainerFactory::create());
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
}
