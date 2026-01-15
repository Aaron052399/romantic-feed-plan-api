<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\binding\Request as BindingRequestModel;
use app\admin\model\base\User as BaseUserModel;

class Binding extends Api
{
    protected function currentUserId(): int
    {
        $h = (int)$this->request->header('X-Base-User-Id', 0);
        return $h > 0 ? $h : 1;
    }

    protected function isValidShortId(string $id): bool
    {
        $len = strlen($id);
        if ($len < 6 || $len > 8) return false;
        return preg_match('/^[A-Za-z0-9]+$/', $id) === 1;
    }

    protected function roleNumToEnum(int $v): string
    {
        return $v === 1 ? 'baby' : 'cook';
    }

    public function createRequest(): void
    {
        $uid = $this->currentUserId();
        $target = (string)$this->request->post('targetUserInput', '');
        $ts = time();
        $user = null;
        $targetUserId = null;
        if ($this->isValidShortId($target)) {
            $u = BaseUserModel::where('short_id', strtoupper($target))->find();
            if ($u) {
                $user = $u->toArray();
                $targetUserId = (int)$user['id'];
            }
        }
        if (!$user) {
            $u = BaseUserModel::where('id', (int)$target)->find();
            if ($u) {
                $user = $u->toArray();
                $targetUserId = (int)$user['id'];
            }
        }
        if (!$user) {
            $this->success('', ['success' => false, 'message' => '用户不存在']);
            return;
        }
        if ($targetUserId === $uid) {
            $this->success('', ['success' => false, 'message' => '不能绑定自己']);
            return;
        }
        $current = BaseUserModel::where('id', $uid)->find();
        $currentRole = $current ? (string)$current['role'] : 'baby';
        $targetRole = (string)($user['role'] ?? 'baby');
        if ($currentRole === $targetRole) {
            $this->success('', ['success' => false, 'message' => '不能与同性绑定']);
            return;
        }
        $cu = $current ? $current->toArray() : [];
        if (!empty($cu['partner_id'])) {
            $this->success('', ['success' => false, 'message' => '你已有绑定的情侣']);
            return;
        }
        if (!empty($user['partner_id'])) {
            $this->success('', ['success' => false, 'message' => '对方已有绑定的情侣']);
            return;
        }
        $existing = BindingRequestModel::where('from_id', $uid)->where('to_id', $targetUserId)->where('status', 'pending')->select()->toArray();
        if ($existing) {
            $this->success('', ['success' => false, 'message' => '已有待处理的申请']);
            return;
        }
        $others = BindingRequestModel::where('to_id', $targetUserId)->where('status', 'pending')->select()->toArray();
        if ($others) {
            $this->success('', ['success' => false, 'message' => '对方已有待处理的绑定申请']);
            return;
        }
        $req = new BindingRequestModel();
        $req->save(['from_id' => $uid, 'to_id' => $targetUserId, 'status' => 'pending', 'create_time' => $ts, 'update_time' => $ts]);
        $this->success('', ['success' => true, 'requestId' => (int)$req->id, 'message' => '申请已发送']);
    }

    public function pending(): void
    {
        $uid = $this->currentUserId();
        $r = BindingRequestModel::where('to_id', $uid)->where('status', 'pending')->select()->toArray();
        if (!$r) {
            $this->success('', null);
            return;
        }
        $req = $r[0];
        $this->success('', [
            '_id'       => (int)$req['id'],
            'from_id'   => (int)$req['from_id'],
            'to_id'     => (int)$req['to_id'],
            'status'    => 'pending',
            'created_at'=> (int)($req['create_time'] ?? 0),
        ]);
    }

    public function accept($requestId): void
    {
        $uid = $this->currentUserId();
        $ts = time();
        $rr = BindingRequestModel::where('id', (int)$requestId)->where('to_id', $uid)->where('status', 'pending')->find();
        if (!$rr) {
            $this->success('', ['success' => false, 'message' => '申请不存在或已处理']);
            return;
        }
        BindingRequestModel::where('id', (int)$requestId)->update(['status' => 'accepted', 'update_time' => $ts]);
        $fromId = (int)$rr['from_id'];
        $toId = (int)$rr['to_id'];
        BaseUserModel::where('id', $fromId)->update(['partner_id' => $toId, 'update_time' => $ts]);
        BaseUserModel::where('id', $toId)->update(['partner_id' => $fromId, 'update_time' => $ts]);
        $this->success('', ['success' => true, 'message' => '绑定成功']);
    }

    public function reject($requestId): void
    {
        $uid = $this->currentUserId();
        $ts = time();
        $rr = BindingRequestModel::where('id', (int)$requestId)->where('to_id', $uid)->where('status', 'pending')->find();
        if (!$rr) {
            $this->success('', ['success' => false, 'message' => '申请不存在或已处理']);
            return;
        }
        BindingRequestModel::where('id', (int)$requestId)->update(['status' => 'rejected', 'update_time' => $ts]);
        $this->success('', ['success' => true, 'message' => '已拒绝']);
    }

    public function partnerInfo($partnerId): void
    {
        $u = BaseUserModel::where('id', (int)$partnerId)->find();
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

    public function bind($partnerId): void
    {
        $uid = $this->currentUserId();
        $ts = time();
        BaseUserModel::where('id', $uid)->update(['partner_id' => (int)$partnerId, 'update_time' => $ts]);
        BaseUserModel::where('id', (int)$partnerId)->update(['partner_id' => $uid, 'update_time' => $ts]);
        $this->success('', true);
    }
}

