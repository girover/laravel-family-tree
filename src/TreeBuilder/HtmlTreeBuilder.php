<?php

namespace Girover\Tree\TreeBuilder;

use Girover\Tree\Database\NodeQuerySql;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\TreeBuilder\TreeBuilderInterface;
use Illuminate\Database\Eloquent\Model;

class HtmlTreeBuilder implements TreeBuilderInterface
{
    /**
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    protected $tree = null;
 
    /**
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    protected $nodes = null;

    /**
     * @var int uses in converting tree to html
     */
    public $nodesCount = 1;

    /**
     * @var callable uses to get custom css class name for specific nodeables.
     */
    protected $node_css_classes;

    /**
     * @var callable uses to get custom attributes for nodeables.
     */
    protected $node_html_attributes;

    /**
     * @var array Here we store callables from user to get custom Html strings.
     */
    protected $callables = [];

    /**
     * instantiate a tree generator
     * 
     * @param \Illuminate\Database\Eloquent\Model $tree
     * @return void
     */
    public function __construct(Model $tree) {

        $this->tree = $tree;

        // app()->has('nodeCssClasses') ? $this->callables['node_css_classes'] = app()->make('nodeCssClasses') : false;
        // app()->has('node_html_attributes') ? $this->callables['node_html_attributes'] = app()->make('node_html_attributes') : false;
        app()->has('tree_node_info') ? $this->callables['tree_node_info'] = app()->make('tree_node_info') : false;
    }

    /**
     * To add custom classes from the developer
     * depending on nodeable attributes
     * to add css classes to node element in the html tree
     * 
     * To active this, developer should bind closure to variable called nodeCssClasses
     * in AppServiceProvider boot method.
     * 
     * @param \Illuminate\Database\Eloquent\Model $nodeable
     * 
     * @return string css classes to add to node element in the html tree
     */
    public function nodeCssClasses($nodeable)
    {
        return ($resolve = $this->resolveCallable('node_css_classes', [$nodeable]))
                ? $resolve
                : '';
    }

    /**
     * Attributes which must be rendered to node element in html tree
     * 
     * @param \illuminate\Database\Eloquent\Model $nodeable
     * @return string
     */
    public function nodeHtmlAttributes($nodeable)
    {
        return ($resolve = $this->resolveCallable('node_html_attributes', [$nodeable]))
                ? $resolve
                : '';
    }

    /**
     * Info of the node to be displayed
     * 
     * @param \Illuminate\Database\EloquentModel $nodeable
     * @return string
     */
    public function nodeInfo($nodeable)
    {
        if ($resolve = $this->resolveCallable('tree_node_info', [$nodeable])) {
            return $resolve;
        }

        // Get the default node info Html
        return   '<div class="node-img"><img src="'.$nodeable->photoURL().'"></div>
                <div class="name">'.$nodeable->name.'</div>';            
    }

    /**
     * Resolve the stored callables
     * 
     * @param string $key
     * @param array $args
     * @return mixed
     */
    public function resolveCallable($key, array $args)
    {
        if (array_key_exists($key, $this->callables)) {

            if (is_callable($this->callables[$key])) {
                return call_user_func_array($this->callables[$key], $args);
            }

            return null;
        }

        return null;
    }

