<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * A minimal, dependency-free service container providing constructor-less
 * binding, singletons and lazy resolution. Inspired by Laravel's container
 * but intentionally tiny so it stays fast on shared hosting.
 */
final class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * Register a lazily-resolved factory.
     */
    public function bind(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /**
     * Register a shared (singleton) factory. The factory runs at most once.
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->bind($id, function (Container $c) use ($id, $factory) {
            if (!array_key_exists($id, $this->instances)) {
                $this->instances[$id] = $factory($c);
            }

            return $this->instances[$id];
        });
    }

    /**
     * Store a pre-built instance.
     */
    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
        $this->bindings[$id] = static fn (): mixed => $value;
    }

    /**
     * Resolve a binding by id.
     */
    public function get(string $id): mixed
    {
        if (!isset($this->bindings[$id])) {
            throw new RuntimeException("No binding registered for [{$id}].");
        }

        return ($this->bindings[$id])($this);
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }
}
