<?php

namespace app\admin\model\favorite;

use think\Model;

/**
 * Dish
 */
class Dish extends Model
{
    // 表名
    protected $name = 'favorite_dish';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}