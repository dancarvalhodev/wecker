<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

/**
 * @param string $database
 * @return Connection
 */
function connectTo(string $database): Connection
{
    return DriverManager::getConnection([
        'dbname'   => $database,
        'user'     => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
        'host'     => $_ENV['DB_HOST'],
        'port'     => (int) $_ENV['DB_PORT'],
        'driver'   => $_ENV['DB_DRIVER'],
    ]);
}

/**
 * @param string $message
 * @return never
 */
function fail(string $message): never
{
    fwrite(STDERR, "✖ $message" . PHP_EOL);
    exit(1);
}
