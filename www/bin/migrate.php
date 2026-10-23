<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$fresh = in_array('--fresh', $_SERVER['argv'], true);
$arguments = array_values(array_diff(array_slice($_SERVER['argv'], 1), ['--fresh']));
$database = $arguments[0] ?? $_ENV['DB_NAME'];

if (preg_match('/^[a-z_][a-z0-9_]*$/', $database) !== 1) {
    fail("Invalid database name: $database");
}

$server = connectTo('postgres');
$quotedDatabase = $server->quoteSingleIdentifier($database);

if ($fresh) {
    $server->executeStatement("DROP DATABASE IF EXISTS $quotedDatabase WITH (FORCE)");
    echo "✔ Database $database dropped" . PHP_EOL;
}

if ($server->fetchOne('SELECT 1 FROM pg_database WHERE datname = ?', [$database]) === false) {
    $server->executeStatement("CREATE DATABASE $quotedDatabase");
    echo "✔ Database $database created" . PHP_EOL;
}

$connection = connectTo($database);
$connection->executeStatement(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )',
);

$appliedVersions = $connection->fetchFirstColumn('SELECT version FROM schema_migrations');
$migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
$appliedNow = 0;

foreach ($migrationFiles as $file) {
    $version = basename($file, '.sql');

    if (in_array($version, $appliedVersions, true)) {
        continue;
    }

    $connection->beginTransaction();

    try {
        $connection->executeStatement((string) file_get_contents($file));
        $connection->insert('schema_migrations', ['version' => $version]);
        $connection->commit();
    } catch (Throwable $e) {
        $connection->rollBack();
        fail("Migration $version failed: " . $e->getMessage());
    }

    echo "✔ Applied $version" . PHP_EOL;
    $appliedNow++;
}

echo $appliedNow === 0
    ? "✔ $database is up to date" . PHP_EOL
    : "✔ $database: $appliedNow migration(s) applied" . PHP_EOL;
