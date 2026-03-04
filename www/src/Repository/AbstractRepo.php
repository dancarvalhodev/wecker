<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

abstract class AbstractRepo
{
    protected Connection $connection;
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}