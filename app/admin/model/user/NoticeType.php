<?php

namespace app\admin\model\user;

use think\Model;

/**
 * NoticeType
 */
class NoticeType extends Model
{
    // 表名
    protected $name = 'user_notice_type';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime         = false;

    protected static function onAfterInsert($model): void
    {
        if ($model->weigh == 0) {
            $pk = $model->getPk();
            if (strlen($model[$pk]) >= 19) {
                $model->where($pk, $model[$pk])->update(['weigh' => $model->count()]);
            } else {
                $model->where($pk, $model[$pk])->update(['weigh' => $model[$pk]]);
            }
        }
    }
}