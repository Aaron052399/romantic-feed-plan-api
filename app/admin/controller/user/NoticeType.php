<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 通知方式管理
 */
class NoticeType extends Backend
{
    /**
     * NoticeType模型对象
     * @var object
     * @phpstan-var \app\admin\model\user\NoticeType
     */
    protected object $model;

    protected string|array $defaultSortField = 'weigh,desc';

    protected array|string $preExcludeFields = ['id', 'create_time'];

    protected string|array $quickSearchField = ['id', 'title', 'name'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\user\NoticeType;
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}