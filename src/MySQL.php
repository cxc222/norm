<?php

namespace Norm;

use PDO;

class MySQL extends Database
{
    protected $user;
    protected $pass;
    protected $opts;

    function __construct($opts)
    {
        $this->user = isset($opts['user']) ? $opts['user'] : '';
        $this->pass = isset($opts['pass']) ? $opts['pass'] : '';
        $conn = [];
        if (isset($opts['unix_socket']))
            $conn[] = 'unix_socket=' . $opts['unix_socket'];
        else {
            if (isset($opts['host']))
                $conn[] = 'host=' . $opts['host'];
            if (isset($opts['port']))
                $conn[] = 'port=' . $opts['port'];
        }
        if (isset($opts['dbname']))
            $conn[] = 'dbname=' . $opts['dbname'];
        if (isset($opts['charset']))
            $conn[] = 'charset=' . $opts['charset'];

        $this->opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        if (isset($opts['options'])) {
            $tmp = $opts['options'];
            foreach ($tmp as $k => $v)
                $this->opts[$k] = $v;
        }

        $conn = implode(';', $conn);
        $this->dsn = 'mysql:' . $conn;
    }

    function connect()
    {
        $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->opts);
    }

    function disconnect()
    {
        try {
            $this->pdo->exec('KILL connection_id()');
        } catch(\Exception $ex) {
            // nothing
        }
        $this->pdo = NULL;
    }

    function ping()
    {
        if (! $this->pdo)
            return FALSE;
        try {
            $stmt = $this->pdo->query('SELECT 1');
            $stmt->fetch();
            $stmt->closeCursor();
            return TRUE;
        } catch (\Exception $ex) {
            return FALSE;
        }
    }
}
