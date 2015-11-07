<?php

use Norm\Database;

include_once __DIR__ . '/_QueryTest.php';

class MySQLTest extends _QueryTest 
{
    function setUp()
    {
        $this->db = Database::init('mysql', Model::$mysqlCfg);
        $this->db->execDDL("create table user (id int primary key auto_increment, name varchar(50) not null default '',  dob datetime)");
        $this->db->execDDL("create table balance (id int primary key auto_increment, user_id int not null, amount int default 0, FOREIGN KEY (user_id) REFERENCES user(id))");
    }

    function tearDown()
    {
        $this->db->execDDL("drop table balance");
        $this->db->execDDL("drop table user");
        $this->db = NULL;
    }
}

