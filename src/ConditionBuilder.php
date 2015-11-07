<?php

namespace Norm;

class ConditionBuilder
{
    private $queryBuilder;
    private $conds;
    public $params;
    // parent condition builder
    private $parent;

    function __construct($queryBuilder, $parent=NULL)
    {
        $this->queryBuilder = $queryBuilder;
        $this->parent = $parent;
        $this->conds = [];
        $this->params = [];
    }

    function where($field, $op, $val=NULL, $quote=TRUE)
    {
        return $this->_where($field, $op, $val, $quote);
    }

    function whereOR($field, $op, $val=NULL, $quote=TRUE)
    {
        return $this->_where($field, $op, $val, $quote, ' OR ');
    }

    private function _where($field, $op, $val=NULL, $quote=TRUE, $logic=' AND ')
    {
        if ($val === NULL) {
            $val = $op;
            $op = '=';
        }
        if ($quote) {
            $name = $this->queryBuilder->_nextParamName();
            $this->params[$name] = $val;
            $cond = "$field $op $name";
        } else {
            $cond = "$field $op $val";
        }
        if ($this->conds)
            $cond = $logic . $cond;
        $this->conds[] = $cond;
        return $this;
    }

    function beginWhereGroup()
    {
        return new self($this->queryBuilder, $this);
    }

    function endWhereGroup()
    {
        $this->parent->childEnded($this);
        if ($this->parent->parent)
            return $this->parent;
        return $this->queryBuilder;
    }

    function condStr()
    {
        return implode('', $this->conds);
    }

    protected function childEnded($child)
    {
        if ($child->conds) {
            $this->params = array_merge($this->params, $child->params);
            $conds = $child->condStr();
            if (count($child->conds) > 1)
                $conds = '(' . $conds . ')';
            $this->conds[] = " AND $conds";
        }
        return $this;
    }
}
