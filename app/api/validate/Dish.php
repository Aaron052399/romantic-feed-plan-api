<?php

namespace app\api\validate;

use think\Validate;

class Dish extends Validate
{
    protected $rule = [
        'name'     => 'require|max:100',
        'price'    => 'require|float|>=:0',
        'imageUrl' => 'max:255',
        'eta'      => 'number|>=:0',
        'catId'    => 'number',
    ];

    protected $scene = [
        'add'    => ['name', 'price', 'imageUrl', 'eta', 'catId'],
        'update' => ['name', 'price', 'imageUrl', 'eta', 'catId'],
    ];
}

