<?php

declare(strict_types=1);

use App\Database\ConnectionFactory;
use App\Database\Migrator;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

// Every test talks to wecker_test, rebuilt from the migrations on each run.
$_ENV['DB_NAME'] = 'wecker_test';

$migrator = new Migrator(new ConnectionFactory());
$migrator->dropDatabase($_ENV['DB_NAME']);
$migrator->createDatabaseIfMissing($_ENV['DB_NAME']);
$migrator->migrate($_ENV['DB_NAME']);
