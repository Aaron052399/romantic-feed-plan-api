<?php

namespace app\admin\model\base;

use think\Model;

/**
 * User
 */
class User extends Model
{
    // 表名
    protected $name = 'base_user';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}