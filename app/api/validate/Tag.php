<?php

namespace app\api\validate;

use think\Validate;

class Tag extends Validate
{
    protected $rule = [
        'name' => 'require|max:50',
    ];

    protected $scene = [
        'add'    => ['name'],
        'update' => ['name'],
    ];
}

