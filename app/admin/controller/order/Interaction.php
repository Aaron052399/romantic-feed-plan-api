<?php

namespace app\admin\controller\order;

use app\common\controller\Backend;

/**
 * 订单互动消息记录管理
 */
class Interaction extends Backend
{
    /**
     * Interaction模型对象
     * @var object
     * @phpstan-var \app\admin\model\order\Interaction
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\order\Interaction();
        $this->request->filter('clean_xss');
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}