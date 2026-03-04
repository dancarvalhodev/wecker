<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Container\ContainerInterface;

return [
    Connection::class => function (ContainerInterface $c) {
        return DriverManager::getConnection([
            'dbname'   => $_ENV['DB_NAME'],
            'user'     => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWORD'],
            'host'     => $_ENV['DB_HOST'],
            'port'     => $_ENV['DB_PORT'],
            'driver'   => $_ENV['DB_DRIVER'],
        ]);
    },
];
