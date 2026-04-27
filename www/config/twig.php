<?php

use App\Bootstrap\App;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

return [
    Twig::class => function (ContainerInterface $c) {
        $twig = Twig::create(
            __DIR__ . '/../templates',
            [
                'cache' => App::isProduction() ? dirname(__DIR__) . '/storage/cache/twig' : false
            ]
        );

        $env = $twig->getEnvironment();

        $env->addGlobal('auth', [
            'logged' => isset($_SESSION['user_id'])
        ]);

        $env->addGlobal('global', [
            'year' => date('Y')
        ]);

        return $twig;
    },
];