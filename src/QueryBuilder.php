<?php

namespace Norm;

use PDO;


class QueryBuilder
{
    protected $database;
    protected $pdo;
    protected $conditionBuilder;
    protected $table;
    protected $fields;
    protected $orderByStr;
    protected $limitN;
    protected $offsetN;

    protected $paramCnt = 0;

    /**
     * @param \string $table 表名
     * @param \PDO $pdo PDO 对象
     */
    function __construct($table, $database)
    {
        $this->table = $table;
        $this->database = $database;
        $this->pdo = $database->getPDO();
        $this->conditionBuilder = new ConditionBuilder($this);
    }

    public function _nextParamName()
    {
        ++$this->paramCnt;
        return ':p' . $this->paramCnt;
    }

    /**
     * Join another table
     * e.g.:
     *   $db->with('user')->join('balance', 'user.id=balance.id')
     * or:
     *   $db->with('user AS u')->join('balance AS b', 'u.id = b.user_id')
     * @param string table table name
     * @param string on join condition
     * @return QueryBuilder
     */
    function join($table, $on)
    {
        $this->table .= " JOIN $table ON $on";
        return $this;
    }

    /**
     * where condition, using AND
     * e.g.:
     *   where('field', 10) // field = 10
     *   where('field', '=', 10) // field = 10
     *   where('field1', '=', 'field+10', FALSE) // field1 = field2+10
     * @param string field
     * @param string op
     * @param scalar val
     * @param quote bool where to quote val
     * @return $this
     */
    function where($field, $op, $val=NULL, $quote=TRUE)
    {
        $this->conditionBuilder->where($field, $op, $val, $quote);
        return $this;
    }

    /**
     * same with where, but use OR
     * @param string field
     * @param string op
     * @param scalar val
     * @param quote bool where to quote val
     * @return $this
     */
    function whereOR($field, $op, $val=NULL, $quote=TRUE)
    {
        $this->conditionBuilder->whereOR($field, $op, $val, $quote);
        return $this;
    }

    /**
     * start a new where group
     */
    function beginWhereGroup()
    {
        return $this->conditionBuilder->beginWhereGroup();
    }

    /**
     * e.g.:
     *   limit(10) // limit 10
     *   limit(10, 30) // limit 30, 10
     * @param int limit
     * @param int offset defaults to 0
     * @return $this
     */
    function limit($limit, $offset=0)
    {
        $this->limitN = $limit;
        $this->offsetN = $offset;
        return $this;
    }

    /**
     * e.g.:
     *   orderBy('rank', 'name desc')
     * @return $this
     */
    function orderBy(/* vargs */)
    {
        $this->orderByStr = implode(',', func_get_args());
        return $this;
    }

    private function sqlSelect()
    {
        $sql = ["SELECT {$this->fields} FROM {$this->table}"];
        $cond = $this->conditionBuilder->condStr();
        if ($cond) {
            $sql[] = "WHERE $cond";
        }

        if ($this->orderByStr) {
            $sql[] = "ORDER BY {$this->orderByStr}";
        }

        if ($this->limitN) {
            if ($this->offsetN)
                $limit = "LIMIT {$this->offsetN}, {$this->limitN}";
            else
                $limit = "LIMIT {$this->limitN}";
            $sql[] = $limit;
        }
        return implode(' ', $sql);
    }

