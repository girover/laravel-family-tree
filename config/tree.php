<?php
    return [
        /*
        |--------------------------------------------------------------------------
        | Model That uses trait Girover\Tree\Traits\Treeable
        |--------------------------------------------------------------------------
        |
        | example: App\Models\Family::class
        */
        'treeable_model' => null,

        /*
        |--------------------------------------------------------------------------
        | Model That uses trait Girover\Tree\Traits\Nodeable
        |--------------------------------------------------------------------------
        |
        | example: App\Models\Person::class
        */
        'nodeable_model' => null,

        /*
        |--------------------------------------------------------------------------
        | The nodeable table column that represents gender
        |--------------------------------------------------------------------------
        |
        |  Gender may be enum['0', '1'] or enum['m', 'f'] or enum['male', 'female']
        |  Or any other values
        */
        'gender' => [
            'column'   => 'gender',
            'male'    => 'm',
            'female'  => 'f',
        ],

        /*
        |--------------------------------------------------------------------------
        | Specify which column in nodeable table is used to order children by
        |--------------------------------------------------------------------------
        |
        |  Example: `b_date` or `birth_date` or any other name you chose
        */
        'children_order_by' => 'b_date',

        /*
        |--------------------------------------------------------------------------
        | Folder name for images in the public folder
        |--------------------------------------------------------------------------
        |
        | should be in the public folder
        | example: vendor/tree/images
        |        means that the folder is:
        |        path-to-project/public/vendor/tree/images
        */
        'photos_folder' => 'vendor/tree/images',
    ];