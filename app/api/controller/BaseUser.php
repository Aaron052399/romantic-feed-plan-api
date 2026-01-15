<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\base\User as BaseUserModel;

class BaseUser extends Api
{
    protected function currentUserId(): int
    {
        $h = (int)$this->request->header('X-Base-User-Id', 0);
        return $h > 0 ? $h : 1;
    }

    protected function roleNumToEnum(int $v): string
    {
        return $v === 1 ? 'baby' : 'cook';
    }

    protected function genShortId(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len = random_int(6, 8);
        $s = '';
        for ($i = 0; $i < $len; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
        return $s;
    }

    protected function generateUniqueShortId(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $sid = $this->genShortId();
            $exists = BaseUserModel::where('short_id', $sid)->find();
            if (!$exists) return $sid;
        }
        return $this->genShortId();
    }

    public function ensure(): void
    {
        $role = (int)$this->request->post('role', 1);
        $ts = time();
        $sid = $this->generateUniqueShortId();
        $m = new BaseUserModel();
        $m->save(['short_id' => $sid, 'nickname' => '用户', 'role' => $this->roleNumToEnum($role), 'create_time' => $ts, 'update_time' => $ts]);
        $this->success('用户创建成功', ['id' => (int)$m->id, 'short_id' => $sid]);
    }

    public function setRole(): void
    {
        $uid = $this->currentUserId();
        $role = (int)$this->request->post('role', 1);
        BaseUserModel::where('id', $uid)->update(['role' => $this->roleNumToEnum($role), 'update_time' => time()]);
        $this->success('用户角色更新成功', true);
    }

    public function profile(): void
    {
        $id = (int)$this->request->get('id', 1);
        $u = BaseUserModel::where('id', (int)$id)->find();
        if (!$u) {
            $this->success('', null);
            return;
        }
        $this->success('', [
            '_id'       => (int)$u['id'],
            'nickname'  => (string)$u['nickname'],
            'role'      => (int)($u['role'] === 'baby' ? 1 : 2),
            'avatar'    => (string)($u['avatar'] ?? ''),
            'partner_id'=> $u['partner_id'] ?? null,
            'short_id'  => (string)($u['short_id'] ?? ''),
        ]);
    }

    public function updatePrefs(): void
    {
        $prefs = $this->request->post();
        $ts = time();
        BaseUserModel::where('id', (int)$prefs['user_id'])->update([
            'notice_order_toggle'       => !empty($prefs['noticeOrder']) ? 1 : 0,
            'notice_dish_ready_toggle'  => !empty($prefs['noticeDishReady']) ? 1 : 0,
            'notice_whisper_toggle'     => !empty($prefs['noticeWhisper']) ? 1 : 0,
            'sweetness'                 => (int)($prefs['sweetness'] ?? 0),
            'heart_bounce_toggle'       => !empty($prefs['heartBounce']) ? 1 : 0,
            'is_dark_toggle'            => !empty($prefs['isDark']) ? 1 : 0,
            'accent'                    => (string)($prefs['accent'] ?? 'pink'),
            'update_time'               => $ts,
        ]);
        $this->success('', true);
    }

    public function byShortId($shortId): void
    {
        $u = BaseUserModel::where('short_id', strtoupper((string)$shortId))->find();
        if (!$u) {
            $this->success('', null);
            return;
        }
        $this->success('', [
            '_id'      => (int)$u['id'],
            'nickname' => (string)$u['nickname'],
            'role'     => (int)($u['role'] === 'baby' ? 1 : 2),
            'short_id' => (string)($u['short_id'] ?? ''),
        ]);
    }

    public function role($id): void
    {
        $u = BaseUserModel::where('id', (int)$id)->find();
        if (!$u) {
            $this->success('', null);
            return;
        }
        $this->success('', (int)($u['role'] === 'baby' ? 1 : 2));
    }

    public function hasPartner($id): void
    {
        $u = BaseUserModel::where('id', (int)$id)->find();
        if (!$u) {
            $this->success('', false);
            return;
        }
        $this->success('', !empty($u['partner_id']));
    }
}

