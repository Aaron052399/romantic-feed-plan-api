<?php

namespace app\admin\model\dish;

use think\Model;

/**
 * Rating
 */
class Rating extends Model
{
    // 表名
    protected $name = 'dish_rating';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}