<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\Message as MessageModel;

class Message extends Api
{
    protected function currentUserId(): int
    {
        $h = (int)$this->request->header('X-Base-User-Id', 0);
        return $h > 0 ? $h : 1;
    }

    public function list($partnerId): void
    {
        $uid = $this->currentUserId();
        $limit = (int)$this->request->param('limit', 50);
        $r1 = MessageModel::where('sender_id', $uid)->where('receiver_id', (int)$partnerId)->select()->toArray();
        $r2 = MessageModel::where('sender_id', (int)$partnerId)->where('receiver_id', $uid)->select()->toArray();
        $list = array_merge($r1, $r2);
        usort($list, fn($a, $b) => ((int)($a['create_time'] ?? 0)) <=> ((int)($b['create_time'] ?? 0)));
        $list = array_slice($list, -$limit);
        $ret = [];
        foreach ($list as $x) {
            $ret[] = [
                'from'       => (int)$x['sender_id'],
                'to'         => (int)$x['receiver_id'],
                'content'    => (string)$x['content'],
                'is_read'    => (int)($x['is_read_toggle'] ?? 0) === 1,
                'created_at' => (int)($x['create_time'] ?? 0),
            ];
        }
        $this->success('', $ret);
    }

    public function send($partnerId): void
    {
        $uid = $this->currentUserId();
        $content = (string)$this->request->post('content', '');
        $ts = time();
        $m = new MessageModel();
        $m->save(['sender_id' => $uid, 'receiver_id' => (int)$partnerId, 'content' => $content, 'is_read_toggle' => 0, 'create_time' => $ts, 'update_time' => $ts]);
        $this->success('', ['_id' => (int)$m->id]);
    }

    public function markRead($partnerId): void
    {
        $uid = $this->currentUserId();
        $rows = MessageModel::where('sender_id', (int)$partnerId)->where('receiver_id', $uid)->where('is_read_toggle', 0)->select()->toArray();
        $ts = time();
        $count = 0;
        foreach ($rows as $m) {
            MessageModel::where('id', (int)$m['id'])->update(['is_read_toggle' => 1, 'update_time' => $ts]);
            $count++;
        }
        $this->success('', $count);
    }
}

