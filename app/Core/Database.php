<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/** Pembungkus PDO sederhana. Mendukung MySQL/MariaDB dan SQLite. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $driver = (string) config('db.driver', 'mysql');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if ($driver === 'sqlite') {
            $path = (string) config('db.sqlite_path');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                config('db.host', '127.0.0.1'),
                (int) config('db.port', 3306),
                config('db.database'),
                config('db.charset', 'utf8mb4')
            );
            $pdo = new PDO($dsn, (string) config('db.username'), (string) config('db.password'), $options);
            $offset = (new \DateTime())->format('P');
            $pdo->exec("SET time_zone = '" . $offset . "'");
        }
        return self::$pdo = $pdo;
    }

    public static function driver(): string
    {
        return (string) config('db.driver', 'mysql');
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' .
            implode(', ', array_fill(0, count($cols), '?')) . ')';
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(', ', array_map(static fn ($c) => $c . ' = ?', array_keys($data)));
        return self::run('UPDATE ' . $table . ' SET ' . $set . ' WHERE ' . $where, [...array_values($data), ...$params])->rowCount();
    }

    /** @template T @param callable():T $fn @return T */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @var array<string, true> tabel yang sudah dipastikan ada (hanya hasil positif yang disimpan) */
    private static array $tables = [];

    public static function tableExists(string $table): bool
    {
        if (isset(self::$tables[$table])) {
            return true;
        }
        try {
            $found = self::driver() === 'sqlite'
                ? (bool) self::value("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table])
                : (bool) self::value('SHOW TABLES LIKE ' . self::pdo()->quote($table));
        } catch (Throwable) {
            return false;
        }
        if ($found) {
            self::$tables[$table] = true;
        }
        return $found;
    }

    /** Menjalankan file .sql yang berisi beberapa perintah dipisah titik koma. */
    public static function runFile(string $file): void
    {
        $sql = (string) file_get_contents($file);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            self::pdo()->exec($statement);
        }
    }
}
