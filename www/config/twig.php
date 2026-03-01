<?php

use App\Bootstrap\App;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

return [
    Twig::class => function (ContainerInterface $c) {
        return Twig::create(
            __DIR__ . '/../templates',
            [
                'cache' => App::isProduction() ? dirname(__DIR__) . '/storage/cache/twig' : false
            ]
        );
    },
];