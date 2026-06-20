<?php

declare(strict_types=1);

namespace App\Core;

use mysqli;
use mysqli_sql_exception;
use RuntimeException;

/**
 * Thin wrapper around mysqli that preserves 100% backward compatibility with
 * the legacy `$conn` connection while exposing a safe, prepared-statement
 * oriented API for the new repository layer.
 *
 * The legacy code expected a raw mysqli handle; `connection()` returns exactly
 * that, so existing modules (Store, Chat) can be dropped in untouched.
 */
final class Database
{
    private ?mysqli $mysqli = null;

    /**
     * @param array{host:string,name:string,user:string,pass:string,port?:int,charset?:string} $config
     */
    public function __construct(private array $config)
    {
    }

    /**
     * The raw mysqli handle — used by legacy modules as `$conn`.
     *
     * The connection is established lazily on first use, so pages that never
     * touch the database (e.g. the login form) render without opening a socket
     * and the app boots even before credentials are configured.
     */
    public function connection(): mysqli
    {
        if ($this->mysqli instanceof mysqli) {
            return $this->mysqli;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->mysqli = new mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['name'],
                (int) ($this->config['port'] ?? 3306)
            );
            $this->mysqli->set_charset($this->config['charset'] ?? 'utf8mb4');
        } catch (mysqli_sql_exception $e) {
            // Never leak credentials in the message.
            throw new RuntimeException('Database connection failed. Check config/database.php.', 0, $e);
        }

        return $this->mysqli;
    }

    /**
     * Run a prepared SELECT and return all rows.
     *
     * @param array<int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return $rows;
    }

    /**
     * Run a prepared SELECT and return the first row or null.
     *
     * @param array<int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $rows = $this->select($sql, $params);

        return $rows[0] ?? null;
    }

    /**
     * Run a prepared write (INSERT/UPDATE/DELETE) and return affected rows.
     *
     * @param array<int, mixed> $params
     */
    public function statement(string $sql, array $params = []): int
    {
        $stmt = $this->execute($sql, $params);
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected;
    }

    public function lastInsertId(): int
    {
        return (int) $this->connection()->insert_id;
    }

    /**
     * @param array<int, mixed> $params
     */
    private function execute(string $sql, array $params): \mysqli_stmt
    {
        $stmt = $this->connection()->prepare($sql);

        if ($params !== []) {
            $types = '';
            foreach ($params as $param) {
                $types .= match (true) {
                    is_int($param)   => 'i',
                    is_float($param) => 'd',
                    default          => 's',
                };
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        return $stmt;
    }
}
