<?php

namespace Girover\Tree\Database;

use Illuminate\Support\Facades\DB;

class NodeQuerySql
{
    /**
     * Here we make a SQL statement for getting a table of all nodes in a tree
     * sorted by node path
     * 
     * @return string
     */
    public static function treeTable($treeable_id)
    {
        return '
            WITH RECURSIVE 

            nodes_with_child_number AS (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY tree_nodes.node_parent_id ORDER BY '.nodeableTable().'.'.sortColumn().' ASC) AS child_number 
            FROM '.nodeableTable().' join tree_nodes 
            on tree_nodes.nodeable_id = '.nodeableTable().'.id where tree_nodes.treeable_id = '.$treeable_id.'
            )
            , 
            cte_recursion  AS 
            ( 
            SELECT CAST(CONCAT(LPAD(nodes_with_child_number.child_number, 2, "0")) AS char(2000)) AS path
                , 1 AS generation
                , nodes_with_child_number.*
            FROM nodes_with_child_number 
            WHERE nodes_with_child_number.node_parent_id is null
            
            UNION ALL
            
                SELECT CONCAT(cte_recursion.path, ".", LPAD(nodes_with_child_number.child_number, 2, "0")) AS path
                , generation+1
                , nodes_with_child_number.*
                FROM nodes_with_child_number
                INNER JOIN cte_recursion ON (nodes_with_child_number.node_parent_id  = cte_recursion.node_id)
            )
            -- SELECT * FROM cte_recursion ORDER BY path
        ';
    }

    /**
     * Here we make a SQL statement for getting all nodes in a tree
     * sorted by node path
     * 
     * @return string
     */
    public static function treeNodesSortedByPathSql($treeable_id)
    {
        return static::treeTable($treeable_id).
               'SELECT * FROM cte_recursion ORDER BY path';
    }

    /**
     * Here we make a SQL statement for getting nodes in specific generation in a tree
     * sorted by node path
     * 
     * @return string
     */
    public static function treeGenerationNodes($treeable_id, $generation)
    {
        return static::treeTable($treeable_id).
               'SELECT * FROM cte_recursion WHERE generation = '.$generation.' ORDER BY path';
    }

    /**
     * Here we make a SQL statement for getting nodes from root to specific generation in a tree
     * sorted by node path
     * 
     * @return string
     */
    public static function treeThroughGenerationNodes($treeable_id, $generation)
    {
        return static::treeTable($treeable_id).
               'SELECT * FROM cte_recursion WHERE generation <= '.$generation.' ORDER BY path';
    }

    /**
     * Here we make a SQL statement for calculating generations quantity in a tree
     * sorted by node path
     * 
     * @return string
     */
    public static function treeCountGenerations($treeable_id)
    {
        return static::treeTable($treeable_id).
               'SELECT MAX(generation) as total_generations FROM cte_recursion ';
    }

    /**
     * Here we make a SQL statement to get all leaf nodes in a tree
     * sorted by node path
     * 
     * @return string
     */
    public static function treeLeafNodes($treeable_id)
    {
        return '
            SELECT n1.* FROM

            (SELECT * FROM people JOIN tree_nodes
                ON people.id = tree_nodes.nodeable_id
                WHERE treeable_id = '.$treeable_id.'
            ) AS n1 
            
            LEFT JOIN
            
            (SELECT * FROM people JOIN tree_nodes
                ON people.id = tree_nodes.nodeable_id
                WHERE treeable_id = '.$treeable_id.'
            ) AS n2
            
            ON n1.node_id = n2.node_parent_id
            
            WHERE n2.node_parent_id IS NULL
        ';
    }

    /**
     * Here we make a SQL statement for getting table for a subtree
     * sorted by node path
     *  
     * @return string
     */
    private static function subtreeTable($nodeable)
    {
        return '
            WITH RECURSIVE 

            nodes_with_child_number AS (
                SELECT *, ROW_NUMBER() OVER (PARTITION BY tree_nodes.node_parent_id ORDER BY '.nodeableTable().'.'.sortColumn().' ASC) AS child_number 
                FROM '.nodeableTable().' join tree_nodes 
                on tree_nodes.nodeable_id = '.nodeableTable().'.id where tree_nodes.treeable_id = '.$nodeable->treeable_id.'
            )
            , 
            subtree  AS 
            ( 
                SELECT CAST(CONCAT(LPAD(nodes_with_child_number.child_number, 2, "0")) AS char(2000)) AS path
                    , 1 AS generation
                    , nodes_with_child_number.*
                FROM nodes_with_child_number 
                WHERE nodes_with_child_number.nodeable_id = '.$nodeable->getKey().'
                
                UNION ALL
                
                    SELECT CONCAT(subtree.path, ".", LPAD(nodes_with_child_number.child_number, 2, "0")) AS path
                    , generation+1
                    , nodes_with_child_number.*
                    FROM nodes_with_child_number
                    INNER JOIN subtree ON (nodes_with_child_number.node_parent_id  = subtree.node_id)
            )
            
        ';
    }

    /**
     * Here we make a SQL statement for getting all nodes in a subtree
     * 
     * @return string
     */
    public static function subtreeNodes($nodeable)
    {
        return static::subtreeTable($nodeable).
                'SELECT * FROM subtree ORDER BY path';
    }

    /**
     * Here we make a SQL statement for getting all female nodes in a subtree
     * 
     * @return string
     */
    public static function subtreeMaleNodes($nodeable)
    {
        return static::subtreeTable($nodeable).
            'SELECT * FROM subtree WHERE '.gender().' = "'.male().'" ORDER BY path';
    }

    /**
     * Here we make a SQL statement for getting all male nodes in a subtree
     * 
     * @return string
     */
    public static function subtreeFemaleNodes($nodeable)
    {
        return static::subtreeTable($nodeable).
        'SELECT * FROM subtree WHERE '.gender().' = "'.female().'" ORDER BY path';
    }

    /**
     * Here we make a SQL statement for quantity of nodes in a subtree
     * 
     * @return string
     */
    public static function subtreeCountNodes($nodeable)
    {
        return static::subtreeTable($nodeable).
        'SELECT COUNT(*) as total_nodes FROM subtree';
    }

    /**
     * Here we make a SQL statement for getting all male nodes in a subtree
     * 
     * @return string
     */
    public static function subtreeCountMaleNodes($nodeable)
    {
        return static::subtreeTable($nodeable).
        'SELECT COUNT(*) as total_nodes FROM subtree WHERE '.gender().' = "'.male().'" ';
    }

    /**
     * Here we make a SQL statement for getting all male nodes in a subtree
     * 
     * @return string
     */
    public static function subtreeCountFemaleNodes($nodeable)
    {
        return static::subtreeTable($nodeable).
        'SELECT COUNT(*) as total_nodes FROM subtree WHERE '.gender().' = "'.female().'" ';
    }

    /**
     * Here we make a SQL statement for getting all nodes in a subtree
     * Who belongs to the given generation
     * 
     * @return string
     */
    public static function subtreeGenerationNodes($nodeable, $generation_number)
    {
        return static::subtreeTable($nodeable).
        'SELECT * FROM subtree WHERE generation = '.$generation_number.' ';
    }

    /**
     * Here we make a SQL statement for getting nodes from a node
     * through specific generation in a subtree
     * 
     * @return string
     */
    public static function subtreeThroughGenerationNodes($nodeable, $generation_number)
    {
        return static::subtreeTable($nodeable).
        'SELECT * FROM subtree WHERE generation <= '.$generation_number.' ';
    }

    /**
     * Here we make a SQL statement for getting all descendants of a node
     * 
     * @return string
     */
    public static function descendants($nodeable)
    {
        return static::subtreeTable($nodeable).
                'SELECT * FROM subtree WHERE nodeable_id <> '.$nodeable->getKey().' ORDER BY path';
    }

    /**
     * Here we make a SQL statement for getting all male descendants of a node
     * 
     * @return string
     */
    public static function descendantsMale($nodeable)
    {
        return static::subtreeTable($nodeable).
                'SELECT * FROM subtree WHERE nodeable_id <> '.$nodeable->getKey().
                ' AND '.gender().' = "'.male().'" ORDER BY path';
    }

    /**
     * Here we make a SQL statement for getting all female descendants of a node
     * 
     * @return string
     */
    public static function descendantsFemale($nodeable)
    {
        return static::subtreeTable($nodeable).
                'SELECT * FROM subtree WHERE nodeable_id <> '.$nodeable->getKey().
                ' AND '.gender().' = "'.female().'" ORDER BY path';
    }

    /**
     * Here we make a SQL statement for getting the generation number of a nodeable
     * 
     * @return string
     */
    public static function generationSql($nodeable)
    {        
        return '
            WITH RECURSIVE
            nodes AS (
                SELECT nodeable_id, node_id, node_parent_id FROM tree_nodes
                WHERE treeable_id = '.$nodeable->treeable_id.'
            )
            , 
            nodes_to_root AS(
                SELECT nodeable_id, node_id, node_parent_id, 1 AS depth FROM nodes
                WHERE nodeable_id = '.$nodeable->getKey().'
                
                UNION ALL
                
                SELECT nodes.*,nodes_to_root.depth+1 FROM nodes JOIN nodes_to_root
                ON nodes.node_id = nodes_to_root.node_parent_id
            )
            SELECT MAX(depth) AS generation FROM nodes_to_root
        ';
    }

    /**
     * Here we make a SQL statement for getting ancestor of a nodeable
     * 
     * @return string
     */
    public static function ancestor($nodeable, $ancestor)
    {        
        return '
        with recursive 
        nodeables_with_nodes as (
            SELECT * FROM '.nodeableTable().' JOIN tree_nodes
            ON tree_nodes.nodeable_id = '.nodeableTable().'.id
            where treeable_id = '.$nodeable->treeable_id.'
        )
        , 
        cte_recursive as(
           select *, 0 as depth from nodeables_with_nodes
            where nodeable_id = '.$nodeable->getKey().'
            
            UNION ALL
            
            select nodeables_with_nodes.*, cte_recursive.depth+1 from nodeables_with_nodes join cte_recursive
            on nodeables_with_nodes.node_id = cte_recursive.node_parent_id
            where cte_recursive.depth < '.$ancestor.'
        )
        select * from cte_recursive where depth = '.$ancestor.'
        ';
    }

    /**
     * Here we make a SQL statement for getting all ancestors of a nodeable
     * 
     * @return string
     */
    public static function ancestors($nodeable)
    {  
        return '
        WITH recursive
        nodeables_with_nodes AS (
            SELECT * FROM '.nodeableTable().' JOIN tree_nodes
            ON tree_nodes.nodeable_id = '.nodeableTable().'.id
            WHERE treeable_id = '.$nodeable->treeable_id.'
        )
        , 
        nodes_to_root AS(
        SELECT *, 0 as depth FROM nodeables_with_nodes
            WHERE nodeable_id = '.$nodeable->getKey().'
            
            UNION ALL
            
            SELECT nodeables_with_nodes.*,nodes_to_root.depth+1 FROM nodeables_with_nodes JOIN nodes_to_root
            ON nodeables_with_nodes.node_id = nodes_to_root.node_parent_id
        )
        SELECT * FROM nodes_to_root WHERE depth <> 0 ORDER BY depth ASC
        ';
    }



    public static function ancestorsSql()
    {
        return '
        WITH recursive
        nodeables_with_nodes AS (
            SELECT * FROM %s JOIN tree_nodes
            ON tree_nodes.nodeable_id = %s.id
            WHERE treeable_id = %d
        )
        , 
        nodes_to_root AS(
        SELECT *, 0 as depth FROM nodeables_with_nodes
            WHERE nodeable_id = %d
            
            UNION ALL
            
            SELECT nodeables_with_nodes.*,nodes_to_root.depth+1 FROM nodeables_with_nodes JOIN nodes_to_root
            ON nodeables_with_nodes.node_id = nodes_to_root.node_parent_id
        )
        SELECT * FROM nodes_to_root %s ORDER BY depth %s
        ';
    }
}