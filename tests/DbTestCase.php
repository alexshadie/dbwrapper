<?php

namespace alexshadie\dbwrapper\tests;

use PHPUnit\DbUnit\Database\Table;
use PHPUnit\DbUnit\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

class DbTestCase extends TestCase
{

    use TestCaseTrait;

    /**
     * Gets DBUnit table
     * @param $tableName
     * @return Table
     */
    public function getTable($tableName)
    {
        return new Table(
            new \PHPUnit\DbUnit\Database\Metadata\Table(
                $tableName,
                $this->getConnection()->getMetaData()
            ),
            $this->getConnection()
        );
    }

    /**
     * Returns the test database connection.
     *
     * @return \PHPUnit\DbUnit\Database\Connection
     */
    protected function getConnection()
    {
        $pdo = new \PDO("mysql:host=localhost;dbname=test", "test", "test");
        return $this->createDefaultDBConnection($pdo);
    }

    /**
     * Returns the test dataset.
     *
     * @return \PHPUnit\DbUnit\DataSet\IDataSet
     */
    protected function getDataSet()
    {
        $path = __DIR__ . "/datasets/" .
            substr(
                str_replace(
                    "\\",
                    "/",
                    str_replace('alexshadie\\dbwrapper\\', '', static::class)
                ),
                0,
                -4
            ) . ".php";
        return $this->createArrayDataSet(require $path);
    }
}