    private function stmtSelect()
    {
        $sql = $this->sqlSelect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->conditionBuilder->params);
        return $stmt;
    }

    private function setFields($arr)
    {
        if (! $arr)
            $this->fields = '*';
        else if (is_array($arr))
            $this->fields = implode(',', $arr);
        else
            $this->fields = $arr;
    }

    private function setField($field)
    {
        $this->fields = $field;
    }

    /**
     * select a single row
     * @param string|array fields specify the fields to fetch
     * @return array result row in assoc array
     * @throws \PDOException
     */
    function get($fields='*')
    {
        $this->limit(1);
        $this->setFields($fields);
        $stmt = $this->stmtSelect();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * fetch a single row indexed by column numbers
     * @see get
     * @return array
     * @throws \PDOException
     */
    function getNum($fields='*')
    {
        $this->limit(1);
        $this->setFields($fields);
        $stmt = $this->stmtSelect();
        return $stmt->fetch(PDO::FETCH_NUM);
    }

    /**
     * select a single row as an instance of a class
     * @param string kls name of the class
     * @param string|array fields specify the fields to fetch
     * @return object result row
     * @throws \PDOException
     */
    function getClass($kls, $fields='*')
    {
        $this->limit(1);
        $this->setFields($fields);

        $stmt = $this->stmtSelect();
        $stmt->setFetchMode(PDO::FETCH_CLASS, $kls);
        return $stmt->fetch();
    }

    /**
     * like get but returns multiple rows
     */
    function all($fields='*')
    {
        $this->setFields($fields);
        $stmt = $this->stmtSelect();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * like getClass but returns multiple rows
     */
    function allClass($kls, $fields='*')
    {
        $this->setFields($fields);

        $stmt = $this->stmtSelect();
        $stmt->setFetchMode(PDO::FETCH_CLASS, $kls);
        return $stmt->fetchAll();
    }

    /**
     * like getNum but returns multiple rows
     */
    function allNum($fields='*')
    {
        $this->setFields($fields);
        $stmt = $this->stmtSelect();
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    /**
     * fetch a single column of a single row
     * @return string
     * @throws \PDOException
     */
    function value($field)
    {
        $this->limit(1);
        $this->setField($field);
        $stmt = $this->stmtSelect();
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return current($row);
    }

    private function aggregate($fn, $field)
    {
        $this->setField("$fn($field)");
        $stmt = $this->stmtSelect();
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $val = current($row);
        return $val === NULL? FALSE : intval($val);
    }

    function max($field)
    {
        return $this->aggregate('MAX', $field);
    }

    function min($field)
    {
        return $this->aggregate('MIN', $field);
    }

    function avg($field)
    {
        return $this->aggregate('AVG', $field);
    }

    /**
     * count matching rows
     * e.g.:
     *   count('user_id')
     *   count('distinct user_id')
     */
    function count($field='*')
    {
        return $this->aggregate('COUNT', $field);
    }

    private function execDML($sql, $params)
    {
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt) {
            throw new \InvalidArgumentException('bad stmt: ' . $sql);
        }
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert a single row
     * data example:
     *   [
     *     'id'=> 1, // 1 will be quoted
     *     'add_time'=>['now()'], // this will not.
     *   ]
     * @param array data
     * @return int last insert id
     * @throws \PDOException
     */
    function insert($data)
    {
        $cols = [];
        $ins = [];
        $params = [];
        foreach ($data as $k => $v) {
            $cols[] = $k;
            if (is_array($v)) {
                $ins[] = current($v);
            } else {
                $name = $this->_nextParamName();
                $ins[] = $name;
                $params[$name] = $v;
            }
        }
        $cols = implode(',', $cols);
        $ins = implode(',', $ins);
        $sql = "INSERT INTO {$this->table} ($cols) VALUES ($ins)";
        $this->execDML($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Insert multiple rows
     * @param array data
     * @param array|string columns
     * @param bool atomic within an transaction?
     * @return int rows inserted
     * @throws \PDOException
     */
    function batchInsert($data, $columns, $atomic=TRUE)
    {
        $ins = [];
        $params = [];
        foreach ($data as $row) {
            $tmpIns = [];
            foreach ($row as $v) {
                if (is_array($v))
                    $tmpIns[] = current($v);
                else {
                    $name = $this->_nextParamName();
                    $tmpIns[] = $name;
                    $params[$name] = $v;
                }
            }
            $ins[] = '(' . implode(',', $tmpIns) . ')';
        }
        if (is_array($columns))
            $columns = implode(',', $columns);
        $ins = implode(',', $ins);
        $sql = "INSERT INTO {$this->table} ($columns) VALUES $ins";
        return $this->execDML($sql, $params);
    }

    /**
     * 更新行
     * @return int 被更新的行数
     * @throws Exception 当条件为空时
     */
    function update($data)
    {
        $cond = $this->conditionBuilder->condStr();
        if (! $cond) {
            throw new Exception('UPDATE without condition?');
        }
        $up = [];
        $params = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) { // do not quote
                $v = current($v);
                $up[] = "$k = $v";
            } else {
                $name = $this->_nextParamName();
                $up[] = "$k = $name";
                $params[$name] = $v;
            }
        }
        $up = implode(',', $up);
        $sql = "UPDATE {$this->table} SET $up WHERE $cond";
        $params = array_merge($params, $this->conditionBuilder->params);
        return $this->execDML($sql, $params);
    }

    /**
     * 删除行
     * @return int 删除的行数
     * @throws Exception 当条件为空时
     */
    function delete()
    {
        $cond = $this->conditionBuilder->condStr();
        if (! $cond) {
            throw new Exception('DELETE without condition?');
        }
        $sql = "DELETE FROM {$this->table} WHERE $cond";
        return $this->execDML($sql, $this->conditionBuilder->params);
    }
}

