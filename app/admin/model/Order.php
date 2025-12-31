<?php

namespace app\admin\model;

use think\Model;

/**
 * Order
 */
class Order extends Model
{
    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getTotalAmountAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }
}