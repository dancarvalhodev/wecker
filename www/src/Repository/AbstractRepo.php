<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

abstract class AbstractRepo
{
    /** @var Connection $connection */
    protected Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    abstract protected function hydrate(array $data): object;
}