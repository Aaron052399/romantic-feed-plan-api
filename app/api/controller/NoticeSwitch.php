<?php

namespace app\api\controller;

use Throwable;
use think\facade\Db;
use app\common\controller\Frontend;
use app\admin\model\user\NoticeItem;
use modules\noticeswitch\library\Helper;

class NoticeSwitch extends Frontend
{
    protected array $noNeedPermission = ['index'];

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * @throws Throwable
     */
    public function index(): void
    {
        // 检查 account/notice 权限节点
        if (!$this->auth->check('account/notice')) {
            $this->error(__('You have no permission'), [], 401);
        }

        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $type            = $data['type'];
            $data['user_id'] = $this->auth->id;

            $noticeItem = NoticeItem::where('status', 1)->where('name', $data['name'])->find();
            if (!isset($noticeItem->type_default_value->$type)) {
                $this->error('通知配置项找不到啦~');
            }

            $typeDefaultValue = (bool)$noticeItem->type_default_value->$type;
            $userConfig       = Db::name('user_notice_config')
                ->where('user_id', $this->auth->id)
                ->where('name', $data['name'])
                ->where('type', $data['type'])
                ->find();
            if ($userConfig && $typeDefaultValue === (bool)$data['value']) {
                Db::name('user_notice_config')->where('id', $userConfig['id'])->delete();
            } else {
                Db::name('user_notice_config')->insert($data);
            }
            $this->success('配置已保存~');
        }
        $noticeItem = NoticeItem::where('status', 1)->select()->toArray();
        $modules    = [];
        $modulesNew = [];
        foreach ($noticeItem as $item) {
            $item['values'] = [];
            foreach ($item['type_default_value'] as $key => $value) {
                $item['values'][$key] = Helper::getUserConfig($this->auth->id, $item['name'], $key);
            }
            unset($item['type_default_value'], $item['type_names']);
            $modules[$item['module']][] = $item;
        }
        foreach ($modules as $key => $module) {
            $groups = [];
            foreach ($module as $item) {
                $groups[$item['group']][] = $item;
            }
            $modulesNew[$key] = $groups;
        }
        $this->success('', [
            'notices' => $modulesNew,
        ]);
    }
}