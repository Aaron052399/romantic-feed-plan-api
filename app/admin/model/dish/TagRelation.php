<?php

namespace app\admin\model\dish;

use think\Model;

/**
 * TagRelation
 */
class TagRelation extends Model
{
    // 表名
    protected $name = 'dish_tag_relation';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}