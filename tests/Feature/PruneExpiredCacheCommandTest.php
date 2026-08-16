<?php

use Illuminate\Support\Facades\DB;

it('removes stale versioned cache keys while keeping the current version', function () {
    $connection = config('cache.stores.database.connection') ?? config('database.default');
    $table = 'cache_prune_test';

    config()->set('cache.stores.database.table', $table);

    DB::connection($connection)->getSchemaBuilder()->dropIfExists($table);
    DB::connection($connection)->getSchemaBuilder()->create($table, function ($table) {
        $table->string('key')->primary();
        $table->text('value');
        $table->integer('expiration')->nullable();
    });

    $now = now()->timestamp;

    DB::connection($connection)->table($table)->insert([
        ['key' => 'laravel-cache-properties.cache.version', 'value' => serialize(5), 'expiration' => $now + 3600],
        ['key' => 'laravel-cache-properties.index.v3.abc123', 'value' => serialize(['stale']), 'expiration' => $now + 3600],
        ['key' => 'laravel-cache-properties.index.v5.abc123', 'value' => serialize(['current']), 'expiration' => $now + 3600],
        ['key' => 'laravel-cache-properties.show.v3.42', 'value' => serialize(['stale']), 'expiration' => $now + 3600],
        ['key' => 'laravel-cache-properties.show.v5.42', 'value' => serialize(['current']), 'expiration' => $now + 3600],
    ]);

    $this->artisan('cache:prune-expired')->assertSuccessful();

    $keys = DB::connection($connection)->table($table)->pluck('key')->all();

    expect($keys)->toContain('laravel-cache-properties.cache.version')
        ->toContain('laravel-cache-properties.index.v5.abc123')
        ->toContain('laravel-cache-properties.show.v5.42')
        ->not->toContain('laravel-cache-properties.index.v3.abc123')
        ->not->toContain('laravel-cache-properties.show.v3.42');
});
