--------------------------------------------------------------------------------------------------
  -- SQl Query for Getting all nodes in a tree sorted by path
--------------------------------------------------------------------------------------------------
WITH RECURSIVE 
 -- get nodes and nodeables with children number partitioned by parent_id and ordered by birth date
nodes_with_child_number AS (
 SELECT *, ROW_NUMBER() OVER (PARTITION BY nodes.parent_id ORDER BY nodeables.b_date ASC) AS child_number 
 FROM nodeables join nodes 
 on nodes.nodeable_id = nodeables.id where nodes.tree_id=1
)
, 
cte_recursion  AS 
( 
  -- Initial Block : Get the root node that has parent_id as null
  SELECT CAST(CONCAT(LPAD(nodes_with_child_number.child_number, 2, '0')) AS char(2000)) AS path
    , 1 AS generation
    , nodes_with_child_number.*
  FROM nodes_with_child_number 
  WHERE nodes_with_child_number.parent_id is null
  
  UNION ALL
  -- Recursive Block
     SELECT CONCAT(cte_recursion.path, '.', LPAD(nodes_with_child_number.child_number, 2, '0')) AS path
      , generation+1
      , nodes_with_child_number.*
     FROM nodes_with_child_number
     INNER JOIN cte_recursion ON (nodes_with_child_number.parent_id  = cte_recursion.node_id)
)

-- Get tree nodes sorted by path
SELECT * FROM cte_recursion
ORDER BY path ASC;

--------------------------------------------------------------------------------------------------
 -- Count generations
 SELECT MAX(generation) from cte_recursion

--------------------------------------------------------------------------------------------------
 -- Get father of a node
 SELECT * FROM nodes JOIN nodeables
	ON nodes.nodeable_id = nodeables.id
  WHERE nodeable_id = $node->parent_id;

--------------------------------------------------------------------------------------------------
-- Get children of a node
select * from nodeables join nodes
on nodes.nodeable_id = nodeables.id
where parent_id = [$node->node_id]
--------------------------------------------------------------------------------------------------
-- Get All siblings of the node : including the node itself
select * from nodeables join nodes
on nodes.nodeable_id = nodeables.id
where parent_id = [$node->parent_id]
--------------------------------------------------------------------------------------------------
-- Get All siblings of the node : without the node itself
select * from nodeables join nodes
on nodes.nodeable_id = nodeables.id
where parent_id = [$node->parent_id] and nodeable_id <> [$node->nodeable_id]

--------------------------------------------------------------------------------------------------
 -- Get all nodes from a node to the root
 with recursive
nodeables_with_nodes as (
	select * from nodes join nodeables
    on nodes.nodeable_id = nodeables.id
    where tree_id = $node->tree_id
)
, 
nodes_to_root as(
   select *, 0 as depth from nodeables_with_nodes
	where nodeable_id = $node->id
	
	UNION ALL
	
	select nodeables_with_nodes.*,nodes_to_root.depth+1 from nodeables_with_nodes join nodes_to_root
	on nodeables_with_nodes.node_id = nodes_to_root.parent_id
)
select * from nodes_to_root
--------------------------------------------------------------------------------------------------
-- Get all nodes from the root to the node
select * from nodes_to_root order by depth desc
--------------------------------------------------------------------------------------------------
-- Get all ancestors of the node : From father to the root
select * from nodes_to_root where nodeable_id <> $node->id
--------------------------------------------------------------------------------------------------
-- Get all ancestors of the node : From root to the father
select * from nodes_to_root where nodeable_id <> $node->id ORDER BY depth desc
--------------------------------------------------------------------------------------------------
-- Get a specific ancestor by number of the ancestor
--------------------------------------------------------------------------------------------------
with recursive
nodeables_with_nodes as (
	select * from nodes join nodeables
    on nodes.nodeable_id = nodeables.id
    where tree_id = $node->tree_id
)
, 
cte_recursive as(
   select *, 0 as depth from nodeables_with_nodes
	where nodeable_id = $node->id
	
	UNION ALL
	
	select nodeables_with_nodes.*, cte_recursive.depth+1 from nodeables_with_nodes join cte_recursive
	on nodeables_with_nodes.node_id = cte_recursive.parent_id
	where cte_recursive.depth < $ancestor_number
)
select * from cte_recursive where depth = $ancestor_number
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------

00000000000000000000000000000000000000000000000000
000000000000000000000000000000000000000000000000001
00000000000000000000000000000000000000000000000000
000000000000000000000000000000000000000000000000002
00000000000000000000000000000000000000000000000000
000000000000000000000000000000000000000000000000003
00000000000000000000000000000000000000000000000000
000000000000000000000000000000000000000000000000004
00000000000000000000000000000000000000000000000000
000000000000000000000000000000000000000000000000005