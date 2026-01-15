<?php

namespace app\api\validate;

use think\Validate;

class Category extends Validate
{
    protected $rule = [
        'name' => 'require|max:100',
    ];

    protected $scene = [
        'add'    => ['name'],
        'update' => ['name'],
    ];
}

