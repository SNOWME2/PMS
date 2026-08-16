<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneExpiredCache extends Command
{
    protected $signature = 'cache:prune-expired {--chunk=1000 : Rows to delete per batch}';

    protected $description = 'Delete expired rows from the database cache driver\'s tables (cache + cache_locks)';

    public function handle(): int
    {
        $connection = config('cache.stores.database.connection') ?? config('database.default');
        $chunk      = (int) $this->option('chunk');

        // NOTE: config('key', 'default')'s default only applies when the key is
        // MISSING from the array entirely. Laravel's cache.php stub defines
        // 'table'/'lock_table' as env('DB_CACHE_TABLE') with no fallback in some
        // versions, so if that env var isn't set, the key exists with value null
        // — and config()'s second argument never kicks in. Coalescing with ??
        // after the fetch handles both cases (missing key or present-but-null).
        $cacheTable = config('cache.stores.database.table') ?? 'cache';
        $lockTable  = config('cache.stores.database.lock_table') ?? 'cache_locks';

        $cacheDeleted = $this->pruneTable($connection, $cacheTable, 'expiration', $chunk);

        // cache_locks holds the atomic-lock rows used by Cache::lock(); it fills
        // up the same way and is easy to forget since it's rarely queried directly.
        $locksDeleted = $this->pruneTable($connection, $lockTable, 'expiration', $chunk);

        // Rows tied to a superseded version number (e.g. properties.index.v3.*
        // once properties.cache.version is v5) are permanently unreachable —
        // no future request will ever build that key again — but their TTL
        // hasn't necessarily passed yet, so pruneTable() above leaves them
        // alone. This second pass finds and deletes those independently of
        // expiration, since "will anyone ever read this again" (no) matters
        // more here than "has the clock run out" (not yet).
        $staleVersionDeleted = $this->purgeStaleVersionedKeys($connection, $cacheTable);

        $this->info(
            "Pruned {$cacheDeleted} expired cache row(s), {$locksDeleted} expired lock row(s), "
                . "and {$staleVersionDeleted} stale unreachable-but-not-yet-expired version row(s)."
        );

        return self::SUCCESS;
    }

    /**
     * Delete expired rows in batches. Postgres' query builder doesn't honor
     * ->limit() on delete() (only MySQL's grammar does), so instead of
     * ->where(...)->limit($chunk)->delete() — which would delete everything
     * in one unbounded statement on Postgres — we select a batch of primary
     * keys first, then delete by key. This keeps each transaction short even
     * on a cache table with a large backlog, avoiding long lock waits on a
     * busy database.
     */
    private function pruneTable(string $connection, string $table, string $expirationColumn, int $chunk): int
    {
        if (! DB::connection($connection)->getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        $total = 0;

        do {
            $keys = DB::connection($connection)
                ->table($table)
                ->where($expirationColumn, '<=', now()->timestamp)
                ->limit($chunk)
                ->pluck('key');

            if ($keys->isEmpty()) {
                break;
            }

            DB::connection($connection)->table($table)->whereIn('key', $keys)->delete();

            $total += $keys->count();
        } while ($keys->count() === $chunk);

        return $total;
    }

    /**
     * Delete stale versioned property cache keys like:
     *
     *   laravel-cache-properties.index.v1.714f08290c5d2b6b07d9a5b6061d8460
     *   laravel-cache-properties.show.v2.5
     *
     * If the current generation is v5, then all v1-v4 keys are unreachable and
     * can be safely removed even before their expiration timestamp expires.
     */
    private function purgeStaleVersionedKeys(string $connection, string $table): int
    {
        $db = DB::connection($connection);

        if (! $db->getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        $prefix = config('cache.prefix', '');
        $versionKey = $prefix . 'properties.cache.version';

        $currentVersion = $this->decodeVersionValue(
            $db->table($table)
                ->where('key', $versionKey)
                ->value('value')
                ?? 1
        );

        $staleKeys = [];

        $candidateKeys = $db->table($table)
            ->where('key', 'like', $prefix . 'properties.%')
            ->pluck('key');

        foreach ($candidateKeys as $candidateKey) {
            if ($candidateKey === $versionKey) {
                continue;
            }

            if (preg_match('/^' . preg_quote($prefix, '/') . 'properties\.(?:index|show|list)\.v(\d+)(?:\.|$)/', $candidateKey, $matches) !== 1) {
                continue;
            }

            if ((int) $matches[1] < $currentVersion) {
                $staleKeys[] = $candidateKey;
            }
        }

        if ($staleKeys === []) {
            return 0;
        }

        $uniqueStaleKeys = array_values(array_unique($staleKeys));
        $deleted = 0;

        while ($batch = array_splice($uniqueStaleKeys, 0, 1000)) {
            $deleted += $db->table($table)->whereIn('key', $batch)->delete();
        }

        return $deleted;
    }

    private function decodeVersionValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        $decoded = @unserialize($value);

        if (is_int($decoded)) {
            return $decoded;
        }

        if (is_string($decoded) && preg_match('/^-?\d+$/', $decoded)) {
            return (int) $decoded;
        }

        return 1;
    }
}
