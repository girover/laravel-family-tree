<?php

namespace Girover\Tree\Database\Sql;

class Delete extends SqlStatements
{
    /**
     * To delete node with its children from database
     *
     * @param int $tree_id
     * @param int $location
     * @return string
     */
    public static function nodeWithChildren($treeable_id, $location)
    {
        return " DELETE FROM `".static::nodesTable()."`  
                WHERE `treeable_id` = ".$treeable_id." 
                AND `location` like '".$location."%' ";
    }
}