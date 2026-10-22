<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_command_creates_sqlite_dump(): void
    {
        $dir = sys_get_temp_dir() . '/komi_backup_test_' . uniqid();
        $dbFile = sys_get_temp_dir() . '/komi_backup_src_' . uniqid() . '.sqlite';

        // Fuente sqlite real con la que probar el dump (el :memory: de tests no se puede dumpear).
        $pdo = new \PDO('sqlite:' . $dbFile);
        $pdo->exec('CREATE TABLE sample (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO sample (name) VALUES ('komi')");
        unset($pdo);

        config(['database.connections.backup_test' => [
            'driver' => 'sqlite',
            'database' => $dbFile,
            'prefix' => '',
        ]]);

        $this->artisan('backup:database', ['--path' => $dir, '--connection' => 'backup_test'])
            ->assertExitCode(0);

        $files = glob("{$dir}/komi_*.sql") ?: [];
        $this->assertNotEmpty($files, 'El comando debe crear un fichero .sql');

        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('sample', $content);
        $this->assertStringContainsString('komi', $content);

        array_map('unlink', glob("{$dir}/*") ?: []);
        @rmdir($dir);
        @unlink($dbFile);
    }

    public function test_backup_keeps_only_last_14_files(): void
    {
        $dir = sys_get_temp_dir() . '/komi_backup_rotate_' . uniqid();
        $dbFile = sys_get_temp_dir() . '/komi_backup_rot_src_' . uniqid() . '.sqlite';
        touch($dbFile);

        config(['database.connections.backup_rot' => [
            'driver' => 'sqlite',
            'database' => $dbFile,
            'prefix' => '',
        ]]);

        mkdir($dir, 0755, true);

        // 16 dumps viejos + el nuevo del comando = 17 → debe quedar 14.
        for ($i = 0; $i < 16; $i++) {
            file_put_contents(sprintf('%s/komi_2020-01-01_%06d.sql', $dir, $i), 'x');
        }

        $this->artisan('backup:database', ['--path' => $dir, '--connection' => 'backup_rot'])
            ->assertExitCode(0);

        $this->assertCount(14, glob("{$dir}/komi_*.sql") ?: []);

        array_map('unlink', glob("{$dir}/*") ?: []);
        @rmdir($dir);
        @unlink($dbFile);
    }

    public function test_memory_database_is_noop_success(): void
    {
        // Con :memory: (config de tests) el comando no debe fallar.
        $this->artisan('backup:database', ['--path' => sys_get_temp_dir() . '/komi_noop'])
            ->assertExitCode(0);
    }
}
