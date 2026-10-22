<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--path= : Directorio de destino (default: storage/app/backups)}
        {--connection= : Conexión a respaldar (default: la activa)}';

    protected $description = 'Copia de seguridad de la BD (mysqldump para MySQL/MariaDB, sqlite3 para SQLite)';

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('database.default');
        $dir = $this->option('path') ?: storage_path('app/backups');

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("No se pudo crear el directorio: {$dir}");

            return self::FAILURE;
        }

        $timestamp = date('Y-m-d_His');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $database = config("database.connections.{$connection}.database");

            if ($database === ':memory:' || ! is_file($database)) {
                $this->warn('BD en memoria o fichero inexistente: no hay nada que respaldar.');

                return self::SUCCESS;
            }

            $target = "{$dir}/komi_{$timestamp}.sql";

            // Dump SQL legible sin depender del binario sqlite3.
            try {
                $this->dumpSqlite($database, $target);
            } catch (\Throwable $e) {
                $this->error('sqlite dump falló: ' . $e->getMessage());

                return self::FAILURE;
            }
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port") ?: 3306;
            $database = config("database.connections.{$connection}.database");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $target = "{$dir}/komi_{$timestamp}.sql.gz";

            $env = 'MYSQL_PWD=' . escapeshellarg((string) $password);
            $cmd = sprintf(
                '%s mysqldump -h %s -P %s -u %s %s 2>&1 | gzip > %s',
                $env,
                escapeshellarg((string) $host),
                escapeshellarg((string) $port),
                escapeshellarg((string) $username),
                escapeshellarg((string) $database),
                escapeshellarg($target)
            );

            exec($cmd, $output, $exit);

            if ($exit !== 0) {
                $this->error('mysqldump falló: ' . implode("\n", $output));

                return self::FAILURE;
            }
        } else {
            $this->error("Conexión no soportada para backup: {$connection}");

            return self::FAILURE;
        }

        $size = is_file($target) ? round(filesize($target) / 1024, 1) : 0;
        $this->info("Backup creado: {$target} ({$size} KB)");

        // Retener solo los últimos 14 backups.
        $files = glob("{$dir}/komi_*.sql*") ?: [];
        rsort($files);
        foreach (array_slice($files, 14) as $old) {
            @unlink($old);
        }

        return self::SUCCESS;
    }

    /**
     * Vuelca tablas e inserts de un SQLite a fichero SQL usando PDO.
     */
    private function dumpSqlite(string $database, string $target): void
    {
        $pdo = new \PDO('sqlite:' . $database);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(\PDO::FETCH_COLUMN);

        $out = fopen($target, 'w');
        fwrite($out, "-- Komi SQLite backup " . date('c') . "\n\n");

        foreach ($tables as $table) {
            $create = $pdo->query(
                "SELECT sql FROM sqlite_master WHERE type='table' AND name = " . $pdo->quote($table)
            )->fetchColumn();

            if ($create) {
                fwrite($out, $create . ";\n");
            }

            $rows = $pdo->query('SELECT * FROM ' . $pdo->quote($table))->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $vals = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
                fwrite($out, 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ");\n");
            }

            fwrite($out, "\n");
        }

        fclose($out);
    }
}
