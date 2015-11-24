<?php

namespace Norm;

use PDO;

abstract class Database
{
    protected $pdo;

    protected static $drivers = [
        'mysql' => MySQL::class,
        'sqlite' => SQLite::class,
    ];

    static function init($type, $opt=[], $connect=TRUE)
    {
        if (! isset(self::$drivers[$type]))
            throw new \InvalidArgumentException('unsupported database type: ' . $type);

        $db = new self::$drivers[$type]($opt);
        if ($connect)
            $db->connect();
        return $db;
    }

    function getPDO()
    {
        return $this->pdo;
    }

    function with($table)
    {
        return new QueryBuilder($table, $this);
    }

    /**
     * execute fn in a new transaction
     * @param callable fn
     * @param array args
     * @return void
     * @throws \PDOException
     */
    function atomic($fn, $args=[])
    {
        try {
            $this->pdo->beginTransaction();
            $ret = call_user_func_array($fn, $args);
            $this->pdo->commit();
            return $ret;
        } catch(\Exception $ex) {
            $this->pdo->rollback();
            throw $ex;
        }
    }

    public function execDDL($sql)
    {
        return $this->pdo->exec($sql);
    }

    /**
     * still connected?
     * @return bool TRUE-connected, FALSE otherwise
     */
    abstract function ping();

    abstract function disconnect();

    abstract function connect();

    function reconnect()
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * reconnect if disconnected
     */
    function ensureConnection()
    {
        if (! $this->ping())
            $this->reconnect();
    }

    function quote($str)
    {
        return $this->pdo->quote($str);
    }

}
