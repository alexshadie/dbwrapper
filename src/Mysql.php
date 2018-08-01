<?php

namespace alexshadie\dbwrapper;


class Mysql implements DBInterface
{
    /** @var \PDO */
    private $db;
    /** @var array */
    private $debugLog = [];
    /** @var bool */
    private $debug = false;

    /**
     * Mysql constructor.
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $table
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function insert(string $table, array $data): int
    {
        $keys = array_keys($data);
        $sql = "INSERT INTO `{$table}` " .
            "(" . implode(', ', array_map(function($item) {return "`{$item}`";}, $keys)) . ") " .
            "VALUES " .
            "(" . implode(', ', array_map(function ($item) {
                return ":" . $item;
            }, $keys)) . ")";

        if ($this->debug) {
            $this->addDebug($sql, $data);
        }

        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $val) {
            $stmt->bindValue(":" . $key, $val);
        }

        if (!$stmt->execute()) {
            throw new \Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
        }
        $result = $this->db->lastInsertId();
        if (!$result) {
            // Returning true if lastInsertId is zero. It may happen when the table doesn't have autoincrement key
            return true;
        }
        return $this->db->lastInsertId();
    }

    public function addDebug(string $query, array $args): void
    {
        $this->debugLog[] = [
            'query' => $query,
            'args' => $args,
        ];
    }

    /**
     * @param string $sql
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function queryOne(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return array_shift($row);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     * @throws \Exception
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        if ($this->debug) {
            $this->addDebug($sql, $params);
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":" . $key, $val);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
        }
        return $stmt;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function queryRow(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return $row;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function queryAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $row;
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function errorInfo()
    {
        return $this->db->errorInfo();
    }

    public function setDebug(bool $debug): Mysql
    {
        $this->debug = $debug;
        return $this;
    }

    public function getDebugLog(): array
    {
        return $this->debugLog;
    }


}