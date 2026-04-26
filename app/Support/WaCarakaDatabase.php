<?php

namespace App\Support;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WaCarakaDatabase
{
    public static function connectionName(): string
    {
        return (string) config('wa_caraka.database_connection', config('database.default'));
    }

    public static function connection(): ConnectionInterface
    {
        return DB::connection(static::connectionName());
    }

    public static function schema(): SchemaBuilder
    {
        return Schema::connection(static::connectionName());
    }

    public static function table(string $table): QueryBuilder
    {
        return static::connection()->table($table);
    }

    public static function hasTable(string $table): bool
    {
        return static::schema()->hasTable($table);
    }

    public static function transaction(Closure $callback, int $attempts = 1): mixed
    {
        return static::connection()->transaction($callback, $attempts);
    }

    public static function usesDedicatedConnection(): bool
    {
        return static::connectionName() !== (string) config('database.default');
    }
}
