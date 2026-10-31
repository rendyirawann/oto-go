<?php

namespace App\Actions\Vehicle;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for public master data (vehicle types, categories, brands,
 * models, service areas, price estimates).
 *
 * Instead of cache tags (unsupported by some stores) every key carries a
 * version number; flush() bumps the version so all old entries go stale at
 * once and expire on their own.
 */
class MasterDataCache
{
    private const VERSION_KEY = 'api:master:version';

    public const TTL_SECONDS = 3600;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        try {
            return Cache::remember(self::key($key), self::TTL_SECONDS, $callback);
        } catch (\Throwable) {
            // Cache store down: serve straight from the database.
            return $callback();
        }
    }

    public static function flush(): void
    {
        try {
            Cache::forever(self::VERSION_KEY, self::version() + 1);
        } catch (\Throwable) {
            // Nothing cached if the store is unavailable.
        }
    }

    private static function key(string $key): string
    {
        return 'api:master:v' . self::version() . ':' . $key;
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
