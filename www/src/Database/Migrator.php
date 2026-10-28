<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class Migrator
{
    private const string MAINTENANCE_DATABASE = 'postgres';

    /** @var ConnectionFactory $connectionFactory */
    private ConnectionFactory $connectionFactory;

    /**
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param string $database
     * @return void
     * @throws Exception
     */
    public function dropDatabase(string $database): void
    {
        $server = $this->connectionFactory->create(self::MAINTENANCE_DATABASE);

        $server->executeStatement('DROP DATABASE IF EXISTS ' . $this->quoteName($server, $database) . ' WITH (FORCE)');
    }

    /**
     * @param string $database
     * @return bool true when the database did not exist and was created
     * @throws Exception
     */
    public function createDatabaseIfMissing(string $database): bool
    {
        $server = $this->connectionFactory->create(self::MAINTENANCE_DATABASE);

        if ($server->fetchOne('SELECT 1 FROM pg_database WHERE datname = ?', [$database]) !== false) {
            return false;
        }

        $server->executeStatement('CREATE DATABASE ' . $this->quoteName($server, $database));

        return true;
    }

    /**
     * @param string $database
     * @return list<string> versions applied in this run
     * @throws Exception
     * @throws RuntimeException when a migration fails (it is rolled back)
     */
    public function migrate(string $database): array
    {
        $connection = $this->connectionFactory->create($database);

        $connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(255) PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )',
        );

        $alreadyApplied = $connection->fetchFirstColumn('SELECT version FROM schema_migrations');
        $appliedNow = [];

        foreach ($this->migrationFiles() as $file) {
            $version = basename($file, '.sql');

            if (in_array($version, $alreadyApplied, true)) {
                continue;
            }

            $connection->beginTransaction();

            try {
                $connection->executeStatement((string) file_get_contents($file));
                $connection->insert('schema_migrations', ['version' => $version]);
                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollBack();
                throw new RuntimeException("Migration $version failed: " . $e->getMessage(), 0, $e);
            }

            $appliedNow[] = $version;
        }

        return $appliedNow;
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        return glob(dirname(__DIR__, 2) . '/database/migrations/*.sql') ?: [];
    }

    /**
     * Database names cannot be bound as query parameters, so they are validated before being quoted.
     *
     * @param Connection $server
     * @param string $database
     * @return string
     */
    private function quoteName(Connection $server, string $database): string
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $database) !== 1) {
            throw new InvalidArgumentException("Invalid database name: $database");
        }

        return $server->quoteSingleIdentifier($database);
    }
}
