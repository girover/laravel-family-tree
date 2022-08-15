![tree-template-3](https://user-images.githubusercontent.com/53403538/137398258-c43cba4a-8257-442a-9e0d-4c49f5e529d7.png)
# Building Family Tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/girover/tree.svg?style=flat-square)](https://packagist.org/packages/girover/tree)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/girover/tree/run-tests?label=tests)](https://github.com/girover/tree/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/girover/tree/Check%20&%20fix%20styling?label=code%20style)](https://github.com/girover/tree/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/girover/tree.svg?style=flat-square)](https://packagist.org/packages/girover/tree)


---
## Content

  - [Introduction](#introduction)
  - [prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Assets](#assets)
  - [Images](#images)
  - [Usage](#usage)
    - [Treeable Model](#treeable-model)
    - [Pointer](#pointer)
    - [Nodeable Model](#nodeable-model)
       - [Retrieving nodes](#retrieving-nodes)
       - [Adding nodes](#adding-nodes)
       - [Deleting nodes](#deleting-nodes)   
       - [Checking nodes](#checking-nodes)   
       - [Relationships](#relationships)   
       - [Relocating nodes](#relocating-nodes) 
       - [Attaching and Detaching nodes](#attaching-and-detaching) 
    - [Helpers](#helpers)
    - [Rendering Trees](#rendering-trees)
    - [Customizing node style](#customizing-node-style)
  - [Testing](#testing)
  - [Changelog](#changelog)
  - [Contributing](#contributing)
  - [Security Vulnerabilities](#security-vulnerabilities)
  - [Credits](#credits)
  - [License](#license)


## Introduction
**girover/laravel-family-tree** is a package that allows you to build family trees.
    With this package it will be very simple to create trees and add nodes to trees.   

Every tree is allowed to have 85 generations. Assuming each generation has 20 years, this means that 1700 years of data can be stored in one tree.    
Every node in a tree is allowed to have 676 direct children.

## prerequisites

- Laravel 8+
- PHP 8+
- Mysql 5.7+

## Installation

You can add the package via **composer**:

```bash
composer require girover/laravel-family-tree
```

Before installing the package you should configure your database.  

Then you can install the package by running Artisan command   
```bash
php artisan tree:install
```

this command will take care of the following tasks:   
 - Publishing Config file ```config\tree.php``` to the config folder of your Laravel application.   
 - Publishing migration files to folder ```Database\migrations``` in your application.   
 - Migrate the published migrations.   
 - Publishing Assets **(css, js, images)** to the public folder in your application and they will be placed in ```public\vendor\tree```.     
 - Publishing JSON Translation files to the ```resources\lang\vendor\tree```   
## Assets

After publishing assets (css, js, images), they will be placed in `public` folder 
of the project in a folder called `vendor/tree`.
You are free to move any of these assets to other directories.    

You should add the CSS file ```vendor/tree/css/tree.css``` to your blade file to get the tree styled.

## Images
Every node in tree has an avatar photo. Male and Female Icons by default will be stored in the public folder under  
```vendor/tree/images```.   
however you are free to choose another folder for the images of nodes, but
you must provide it in the ```config\tree.php``` file.
```php
    'photos_folder' => 'path/to/your/images',
```
So your images folder should be in the **```public```** folder.
Example: if images are stored in folder called ```images/avatars``` the configs must be:  
```php
    'photos_folder' => 'images/avatars',
```
**Note:**  When saving photos in storage folder, it is important to create symbolic link to the photos folder.


# Usage
To start building family trees, you must have two models. The first one represents trees in database and it must use the trait:    
```\Girover\Tree\Traits\Treeable```   
The second model represents nodes in these trees, and it must use trait:   
```\Girover\Tree\Traits\nodeable```   
***NOTE: The names of the models that represents trees and nodes in database must be provided in ```config/tree.php```.***    

```php
    // config/tree.php
    return [
        /*
        |----------------------------------------------------------
        | Model That uses trait \Girover\Tree\Traits\Treeable
        |----------------------------------------------------------
        |
        | example: App\Models\Family::class
        */
        'treeable_model' => App\Models\Family::class,
        /*
        |----------------------------------------------------------
        | Model That uses trait \Girover\Tree\Traits\Nodeable
        |----------------------------------------------------------
        |
        | example: App\Models\Person::class
        */
        'nodeable_model' => App\Models\Person::class,
        .
        .
        .
```
## Treeable Model

To start building a tree or creating a new tree, it is very simple and thanks to [Eloquent](https://laravel.com/docs/8.x/eloquent) models from [Laravel](http://laravel.com).   
The model that represents trees in database should use **trait**: ```\Girover\Tree\Traits\Treeable```
For example if your model is called ```Tree``` so it should looks like this:

```php
namespace App\Models;

use Girover\Tree\Traits\Treeable;

class Tree extends Model
{
    use Treeable;
}

```   

And now you can use your model to deal with trees.

```php
use App\Models\Tree;

$tree = Tree::create(
    [
        'info' => 'info',
        'another_info' => 'another info',
    ]
);
```

After creating the tree, you can start to add as many nodes as you want.    
Let's start adding the First node, the **```Root```**, to the tree.
```php
    $data = ['name'=>'root', 'birth_date'=>'2000-01-01'];

    $tree->createRoot($data);
```
**What if you want to make a new Root?**   
In this case you can use the method **```newRoot```**, and the previously created Root will become a child of the new created Root.
```php
    $new_root_data = ['name'=>'new_root', 'birth_date'=>'2001-01-01'];

    $tree->newRoot($new_root_data);
```
After creating the Root in the tree, let's add first child for the Root.
```php
    use Girover\Tree\Models\Tree;

    $tree = Tree::find(1);

    $first_child_data = ['name'=>'first child', 'birth_date'=>'2001-01-01'];

    $tree->pointerToRoot()->newChild($first_child_data ,'m'); // m = male

    // Or you can do this instead
    $tree->pointerToRoot()->newSon($first_child_data);
```
Now our tree consists of two nodes, the root and the first child of the root.   

You can call the following method on an object of Tree 
| #   | Method                   | Description                                           | Params                                        |
| --- | -------------------------- | ----------------------------------------------------- | --------------------------------------------- |
| 1   | `createRoot($data)`        | create root in the tree.                              | ```$data``` is array of root info             |
| 2   | `newRoot($data)`           | makes new root for the tree.                          | ```$data``` is array of info for the new root |
| 3   | `toHtml()`                 | convert the tree to html to view it                   |                                               |
| 4   | `draw()`                   | convert the tree to html to view it                   |                                               |
| 5   | `toTree()`                 | convert the tree to html to view it                   |                                               |
| 6   | `emptyTree()`              | return an empty tree to view it                       |                                               |
| 7   | `pointer()`                | To get the pointer inside the tree                    |                                               |
| 8   | `pointerToRoot()`      | To move the pointer to indicate to the root           |                                               |
| 9   | `pointerTo($nodeable)` | To move the pointer to the given node             |                                               |
| 10  | `goTo($nodeable)`          | To move the pointer to the given node             |                                               |
| 14  | `countGenerations()`       | To get how many generations this tree has             |                                               |
| 15  | `nodesOnTop()`             | Get the newest generation members in the tree         |                                               |
| 16  | `mainNode()`               | Get the main node in the tree                         |                                               |


## Pointer

A tree has a **Pointer** inside it, and this **Pointer** points to one node.    
Pointer can move through all nodes in the tree.     
Because the Pointer points to a **node** inside the tree, so it can call all [methods of model that uses Nodeable Trait](#node) .   
To get the pointer you can do the following:
```php
    use App\Models\Tree;

    $tree    = Tree::find(1);
    $pointer = $tree->pointer();
```
And now you can use the pointer to make a lot of actions inside the tree, for example moving through nodes, deleting and retrieving more information about nodes.   
Eg.
To move the pointer to specific node:
```php
    use App\Models\Tree;
    use App\Models\Node;

    $tree    = Tree::find(1);
    $node    = Node::find(10);

    $tree->pointer()->to($node);
```
And now you can get the node data by calling the method ```node```
```php
    $node = $pointer->node();
    echo $node->attribute_1;
    echo $node->attribute_2;
    echo $node->attribute_3;
```

Note that we called method ```node``` after we had called the method ```to($node)```.   
This is because when a tree object is created, its **Pointer** indicates to ```null```.

## Nodeable Model
 - [What is a node](#what-is-a-node)
 - [Retrieving nodes](#retrieving-nodes)
 - [Adding nodes](#adding-nodes)
 - [Deleting nodes](#deleting-nodes)   
 - [Checking nodes](#checking-nodes)   
 - [Relationships](#relationships)   
 - [Relocating nodes](#relocating-nodes) 
 - [Attaching and Detaching nodes](#attaching-and-detaching)   

### What is a node

**Node** is a **person** in a tree and every node in the tree is connected with another one by using **Location mechanism**.    

To retrieve a nodes you can use Eloquent model that uses trait ```Girover\Tree\Traits\Nodeable```
```php
    use App\Models\Node;

    $node    = Node::find(1);
```


### Retrieving nodes
### <!-- getting the tree of node -->
To get the tree that the node belongs to.
```php
    $node->getTree();
```
### <!-- getting father of node -->
To get the father of the node:
```php
    return $node->father();
```
### <!-- getting grand father of node -->
To get the grandfather of the node:
```php
    return $node->grandfather();
```
### <!-- getting ancestor of node that matches given generation -->
To get the ancestor that matches the given number as parameter. 
```php
    $node->ancestor(); // returns father
    $node->ancestor(2); // returns grandfather
    $node->ancestor(3); // returns the father of grandfather
```
### <!-- getting all ancestors of a node -->
To get all ancestors of a node
```php
    return $node->ancestors();
```
### <!-- getting all uncles of a node -->
To get all uncles of the node:
```php
    return $node->uncles();
```
### <!-- getting all aunts of a node -->
To get all aunts of the node:
```php
    return $node->aunts();
```
### <!-- getting all children of a node -->
To get all children of the node:
```php
    return $node->children();
```
### <!-- getting all sons of a node -->
To get all sons of the node:
```php
    return $node->sons();
```
### <!-- getting all daughters of a node -->
To get all daughters of the node:
```php
    return $node->daughters();
```
### <!-- getting all descendants of a node -->
To get all descendants of the node:
```php
    return $node->descendants();
```
### <!-- getting all male descendants of a node -->
To get all male descendants of the node:
```php
    return $node->maleDescendants();
```
### <!-- getting all female descendants of a node -->
To get all female descendants of the node:
```php
    return $node->femaleDescendants();
```
### <!-- getting the first child of a node -->
To get the first child of the node:
```php
    return $node->firstChild();
```
### <!-- getting the last child of a node -->
To get the last child of the node:
```php
    return $node->lastChild();
```
### <!-- getting all siblings of a node -->
To get all siblings of the node:
```php
    return $node->siblings();
```
### <!-- getting all brothers of a node -->
To get all brothers of the node:
```php
    return $node->brothers();
```
### <!-- getting all sisters of a node -->
To get all sisters of the node:
```php
    return $node->sisters();
```
### <!-- getting the next(younger) sibling of a node -->
To get the next sibling of the node. gets only one sibling.
```php
    return $node->nextSibling();
```
### <!-- getting all next(younger) siblings of a node -->
To get all the next siblings of the node. siblings who are younger.
```php
    return $node->nextSiblings();
```
### <!-- getting the next(younger) brother of a node -->
To get the next brother of the node. gets only one brother.
```php
    return $node->nextBrother();
```
### <!-- getting all next(younger) brothers of a node -->
To get all the next brothers of the node. brothers who are younger.
```php
    return $node->nextBrothers();
```
### <!-- getting the next(younger) sister of a node -->
To get the next sister of the node. gets only one sister.
```php
    return $node->nextSister();
```
### <!-- getting all next(younger) sisters of a node -->
To get all the next sisters of the node. sisters who are younger.
```php
    return $node->nextSisters();
```
### <!-- getting the previous(older) sibling of a node -->
To get the previous sibling of the node. only one sibling.
```php
    return $node->prevSibling();
```
### <!-- getting all previous(older) siblings of a node -->
To get all the previous siblings of the node. siblings who are older.
```php
    return $node->prevSiblings();
```
### <!-- getting the previous(older) brother of a node -->
To get the previous brother of the node. only one brother.
```php
    return $node->prevBrother();
```
### <!-- getting all previous(older) brothers of a node -->
To get all the previous brothers of the node. brothers who are older.
```php
    return $node->prevBrothers();
```
### <!-- getting the previous(older) sister of a node -->
To get the previous sister of the node. only one sister.
```php
    return $node->prevSister();
```
### <!-- getting all previous(older) sisters of a node -->
To get all the previous sisters of the node. sisters who are older.
```php
    return $node->prevSisters();
```
### <!-- getting the first sibling of a node -->
To get the first sibling of the node.
```php
    return $node->firstSibling();
```
### <!-- getting the last sibling of a node -->
To get the last sibling of the node.
```php
    return $node->lastSibling();
```
### <!-- getting the first brother of a node -->
To get the first brother of the node.
```php
    return $node->firstBrother();
```
### <!-- getting the last brother of a node -->
To get the last brother of the node.
```php
    return $node->lastBrother();
```
### <!-- getting the first sister of a node -->
To get the first sister of the node.
```php
    return $node->firstSister();
```
### <!-- getting the last sister of a node -->
To get the last sister of the node.
```php
    return $node->lastSister();
```


### Adding nodes

### <!-- Creating father for a node -->
The next code will create father for a node, only if this node is a Root in the tree.   
If the node is not a ```root```, ```Girover\Tree\Exceptions\TreeException``` will be thrown.
```php
    $data = ['name' => $name, 'birth_date' => $birth_date];
    $node->createFather($data);
    // OR
    $tree = Tree::find(1);
    $tree->pointerToRoot()->createFather($data);
```

### <!-- Creating new sibling for a node -->
To create new sibling for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
       
    return $node->newSibling($data, 'm'); // m = male
```

### <!-- Creating new brother for a node -->
To create new brother for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newBrother($data);

    // or you can use the newSibling method
    $node->newSibling($data, 'm');
```

### <!-- Creating new sister for a node -->
To create new sister for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newSister($data);

    // or you can use the newSibling method
    $node->newSibling($data, 'f');
```

### <!-- Creating new son for a node -->
To create new son for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newSon($data);

    // or you can use the newChild method
    $node->newChild($data, 'm');
```

### <!-- Creating new daughter for a node -->
To create new daughter for the node:
```php
    $data = ['name'=>$name, 'birth_date'=>$birth_date];
    $node->newDaughter($data);
    
    // or you can use the newChild method
    $node->newChild($data, 'f');
```    

### <!-- Updating: to make node as main node -->
To make the node as the main node in its tree.   
```php
    $node->makeAsMainNode();
```

### Deleting nodes

### <!-- Deleting: to delete a node -->
To delete a node.
```php
    use App\Models\Person;

    $node = Person::find(10)->delete();
```
**note:** The node will be deleted with all its descendants.   
But if you want just to delete the node and not its descendants,   
then you can move its descendants before deleting the node.
```php
    use App\Models\Person;

    $node = Person::find(10);
    $another_node = Person::find(30);

    $node->moveChildrenTo($another_node);
    $node->delete();
```
Or you can directly move children to the father of deleted node by 
using ```onDeleteMoveChildren``` method without passing any node as parameter.
```php
    use App\Models\Person;

    $node = Person::find(10);

    $node->onDeleteMoveChildren()->delete();
```

### <!-- Deleting: to delete all children of a node -->
To delete all children of a node:
```php
    use App\Models\Person;

    $node = Person::find(10);

    return $node->deleteChildren(); // number of deleted nodes
```

### Checking nodes 

### <!-- Determining is Rot -->
To determine if the node is root in the tree
```php
    return $node->isRoot(); // returns true or false
```




### <!-- Determining is Ancestor of -->
To determine if the node is ancestor of another node:
```php
    use App\Models\Person;

    $node = Person::find(1);
    $another_node = Person::find(2);

    return $node->isAncestorOf($another_node); // returns true OR false
```

### <!-- Determining is father of -->
To determine if the node is father of another node:
```php
    use App\Models\Person;

    $node = Person::find(1);
    $another_node = Person::find(2);

    return $node->isFatherOf($another_node); // returns true OR false
```


### <!-- Determining ha children -->
Determine if the node has children
```php
    return $node->hasChildren(); // returns true or false
```

### <!-- Determining is child of -->
To determine if the node is child of another node:
```php
    use App\Models\Person;

    $node = Person::find(1);
    $another_node = Person::find(2);
    
    return $node->isChildOf($another_node); // returns true OR false
```

### <!-- Determining has siblings -->
Determine if the node has siblings
```php
    return $node->hasSiblings(); // returns true or false
```

### <!-- Determining is sibling of -->
To determine if the node is sibling of another node:
```php
    use App\Models\Person;

    $node = Person::find(1);
    $another_node = Person::find(2);
    
    return $node->isSiblingOf($another_node); // returns true OR false
```

### <!-- counting children of a node -->
To count the children of the node:
```php
    return $node->countChildren();
```

### <!-- counting sons of a node -->
To count sons of a node:
```php
    return $node->countSons();
```

### <!-- counting daughter of a node -->
To count daughters of the node:
```php
    return $node->countDaughters();
```

### <!-- counting siblings of a node -->
To count all siblings of the node:
```php
    return $node->countSiblings();
```

### <!-- counting brothers of a node -->
To count all brothers of the node:
```php
    return $node->countBrothers();
```

### <!-- counting sisters of a node -->
To count all sisters of the node:
```php
    return $node->countSisters();
```

### <!-- counting all descendants of a node -->
To count all descendants of the node:
```php
    return $node->countDescendants();
```

### <!-- counting male descendants of a node -->
To count all male descendants of the node:
```php
    return $node->countMaleDescendants();
```

### <!-- counting female descendants of a node -->
To count all female descendants of the node:
```php
    return $node->countFemaleDescendants();
```

### Relationships

### <!-- Relations: getting all wives of a node -->

To get all wives of the node:
```php
    $node->wives;
    // to add constraints
    $node->wives()->where('name', $name)->get();
```
When trying to get wives of female node a ```Girover\Tree\Exceptions\TreeException``` will be thrown.   
Note that this will get divorced wives too.   
To get only wives who are not divorced you can do this:
```php
    $node->wives()->ignoreDivorced()->get();
```
### <!-- Relations: getting husbands of a node -->
To get husbands of the node:
```php
    $node->husband;
    // to add constraints
    $node->husband()->where('name', $name)->get();
```
When trying to get husband of male node a ```Girover\Tree\Exceptions\TreeException``` will be thrown.   
Note that this will get divorced husbands too.   
To get only husbands who are not divorced you can do this:
```php
    $node->husband()->ignoreDivorced()->get();
```

### <!-- Relations to assign wife to a node -->
To assign wife to a node:
```php
    $wife = Node::find($female_node_id)
    $data = ['date_of_marriage'=>'2000/01/01', 'marriage_desc'=>'description'];
       
    return $node->getMarriedWith($wife, $data);
```
When trying to do this with a female node (woman) a ```Girover\Tree\Exceptions\TreeException``` will be thrown.   so if ```$node``` is a woman Exception will be thrown.

### <!-- Relations to divorce a wife -->
To divorce a wife
```php
    $husband = Node::find($male_node_id)
    $wife    = Node::find($female_node_id)
       
    return $husband->divorce($wife);
```
When trying to do this with a female node a ```Girover\Tree\Exceptions\TreeException``` will be thrown. so if ```$husband``` is a woman Exception will be thrown.

### <!-- Relations to upload photo for a node -->
To set uploaded photo to the node:
```php
    <?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Node;

    class PersonController extends Controller
    {
        public function addPhoto(Request $request)
        {
            $person     = Node::find($request->person_id);
            $photo      = $request->file('photo');

            $person->setPhoto($photo, 'new name');

            return view('persons.index')->with('success', 'photo was added');
        }
    }
```


## Relocating nodes

### <!-- Relocating: to move a node to another node -->
to move an existing node to be child of another node.   
**Note** this will move the node with its children.
```php
    use App\Models\Node;

    $node = Node::find(10);
    $another_node = Node::find(30);

    $node->moveTo($another_node);
```

### <!-- Relocating: to move children of a node to another node -->
To move children of a node to be children of another node  
you can do this:
```php
    use App\Models\Node;

    $node = Node::find(10);
    $another_node = Node::find(30);

    $node->moveChildrenTo($another_node);

```

**Note:** When trying to move node1 to node2, if node2 is descendant of node1 
```TreeException``` will be thrown.   
The same exception will be thrown if node2 is a female node.

### <!-- Relocating: to move a node to be after another node -->
To move a node after another node
you can do this:
```php
    use App\Models\Node;

    $node = Node::find(10);
    $another_node = Node::find(30);

    $node->moveAfter($another_node);
```

### <!-- Relocating: to move a node to be before another node -->
To move a node before another node
you can do this:
```php
    use App\Models\Node;

    $node = Node::find(10);
    $another_node = Node::find(30);

    $node->moveBefore($another_node);
```
**Note:** When trying to move the Root or when trying to move a node after or before one of its descendants 
```TreeException``` will be thrown.  

### <!-- Getting generation of a node -->
to get the generation number of a node in a tree:
```php
    return $node->generation(); // int or null
```


###

**Note**: You can use the **Pointer** of tree to access all methods of class Node.   
for example:
```php
    use Girover\Tree\Models\Tree;

    $tree = Tree::find(1);
    
    $tree->pointer()->to('aa.aa')->father();       // move Pointer to location 'aa.aa' and then get its father.
    $tree->pointer()->grandfather();       // get grandfather of node that Pointer indicates to
    $tree->pointer()->ancestor(3);            // get father of node that Pointer indicates to
    $tree->pointer()->children();          // get children of node that Pointer indicates to
    $tree->pointer()->sons();              // get sons of node that Pointer indicates to
    $tree->pointer()->newDaughter($data);  // create daughter for the node that Pointer indicates to
    $tree->pointer()->newSon($data);  // create son of node that Pointer indicates to
    $tree->pointer()->newSister($data);  // create sister for the node that Pointer indicates to
    $tree->pointer()->newChild($data, 'm');  // create son for the node that Pointer indicates to
    $tree->pointer()->firstChild();  // get the first child of node that Pointer indicates to
    .
    .
    .
    .
    .
    $tree->pointer()->toHtml();
```   

## Attaching and Detaching
When creating nodeable models by using methods ```create``` or ```save```, the created model will not be attached with any node in any tree.   

```php
    use App\Models\Person;

    $person = Person::create(['name'=>'Person', 'birth_date'=>'2000-01-01']);
    
    $another_person = new Person;
    $another_person->name = 'Another';
    $another_person->birth_date = '2000-01-01';
    $another_person->save();

```
Both $person and $another_person will be created in database table, but they will not be a node yet in any tree.  
To make Adam as a node in a tree you can use all nodeable methods like:   

```php
    use App\Models\Person;

    $new_person = Person::where('name', 'new person')->first();

    $person = Person::find(1);

    $person->newSon($new_person);
    //or
    $person->createFather($new_person); // only if $person is a root in the tree 
    //or
    $person->newBrother($new_person); // only if $person is not a root in the tree  
    //or
    .
    .
    .

```
### detaching
When deleting nodeable models they will be deleted from database table, but what if you need to delete(detach) them from a tree while keeping them in database.   
Let's look at the following code:

```php
    use App\Models\Person;

    $person = Person::find(100);
    $person->delete();    
```
In the code above, the person will be deleted from database with all its descendants.   

But we need only to delete it from a tree not from database. Look at this code:

```php
    use App\Models\Person;

    $person = Person::find(100);
    $person->detachFromTree(); 

```
Now the person still exists in database, but it is not a node in any tree anymore.

## Helpers

To get all detached nodeable models, you can use the helper function ```detachedNodeables```.   
This will return instance of ```\Illuminate\Database\Eloquent\collection``` with all detached nodeable models.

```php
    <?php

    $detached = detachedNodeables();

```

To get the count of detached nodeable models, use the helper function ```countDetachedNodeables```.  

```php
    <?php

    $count_detached = countDetachedNodeables();

```

## Rendering trees

To show a tree in the browser you can use one of these methods ```toHtml```, ```toTree``` or ```draw``` on an object of the Treeable model eg. ```App\Models\Tree```
```php
    <?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Tree;

    class TreeController extends Controller
    {
        public function index()
        {
            $tree     = Tree::find(1);

            $treeHTML = $tree->toHtml()
            // OR
            $treeHTML = $tree->toTree()
            // OR
            $treeHTML = $tree->Draw()

            return view('tree.index')->with('treeHTML', $treeHTML);
        }
    }
```
And now inside your blade file you can render the tree by using custom blade directive 
```@tree($treeHtml)``` or by using this syntax ```{!! toHtml !!}```. 

```html
    <!-- views/tree/index.blade.php -->
    <div>
        @tree($treeHtml)
    </div>
    <!-- OR -->
    <div>
        {!! $treeHTML !!}
    </div>
```
**Note** the above example will render the entire tree, but if you want to render a subtree starting from a specific node you can use the **Pinter** to do that:
```php
    <?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Tree;
    use App\Models\Node;

    class TreeController extends Controller
    {
        public function index()
        {
            $tree     = Tree::find(1);

            $person     = Node::find(11);

            $treeHTML = $tree->pointer()->to($person)->toHtml();
            // OR
            $treeHTML = $tree->pointer()->to($person)->toTree();
            // OR
            $treeHTML = $tree->pointer()->to($person)->draw();

            return view('tree.index')->with('treeHTML', $treeHTML);
        }
    }
```

You can also use a node model to do the same by using one of these methods ```toHtml```, ```toTree``` or ```draw``` on an object of the nodeable model eg. ```App\Models\Node```:
```php
    <?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Node;

    class TreeController extends Controller
    {
        public function index()
        {
            $person  = Node::find(11);

            $treeHTML = $person->toHtml();
            // OR
            $treeHTML = $person->toTree();
            // OR
            $treeHTML = $person->draw();

            return view('tree.index')->with('treeHTML', $treeHTML);
        }
    }
```
### Customizing node style
If you want to add some html attributes to the nodes, then you can bind a custom function called ```nodeHtmlAttributes``` to the **Service Container**. This function must return a callable function which accept one argument represents nodeable model. And this callable function must return html attributes with their values as one string.   
Let's take an example.   
If your nodeable models have fields ```id, name, gender, is_died``` , then you want to add them as html attributes to the nodes to style or to control nodes.   

```php
    // AppServiceProvider
    public function boot()
    {
        $this->app->singleton('nodeHtmlAttributes',function($app){
            return function($nodeable){
                return " data-id='{$nodeable->id}' data-name='{$nodeable->name}' data-gender='{$nodeable->gender}' data-is-died='{$nodeable->is_died}' ";
            };
        });
    }
```
Now all node elements will have these attributes like:
```html
    <a class="node" data-id='1' data-name='name' data-gender='1' data-is-died='0'>...</a>
```
 

If you want to add some css classes to the nodes that have specific role, then you can bind a custom function called ```nodeCssClass``` to the **Service Container**. This function must return a callable function which accept one argument represents nodeable model. And this callable function must return css classes names as string.   
Let's take an example.   
If your nodeable models have an attribute called ```is_died``` which has values ```true``` or ```false```, then you want to add a css class called ```is-died``` to the nodes that have this value as ```true``` to give them more style.   

```php
    // AppServiceProvider
    public function boot()
    {
        $this->app->singleton('nodeCssClasses',function($app){
            return function($nodeable){
                return ($nodeable->is_died) ? 'is-died' :'';
            };
        });
    }
``` 

Now all nodeables that have ```is_died``` to true will be like:
```html
    <a class="node is-died">...</a>
```

## Testing

```bash
./vendor/bin/PHPUnit
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Majed Girover](https://github.com/girover)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
