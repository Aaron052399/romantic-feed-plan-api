<?php

namespace app\admin\model\binding;

use think\Model;

/**
 * Request
 */
class Request extends Model
{
    // 表名
    protected $name = 'binding_request';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}