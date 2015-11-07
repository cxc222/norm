<?php

namespace Norm;

use PDO;

class SQLite extends Database
{
    private $dsn;

    function __construct($opts=[])
    {
        if (! isset($opts['file']))
            throw new Exception('SQLite path missing');

        $this->dsn = "sqlite:{$opts['file']}";
    }

    function connect()
    {
        $this->pdo = new PDO($this->dsn);
    }

    function disconnect()
    {
        $this->pdo = NULL;
    }

    function ping()
    {
        return TRUE;
    }
}

