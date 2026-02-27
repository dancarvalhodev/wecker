<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

abstract class AbstractRepo
{
    private Connection $connection;
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}