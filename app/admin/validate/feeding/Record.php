<?php

namespace app\admin\validate\feeding;

use think\Validate;

class Record extends Validate
{
    protected $failException = true;

    /**
     * 验证规则
     */
    protected $rule = [
    ];

    /**
     * 提示消息
     */
    protected $message = [
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];

}
