<?php

/**
 * Function for getting the model that represents nodeable models
 */

use Illuminate\Support\Facades\DB;

if (! function_exists('nodeableModel')) {
    function nodeableModel()
    {
        return config('tree.nodeable_model');
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('treeableModel')) {
    function treeableModel()
    {
        return config('tree.treeable_model');
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('nodeableTable')) {
    function nodeableTable()
    {
        return (new (config('tree.nodeable_model')))->getTable();
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('treeableTable')) {
    function treeableTable()
    {
        return (new (config('tree.treeable_model')))->getTable();
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('photoPath')) {
    function photoPath()
    {
        return public_path(rtrim(config('tree.photos_folder'), '/\\')) . DIRECTORY_SEPARATOR;
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('photosURL')) {
    function photosURL()
    {
        return rtrim(config('tree.photos_folder'), '/\\') . '/';
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('malePhoto')) {
    function malePhoto()
    {
        return 'icon_male.png';
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('femalePhoto')) {
    function femalePhoto()
    {
        return 'icon_female.png';
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('photoIcon')) {
    function photoIcon($gender = 'm')
    {
        return ($gender === 'm') ? malePhoto() : femalePhoto();
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('maleAsset')) {
    function maleAsset()
    {
        return photosURL().malePhoto();
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('femaleAsset')) {
    function femaleAsset()
    {
        return photosURL().femalePhoto();
    }
}

/**
 * Function for getting the model that represents treeable models
 */
if (! function_exists('photoAsset')) {
    function photoAsset($nodeable_model)
    {
        $photo_path  = photoPath() . $nodeable_model->photo;
        $photo_asset = photosURL();
        
        return (file_exists($photo_path) and ! is_dir($photo_path)) 
                ? asset($photo_asset.$nodeable_model->photo) 
                : asset($photo_asset.photoIcon($nodeable_model->gender));
    }
}

/**
 * to get all nodeable models which are not attached with any node
 * in any tree yet.
 * 
 * @return \Illuminate\Database\Eloquent\Collection
 */
if (! function_exists('detachedNodeables')) {
    function detachedNodeables()
    {
        return (nodeableModel())::whereNull('location')->get();
    }
}

/**
 * to get the count of nodeable models which are not attached with any node
 * in any tree yet.
 * 
 * @return int
 */
if (! function_exists('countDetachedNodeables')) {
    function countDetachedNodeables()
    {
        return (nodeableModel())::whereNull('location')->count();
    }
}

/**
 * To get all methods from a class or trait.
 * @param string $class the class name.
 * @param bool $sort To sort the array of methods or not.
 * @return array
 */
if (! function_exists('allMethods')) {
    function allMethods($class, $sort = false)
    {
        $methods = [];
        $r = new ReflectionClass($class);
        foreach ($r->getMethods() as $method) {
            array_push($methods, $method->name);
        }
        if ($sort) {
            sort($methods);
        }

        return ['Methods count'=>count($methods), 'Methods'=>$methods];
    }
}