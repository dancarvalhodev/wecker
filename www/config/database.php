<?php

use App\Database\ConnectionFactory;
use Doctrine\DBAL\Connection;

return [
    Connection::class => function (ConnectionFactory $connectionFactory) {
        return $connectionFactory->create($_ENV['DB_NAME']);
    },
];
