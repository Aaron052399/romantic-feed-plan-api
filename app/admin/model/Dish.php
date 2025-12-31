<?php

namespace app\admin\model;

use think\Model;

/**
 * Dish
 */
class Dish extends Model
{
    // 表名
    protected $name = 'dish';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getPriceAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

    public function getAvgScoreAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }
}