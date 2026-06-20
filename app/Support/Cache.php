<?php

declare(strict_types=1);

namespace App\Support;

/**
 * File-based cache — no Redis, no Memcached, no daemons. Perfect for shared
 * hosting. Stores serialized payloads with a TTL guard so the database is hit
 * far less often (helps eliminate repeated heavy queries / N+1 hotspots).
 */
final class Cache
{
    public function __construct(private string $directory)
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }
    }

    /**
     * Get a cached value or compute, store and return it.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->put($key, $value, $ttlSeconds);

        return $value;
    }

    public function get(string $key): mixed
    {
        $file = $this->path($key);
        if (!is_file($file)) {
            return null;
        }

        $payload = @unserialize((string) file_get_contents($file), ['allowed_classes' => false]);
        if (!is_array($payload) || !isset($payload['expires'], $payload['value'])) {
            return null;
        }

        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            @unlink($file);

            return null;
        }

        return $payload['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $expires = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $payload = serialize(['expires' => $expires, 'value' => $value]);
        @file_put_contents($this->path($key), $payload, LOCK_EX);
    }

    public function forget(string $key): void
    {
        @unlink($this->path($key));
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . hash('xxh128', $key) . '.cache';
    }
}
