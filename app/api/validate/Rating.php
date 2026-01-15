<?php

namespace app\api\validate;

use think\Validate;

class Rating extends Validate
{
    protected $rule = [
        'orderId' => 'require|number',
        'score'   => 'require|number|between:0,10',
        'comment' => 'max:1000',
    ];

    protected $scene = [
        'submit' => ['orderId', 'score', 'comment'],
    ];
}

