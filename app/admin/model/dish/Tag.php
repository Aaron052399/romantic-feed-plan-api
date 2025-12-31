<?php

namespace app\admin\model\dish;

use think\Model;

/**
 * Tag
 */
class Tag extends Model
{
    // 表名
    protected $name = 'dish_tag';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}