<?php

namespace app\admin\model\feeding;

use think\Model;

/**
 * Record
 */
class Record extends Model
{
    // 表名
    protected $name = 'feeding_record';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}