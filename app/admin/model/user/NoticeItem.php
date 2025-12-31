<?php

namespace app\admin\model\user;

use think\Model;

/**
 * NoticeItem
 */
class NoticeItem extends Model
{
    // 表名
    protected $name = 'user_notice_item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime         = false;

    // 追加属性
    protected $append = [
        'typeNamesTable',
    ];

    protected $json = ['type_default_value'];


    public function getTypeNamesTableAttr($value, $row): array
    {
        return \app\admin\model\user\NoticeType::whereIn('name', $row['type_names'])->column('title', 'name');
    }

    public function getTypeNamesAttr($value): array
    {
        if ($value === '' || $value === null) return [];
        if (!is_array($value)) {
            return explode(',', $value);
        }
        return $value;
    }

    public function setTypeNamesAttr($value): string
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
}