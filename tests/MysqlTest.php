<?php

namespace alexshadie\dbwrapper;

use alexshadie\dbwrapper\tests\DbTestCase;

class MysqlTest extends DbTestCase
{
    /** @var Mysql */
    private $mysql;

    public function setUp(): void
    {
        $this->mysql = new Mysql($this->getConnection()->getConnection());
        parent::setUp();
    }

    public function testInsert()
    {
        $id = $this->mysql->insert('mysql_test', ['field' => 'field_N', 'value' => 'value_N']);
        $this->assertTableContains(
            ['id' => $id, 'field' => 'field_N', 'value' => 'value_N'],
            $this->getTable('mysql_test')
        );
    }

    public function testQueryOne()
    {
        $sql = "SELECT `field` FROM `mysql_test` WHERE `id` = :id";
        $params = ['id' => 1];
        $result = $this->mysql->queryOne($sql, $params);
        $this->assertEquals('field_name_1', $result);
    }

    public function testQueryOneEmpty()
    {
        $sql = "SELECT `field` FROM `mysql_test` WHERE `id` = :id";
        $params = ['id' => 123];
        $result = $this->mysql->queryOne($sql, $params);
        $this->assertNull($result);
    }

    public function testQueryRow()
    {
        $sql = "SELECT * FROM `mysql_test` WHERE `id` = :id";
        $params = ['id' => 1];
        $result = $this->mysql->queryRow($sql, $params);
        $this->assertEquals(['id' => '1', 'field' => 'field_name_1', 'value' => 'value_1'], $result);
    }

    public function testQueryRowEmpty()
    {
        $sql = "SELECT * FROM `mysql_test` WHERE `id` = :id";
        $params = ['id' => 101];
        $result = $this->mysql->queryRow($sql, $params);
        $this->assertNull(null);
    }

    public function testQueryAll()
    {
        $sql = "SELECT * FROM `mysql_test` WHERE `id` IN (:id1, :id2)";
        $params = ['id1' => 1, 'id2' => 2];
        $result = $this->mysql->queryAll($sql, $params);
        $this->assertEquals(
            [
                ['id' => '1', 'field' => 'field_name_1', 'value' => 'value_1'],
                ['id' => '2', 'field' => 'field_name_2', 'value' => 'value_2'],
            ],
            $result
        );
    }

    public function testQueryAllEmpty()
    {
        $sql = "SELECT * FROM `mysql_test` WHERE `id` IN (:id1, :id2)";
        $params = ['id1' => 101, 'id2' => 102];
        $result = $this->mysql->queryAll($sql, $params);
        $this->assertEquals(
            [],
            $result
        );
    }
}
