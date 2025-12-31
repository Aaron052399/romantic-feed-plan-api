<?php

namespace app\admin\model\order;

use think\Model;

/**
 * Interaction
 */
class Interaction extends Model
{
    // 表名
    protected $name = 'order_interaction';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getContentAttr($value): string
    {
        return !$value ? '' : htmlspecialchars_decode($value);
    }
}