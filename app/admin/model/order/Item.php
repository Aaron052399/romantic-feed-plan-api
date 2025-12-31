<?php

namespace app\admin\model\order;

use think\Model;

/**
 * Item
 */
class Item extends Model
{
    // 表名
    protected $name = 'order_item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getPriceAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }
}