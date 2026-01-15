<?php

namespace app\api\validate;

use think\Validate;

class Order extends Validate
{
    protected $rule = [
        'dishId' => 'require|number',
        'qty'    => 'number|>=:1',
        'note'   => 'max:1000',
        'status' => 'require|in:1,2,3,4',
    ];

    protected $scene = [
        'fromDish'    => ['dishId', 'qty', 'note'],
        'updateStatus'=> ['status'],
    ];
}

