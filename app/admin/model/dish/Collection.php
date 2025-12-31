<?php

namespace app\admin\model\dish;

use think\Model;

/**
 * Collection
 */
class Collection extends Model
{
    // 表名
    protected $name = 'dish_collection';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}