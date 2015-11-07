<?php

use Norm\Database;
use Norm\MySQL;
use Norm\SQLite;


class DatabaseTest extends PHPUnit_Framework_TestCase
{
    function testSQLite()
    {
        $db = Database::init('sqlite', Model::$sqliteCfg);
        $this->assertInstanceOf(SQLite::class, $db);
    }

    function testMySQL()
    {
        $db = Database::init('mysql', Model::$mysqlCfg);
        $this->assertInstanceOf(MySQL::class, $db);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testUnsupportedDatabase()
    {
        Database::init('oracle', []);
    }

}

