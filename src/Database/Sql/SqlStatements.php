<?php

namespace Girover\Tree\Database\Sql;

class SqlStatements
{
    public static $counter = 1;

    /**
     * table that contains nodes
     * @return string
     */
    public static function nodesTable()
    {
        return "tree_nodes";
    }
}
