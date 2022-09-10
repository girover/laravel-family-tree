<?php

namespace Girover\Tree\Database;

class CteRecursiveQueryBuilder
{
    protected $with_recursive  = false;
    protected $cte     = [];
    protected $selects = [];
    public function withRecursive()
    {
        $this->with_recursive = true;
    }

    public function cte(string $cte_name, callable $function)
    {
        $this->cte[$cte_name] = $function();

        return $this;
    }

    public function select(string $column)
    {
        $this->selects[] = $column;

        return $this;
    }

    public function toSql()
    {
        $sql = ($this->with_recursive) ? 'WITH RECURSIVE ' : 'WITH ';

        foreach ($this->cte as $key => $value) {
            $sql .= ' '.$key.' AS (
                '.$value.'
            ),';
        }

        rtrim($sql, ',');

        $sql .= $this->select;

        return $sql;
    }
}
