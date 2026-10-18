<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\CertVariable;

/**
 * Menjalankan migrasi aditif di database/migrations/*.{mysql|sqlite}.sql satu kali saja.
 * Migrasi yang sudah dijalankan dicatat di tabel schema_migrations.
 * File migrasi hanya boleh berisi CREATE TABLE/INDEX IF NOT EXISTS (tidak menghapus data).
 */
final class Migrator
{
    private static function suffix(): string
    {
        return Database::driver() === 'sqlite' ? 'sqlite' : 'mysql';
    }

    private static function ensureTable(): void
    {
        Database::pdo()->exec(Database::driver() === 'sqlite'
            ? 'CREATE TABLE IF NOT EXISTS schema_migrations (name TEXT NOT NULL PRIMARY KEY, applied_at TEXT NOT NULL)'
            : 'CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    /** @return array<string,string> nama migrasi => path file */
    public static function files(): array
    {
        $out = [];
        foreach (glob(ROOT_PATH . '/database/migrations/*.' . self::suffix() . '.sql') ?: [] as $file) {
            $out[basename($file, '.' . self::suffix() . '.sql')] = $file;
        }
        ksort($out);
        return $out;
    }

    public static function applied(): array
    {
        if (!Database::tableExists('schema_migrations')) {
            return [];
        }
        return array_column(Database::all('SELECT name FROM schema_migrations'), 'name');
    }

    /** @return string[] */
    public static function pending(): array
    {
        try {
            return array_values(array_diff(array_keys(self::files()), self::applied()));
        } catch (\Throwable) {
            return [];
        }
    }

    public static function upToDate(): bool
    {
        static $ok = null;
        return $ok ??= self::pending() === [];
    }

    /** @return string[] migrasi yang baru dijalankan */
    public static function run(): array
    {
        self::ensureTable();
        $ran = [];
        $applied = self::applied();
        foreach (self::files() as $name => $file) {
            if (in_array($name, $applied, true)) {
                continue;
            }
            Database::runFile($file);
            Database::insert('schema_migrations', ['name' => $name, 'applied_at' => now()]);
            $ran[] = $name;
        }
        if (Database::tableExists('cert_variables')) {
            CertVariable::seedDefaults();
        }
        return $ran;
    }
}
