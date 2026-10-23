<?php

declare(strict_types=1);

use App\Console\Terminal;
use App\Database\Migrator;
use DI\Container;

/** @var Container $container */
$container = require __DIR__ . '/bootstrap.php';

$terminal = $container->get(Terminal::class);
$migrator = $container->get(Migrator::class);

$fresh = in_array('--fresh', $_SERVER['argv'], true);
$arguments = array_values(array_diff(array_slice($_SERVER['argv'], 1), ['--fresh']));
$database = $arguments[0] ?? $_ENV['DB_NAME'];

try {
    if ($fresh) {
        $migrator->dropDatabase($database);
        $terminal->success("Database $database dropped");
    }

    if ($migrator->createDatabaseIfMissing($database)) {
        $terminal->success("Database $database created");
    }

    $appliedVersions = $migrator->migrate($database);

    foreach ($appliedVersions as $version) {
        $terminal->success("Applied $version");
    }

    $terminal->success(
        $appliedVersions === []
            ? "$database is up to date"
            : "$database: " . count($appliedVersions) . ' migration(s) applied',
    );
} catch (Throwable $e) {
    $terminal->fail($e->getMessage());
}
