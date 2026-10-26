<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Bootstrap\App as Application;
use DI\Container;
use DI\ContainerBuilder;
use Exception;

final class ContainerFactory
{
    /**
     * @return Container
     * @throws Exception
     */
    public static function create(): Container
    {
        $builder = new ContainerBuilder();
        $builder->useAttributes(true);
        $builder->useAutowiring(true);

        if (Application::isProduction()) {
            $builder->enableCompilation(dirname(__DIR__) . '/storage/cache');
            $builder->writeProxiesToFile(true, dirname(__DIR__) . '/storage/cache');
        }

        $builder->addDefinitions(dirname(__DIR__) . '/config/database.php');
        $builder->addDefinitions(dirname(__DIR__) . '/config/twig.php');
        $builder->addDefinitions(dirname(__DIR__) . '/config/handler.php');
        $builder->addDefinitions(dirname(__DIR__) . '/config/docker.php');

        return $builder->build();
    }
}
