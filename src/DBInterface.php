<?php

namespace alexshadie\dbwrapper;

interface DBInterface
{
    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert(string $table, array $data): int;

    /**
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement;

    /**
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function queryOne(string $sql, array $params = []);

    /**
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function queryRow(string $sql, array $params = []): ?array;

    /**
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function queryAll(string $sql, array $params = []): array;

    public function isTransactionStarted(): bool;

    public function startTransaction(): void;

    public function commit(): void;

    public function rollback(): void;
}