<?php

declare(strict_types=1);

use App\Database\ConnectionFactory;
use App\Database\Migrator;
use App\Service\Api\DockerClientFactory;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

// Every test talks to wecker_test, rebuilt from the migrations on each run.
$_ENV['DB_NAME'] = 'wecker_test';

$migrator = new Migrator(new ConnectionFactory());
$migrator->dropDatabase($_ENV['DB_NAME']);
$migrator->createDatabaseIfMissing($_ENV['DB_NAME']);
$migrator->migrate($_ENV['DB_NAME']);

// Image for the throwaway containers of IntegrationTestCase::createRunningContainer().
new DockerClientFactory()->create()->request('POST', 'images/create', [
    'query' => ['fromImage' => 'busybox', 'tag' => 'latest'],
]);
