<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    public static function db(): \PDO
    {
        return Database::getConnection();
    }

    public static function all(): array
    {
        return static::db()->query("SELECT * FROM " . static::$table . " ORDER BY id DESC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function where(string $column, mixed $value): array
    {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function first(string $column, mixed $value): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    public static function insert(array $data): int
    {
        $cols = array_map(fn($c) => "`{$c}`", array_keys($data));
        $columns = implode(', ', $cols);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = static::db()->prepare("INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int)static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sets = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $stmt = static::db()->prepare("UPDATE " . static::$table . " SET {$sets} WHERE " . static::$primaryKey . " = ?");
        $values = array_values($data);
        $values[] = $id;
        return $stmt->execute($values);
    }

    public static function delete(int $id): bool
    {
        $stmt = static::db()->prepare("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?");
        return $stmt->execute([$id]);
    }

    public static function count(): int
    {
        return (int)static::db()->query("SELECT COUNT(*) as c FROM " . static::$table)->fetch()['c'];
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
