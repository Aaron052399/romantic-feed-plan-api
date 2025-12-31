<?php

namespace app\admin\model\dish;

use think\Model;

/**
 * Category
 */
class Category extends Model
{
    // 表名
    protected $name = 'dish_category';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}