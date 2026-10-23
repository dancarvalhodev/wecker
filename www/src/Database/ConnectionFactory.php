<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

final class ConnectionFactory
{
    /**
     * @param string $database
     * @return Connection
     * @throws Exception
     */
    public function create(string $database): Connection
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
}
