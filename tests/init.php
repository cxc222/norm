<?php

class Model
{
    public static $mysqlCfg = [
        'dbname' => 'test',
        'host' => '127.0.0.1',
        'charset' => 'UTF8',
        'user' => 'root',
        'pass' => '123',
    ];

    public static $sqliteCfg = [
        'file' => ':memory:',
    ];
}

class User extends Model
{
    public $id;
    public $name;
    public $dob;
}

class Balance extends Model
{
    public $id;
    public $user_id;
    public $amount;
}

$loader = require(__DIR__ . '/vendor/autoload.php');
$loader->addPsr4('Norm\\', __DIR__ . '/../src/');
return $loader;
