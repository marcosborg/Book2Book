<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use RuntimeException;

class DatabaseCloneService
{
    /**
     * @return array{tables:int, created_tables: array<int, string>, missing_in_target: array<int, string>}
     */
    public function clone(string $sourceConnection, string $targetConnection): array
    {
        if ($sourceConnection === $targetConnection) {
            return ['tables' => 0, 'created_tables' => [], 'missing_in_target' => []];
        }

        $this->ensureDatabaseExists($targetConnection);

        try {
            $source = DB::connection($sourceConnection);
        } catch (\Throwable $exception) {
            throw new RuntimeException("Source database connection [{$sourceConnection}] is not available.");
        }

        $target = DB::connection($targetConnection);

        $sourceTables = $this->getTables($source);
        $targetTables = $this->getTables($target);
        $createdTables = [];
        $missingInTarget = [];

        foreach ($sourceTables as $table) {
            if (in_array($table, $targetTables, true)) {
                continue;
            }

            try {
                $this->createTableLike($source, $target, $table);
                $targetTables[] = $table;
                $createdTables[] = $table;
            } catch (\Throwable $exception) {
                Log::warning('Failed to create table in target database', [
                    'table' => $table,
                    'target' => $targetConnection,
                    'error' => $exception->getMessage(),
                ]);
                $missingInTarget[] = $table;
            }
        }

        $tables = array_values(array_intersect($sourceTables, $targetTables));

        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $target->statement("TRUNCATE TABLE `{$table}`");
        }

        foreach ($tables as $table) {
            $primaryKey = $this->getPrimaryKey($source, $table);
            $query = $source->table($table);

            if ($primaryKey) {
                $query->orderBy($primaryKey)->chunkById(500, function ($rows) use ($target, $table) {
                    $payload = $rows->map(fn ($row) => (array) $row)->all();
                    if ($payload) {
                        $target->table($table)->insert($payload);
                    }
                }, $primaryKey);
            } else {
                $query->orderByRaw('1')->chunk(500, function ($rows) use ($target, $table) {
                    $payload = $rows->map(fn ($row) => (array) $row)->all();
                    if ($payload) {
                        $target->table($table)->insert($payload);
                    }
                });
            }
        }

        $target->statement('SET FOREIGN_KEY_CHECKS=1');

        $result = [
            'tables' => count($tables),
            'created_tables' => $createdTables,
            'missing_in_target' => $missingInTarget,
        ];

        Log::info('Database cloned', [
            'source' => $sourceConnection,
            'target' => $targetConnection,
            'tables' => $result['tables'],
            'missing_in_target' => $result['missing_in_target'],
        ]);

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function getTables($connection): array
    {
        $dbName = $connection->getDatabaseName();

        return collect($connection->select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'",
            [$dbName]
        ))->pluck('table_name')->all();
    }

    private function getPrimaryKey($connection, string $table): ?string
    {
        $result = $connection->select("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");

        if (! $result) {
            return null;
        }

        return $result[0]->Column_name ?? null;
    }

    private function ensureDatabaseExists(string $connectionName): void
    {
        $config = config("database.connections.{$connectionName}");

        if (! $config || ($config['driver'] ?? null) !== 'mysql') {
            return;
        }

        $database = $config['database'] ?? null;
        if (! $database) {
            return;
        }

        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $socket = $config['unix_socket'] ?? null;

        $dsn = $socket
            ? "mysql:unix_socket={$socket};charset={$charset}"
            : "mysql:host={$host};port={$port};charset={$charset}";

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$collation}");
    }

    private function createTableLike($source, $target, string $table): void
    {
        $result = $source->select("SHOW CREATE TABLE `{$table}`");

        if (! $result) {
            throw new RuntimeException("Unable to read schema for table {$table}.");
        }

        $row = (array) $result[0];
        $createSql = $row['Create Table'] ?? null;

        if (! $createSql) {
            throw new RuntimeException("Unable to read create statement for table {$table}.");
        }

        $target->statement($createSql);
    }
}
