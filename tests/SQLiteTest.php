<?php

use Norm\Database;

include_once __DIR__ . '/_QueryTest.php';

class SQLiteTest extends _QueryTest 
{

    function setUp()
    {
        $this->db = Database::init('sqlite', Model::$sqliteCfg);
        $this->db->execDDL("create table user (id integer primary key autoincrement, name text not null default '', dob datetime)");
        $this->db->execDDL("create table balance (id integer primary key autoincrement, user_id integer, amount integer, FOREIGN KEY (user_id) REFERENCES user(id))");
    }

    function tearDown()
    {
        $this->db = NULL;
    }

    function testOne()
    {
        $this->assertTrue(1 == 1);
    }
}