    /**
     * Get Html for empty tree. when the user has no trees in database
     *
     * @return string
     */
    public function emptyTree()
    {
        return '<div id="tree" class="tree"><ul>'
                . '<li>'
                . '<a class="node" data-role="empty">
                        <div class="empty-node">
                        <div class="node-info-wrapper">
                            <div class="node-info">
                                <div class="node-img"><img src="'. maleAsset() .'"></div>
                                <div class="name">add new</div>                
                            </div>
                        </div>
                        </div>
                    </a>'
                . '</li>'
            . '</ul></div>';
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodesFromPointer()
    {
        if ($this->tree->isPointerFree()) {
            $this->tree->pointerToRoot();
            return $this->tree->nodes();
        }
        // using load() to Eager load all `Wives`
        return ((nodeableModel())::fromQuery(NodeQuerySql::subtreeNodes($this->tree->pointer()->node())))->load('wives');
    }

    /**
     * Load specific number of generation of this tree
     *
     * @param int $number_of_generations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function loadGenerations($number_of_generations = 1)
    {
        if ($this->tree->isPointerFree()) {
            return ((nodeableModel())::fromQuery(NodeQuerySql::treeThroughGenerationNodes($this->tree->getKey(), $number_of_generations)))->load('wives');
        }
        // Using load('wives') for Eager loading all wives
        return ((nodeableModel())::fromQuery(NodeQuerySql::subtreeThroughGenerationNodes($this->tree->getKey(), $number_of_generations)))->load('wives');
    }

    /**
     * Load tree nodes
     *
     * Get all Nodes of this tree from database
     * and set them in the variable $nodes
     *
     * @param int|null $number_of_generations
     * @return \Girover\Tree\Models\Tree
     */
    public function load($number_of_generations = null)
    {
        if (! is_numeric($number_of_generations) && ! is_null($number_of_generations)) {
            throw new TreeException("Error: The given generation '".$number_of_generations."' should be number", 1);
        }
        $this->nodes = $number_of_generations
                       ? $this->loadGenerations($number_of_generations)
                       : $this->getAllNodesFromPointer();

        return $this;
    }

    /**
     * get all loaded nodes in this tree
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function loadedNodes()
    {
        return $this->nodes;
    }

    /**
     * Determine if the nodes of this tree are already loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->nodes !== null ? true : false;
    }

    /**
     * To count the tree nodes that are already loaded
     *
     * @return int
     */
    protected function countLoadedNodes()
    {
        if (null === $this->nodes) {
            return 0;
        }

        return $this->nodes->count();
    }

    /**
     * Count the nodes
     *
     * Count the number of nodes in the tree of current node
     * First check if there are nodes assigned to variable $node:
     * If there are no nodes then query the database
     * if there are nodes count them from $nodes
     *
     * @return int
     */
    public function countNodes()
    {
        return $this->nodes === null
               ? $this->tree->countNodesFromPointer()
               : $this->countLoadedNodes();
    }

    /**
     * Get item styled as Html code to print it in the tree
     *
     * @param Node $node
     * @param Node $is_node_father
     * @return string [ Html code ]
     */
    protected function getNodeStyled($node, $is_node_father)
    {
        $node_html = '';
        
        // Get wives of this father/node
        $wives = $node->wives->all();
        if ($is_node_father) {
            $node_html .= '<div class="parent">';
            $node_html .= $this->getHusbandHtml($node);
            $node_html .= $this->getWivesHtml($node, $wives);
            $node_html .= '</div>';
        
        } elseif (! empty($wives)) {
            $node_html .= '<div class="parent">';
            $node_html .= $this->getHusbandHtml($node, ' no-children');
            $node_html .= $this->getWivesHtml($node, $wives);
            $node_html .= '</div>';
        } else {
            $node_html .= $this->getChildHtml($node);
        }

        return $node_html;
    }

    /**
     * Get Html Code for the given node as husband node
     *
     * @param \Illuminate\Database\Eloquent\Model $node nodeable model
     * @param string $classes
     *
     * @return string
     */
    protected function getHusbandHtml($node, $classes = '')
    {
        return $this->getNodeHtml($node, 'husband'.$classes);
    }

    /**
     * Get Html Code for the given node as Child node
     *
     * @param \Illuminate\Database\Eloquent\Model $node
     * @return string
     */
    protected function getChildHtml($node)
    {
        return $this->getNodeHtml($node, 'child');
    }

    /**
     * Get Html Code for items that given as wives
     *
     * @param array $wives
     * @return string
     */
    protected function getWivesHtml($item, $wives)
    {
        $wivesCount = count($wives);
        if ($wivesCount > 0) {
            $firstWife = array_shift($wives);

            return $this->getNodeHtml($firstWife, 'wife', $wives);
        } else {
            // Return Html For Undefined Wife
            return  '<div class="wives-group">'
                      . '<a class="node empty" data-counter="'.$this->nodesCount++.'" data-husband-node-id="'.$item->node_id.'">
                         <div class="female-node wife empty">
                            <div class="node-info-wrapper">
                                <div class="node-info">
                                    <div class="node-img"><img src="'. femaleAsset() .'"></div>
                                    <div class="name"></div>
                                    <div class="wife-number">0</div>
                                </div>
                            </div>
                         </div>
                         </a>
                     </div>';
        }
        //            return $this->getNodeHtml($firstWife, 'wife', $wives);
    }

    /**
     * Get Wives Html Code from second wife to the last
     *
     * @param array $wives array of nodeables
     * @return string
     */
    protected function getOtherWivesStyled($wives)
    {
        $id = 2;
        $hText = '';
        foreach ($wives as $wife) {
            $hText .= '<a class="node '.$this->nodeCssClasses($wife).'" data-role="wife" data-counter="' . $this->nodesCount++ . '" '. $this->nodeHtmlAttributes($wife) .'>
                         <div class="female-node wife-'.$id.'">
                            <div class="node-info-wrapper">
                                <div class="node-info">
                                    '.$this->nodeInfo($wife).'
                                    <div class="wife-number">'.$id.'</div>
                                </div>
                            </div>
                         </div>
                       </a>';
            $id++;
        }

        return $hText;
    }

    /**
     * return html for one node
     *
     * @param \Illuminate\Database\Eloquent\Model $node
     * @param string $role
     * @param array  $wives
     * @return string
     */
    protected function getNodeHtml($node = null, $role = 'husband no-children', $wives = [])
    {
        if ($node === null) {
            return '';
        }

        $node_class = ($node->{gender()} == female()) ? 'female-node' : 'male-node';
        
        // If the node is root, show button for creating father for the root
        $addFather = (($node->isRoot()) and ($role != 'wife')) ? '<div id="add-father" data-node-id="'.$node->node_id.'" data-toggle="modal" data-target="#addChildModal" alt="add Father"><i class="fa fa-plus"></i></div>' : '';
        $showFather = (($node->node_id == $this->tree->pointer()->node()->node_id) and ($role != 'wife') and (! $node->isRoot())) ? '<div id="show-father" data-node-id="'.$node->node_id.'"  title="show Father"><i class="fa fa-arrow-up"></i></div>' : '';
        $nodeCollapse = ($role === 'husband') ? '<div class="node-collapse down"><i class="fa fa-chevron-circle-up"></i></div>' : '';

        $html = ($role == 'wife') ? '<div class="wives-group">' : '';
        $html .= '<a class="node '.$this->nodeCssClasses($node).'" data-counter="' . $this->nodesCount++ . '" data-role="'.$role.'" '.$this->nodeHtmlAttributes($node).'>
                    '.$addFather.$showFather.$nodeCollapse.
                    '<div class="'.$node_class.' '.$role.'">	    
                        <div class="node-info-wrapper">
                            <div class="node-info">
                                '.$this->nodeInfo($node).'
                                '.(($role === 'wife') ? '<div class="wife-number">1</div>' : '').'
                            </div>
                        </div>
                    </div> 
                </a>'.($this->getOtherWivesStyled($wives));
        
        return ($role == 'wife') ? $html.'</div>' : $html;
    }


    /**
     * Draw the current tree and return it as HTML
     *
     * @return string
     */
    public function build()
    {
        // Determine if nodes of this tree are loaded before starting to draw it.
        // if no nodes are loaded And there are nodes in database, so load them.
        if ($this->nodes == null) {
            $this->load();
            if ($this->nodes === null) {
                return $this->emptyTree();
            }
        }

        if (($nodes_count = $this->countNodes()) == 0) {
            return $this->emptyTree();
        }

        $fathers = [];
        $close_status = false;
        $is_node_father = false;
        $tree_html = '' ;

        //=============================
        // Start drawing the tree from the pointer
        $tree_html .= ' <div id="tree" class="tree"><ul>' ;
        //=============================
        for ($i = 0; $i < $nodes_count; $i++) {
            $node = $this->nodes[ $i ] ;
            $next_node = isset($this->nodes[$i + 1]) ? $this->nodes[ $i + 1 ] : null;
            $is_node_father = is_null($next_node) ? false : $node->isFatherOf($next_node);
            $is_node_sibling_of_next = is_null($next_node) ? false : $node->isSiblingOf($next_node);

            if ($close_status) {
                $father_loop_count = count($fathers);
                for ($j = $father_loop_count ; $j > 0; $j--) {
                    $node_father = array_pop($fathers);
                    if ($node->isSiblingOf($node_father)) {
                        $i--;
                        $close_status = false ;

                        break;
                    } else {
                        $tree_html .= ' </ul></li> ' ;
                    }
                }
                $close_status = false ;
            } else {
                $tree_html .= ' <li> '.$this->getNodeStyled($node, $is_node_father) ;
                // if ($node->isFatherOf($next_node)) {
                if ($is_node_father) {
                    $tree_html .= ' <ul> ' ;
                    $fathers[] = $node;
                } else {
                    // if ($node->isSiblingOf($next_node)) {
                    if ($is_node_sibling_of_next) {
                        $tree_html .= ' </li> ' ;
                    } else {
                        $tree_html .= ' </li></ul></li> ' ;
                        $close_status = true;
                    }
                }
            }
        }
        $tree_html .= '</ul></div>';

        return $tree_html;
    }

    /**
     * Rendering the tree as Html string
     * 
     * @return string
     */
    public function render()
    {
        return $this->build();
    }
}
