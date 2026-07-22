<?php
declare(strict_types=1);

namespace App\Models;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::first('key', $key);
        return $row ? $row['value'] : $default;
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?string $description = null): void
    {
        $existing = static::first('key', $key);
        if ($existing) {
            static::update((int)$existing['id'], ['value' => (string)$value]);
        } else {
            static::insert([
                'key' => $key,
                'value' => (string)$value,
                'type' => $type,
                'description' => $description ?? '',
            ]);
        }
    }
}
