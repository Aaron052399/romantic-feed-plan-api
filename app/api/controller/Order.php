<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\Order as OrderModel;
use app\admin\model\order\Item as OrderItemModel;
use app\admin\model\feeding\Record as FeedingRecordModel;
use app\admin\model\order\Interaction as InteractionModel;
use app\admin\model\Dish as DishModel;
use app\api\validate\Order as OrderValidate;

class Order extends Api
{
    protected function currentUserId(): int
    {
        $h = (int)$this->request->header('X-Base-User-Id', 0);
        return $h > 0 ? $h : 1;
    }

    protected function statusNumToEnum(int $v): string
    {
        if ($v === 1) return 'pending';
        if ($v === 2) return 'cooking';
        if ($v === 3) return 'completed';
        if ($v === 4) return 'cancelled';
        return 'pending';
    }

    protected function statusEnumToNum(string $s): int
    {
        if ($s === 'pending') return 1;
        if ($s === 'cooking') return 2;
        if ($s === 'completed') return 3;
        if ($s === 'cancelled') return 4;
        return 1;
    }

    public function recent(): void
    {
        $limit = (int)$this->request->param('limit', 2);
        $orders = OrderModel::order('create_time desc')->limit($limit)->select()->toArray();
        $ids = array_column($orders, 'id');
        if (!$ids) {
            $this->success('', []);
            return;
        }
        $items = OrderItemModel::whereIn('order_id', $ids)->select()->toArray();
        $feeds = FeedingRecordModel::whereIn('order_id', $ids)->select()->toArray();
        $itemMap = [];
        foreach ($items as $it) {
            $arr = $itemMap[(int)$it['order_id']] ?? [];
            $arr[] = $it;
            $itemMap[(int)$it['order_id']] = $arr;
        }
        $feedMap = [];
        foreach ($feeds as $f) $feedMap[(int)$f['order_id']] = $f;
        $res = [];
        foreach ($orders as $o) {
            $arr = $itemMap[(int)$o['id']] ?? [];
            $names = array_values(array_filter(array_map(fn($x) => (string)($x['dish_name'] ?? ''), $arr)));
            $comment = (string)($feedMap[(int)$o['id']]['comment'] ?? ($o['note'] ?? ''));
            $res[] = ['names' => $names, 'created_at' => (int)($o['create_time'] ?? 0), 'comment' => $comment];
        }
        $this->success('', $res);
    }

    public function user(): void
    {
        $limit = (int)$this->request->param('limit', 20);
        $uid = $this->currentUserId();
        $orders = OrderModel::where('user_id', $uid)->order('create_time desc')->limit($limit)->select()->toArray();
        $ids = array_column($orders, 'id');
        if (!$ids) {
            $this->success('', []);
            return;
        }
        $items = OrderItemModel::whereIn('order_id', $ids)->select()->toArray();
        $feeds = FeedingRecordModel::whereIn('order_id', $ids)->select()->toArray();
        $itemMap = [];
        foreach ($items as $it) {
            $arr = $itemMap[(int)$it['order_id']] ?? [];
            $arr[] = ['name' => (string)$it['dish_name'], 'qty' => (int)$it['quantity'], 'price' => (float)$it['price'], 'dish_id' => (int)$it['dish_id']];
            $itemMap[(int)$it['order_id']] = $arr;
        }
        $feedMap = [];
        foreach ($feeds as $f) $feedMap[(int)$f['order_id']] = $f;
        $res = [];
        foreach ($orders as $o) {
            $res[] = [
                '_id'        => (int)$o['id'],
                'cook_id'    => (int)$o['cook_id'],
                'status'     => $this->statusEnumToNum((string)$o['status']),
                'created_at' => (int)($o['create_time'] ?? 0),
                'items'      => $itemMap[(int)$o['id']] ?? [],
                'note'       => (string)($feedMap[(int)$o['id']]['comment'] ?? ($o['note'] ?? '')),
            ];
        }
        $this->success('', $res);
    }

    public function cook(): void
    {
        $limit = (int)$this->request->param('limit', 20);
        $cookId = $this->currentUserId();
        $orders = OrderModel::where('cook_id', $cookId)->order('create_time desc')->limit($limit)->select()->toArray();
        $ids = array_column($orders, 'id');
        if (!$ids) {
            $this->success('', []);
            return;
        }
        $items = OrderItemModel::whereIn('order_id', $ids)->select()->toArray();
        $feeds = FeedingRecordModel::whereIn('order_id', $ids)->select()->toArray();
        $itemMap = [];
        foreach ($items as $it) {
            $arr = $itemMap[(int)$it['order_id']] ?? [];
            $arr[] = ['name' => (string)$it['dish_name'], 'qty' => (int)$it['quantity']];
            $itemMap[(int)$it['order_id']] = $arr;
        }
        $feedMap = [];
        foreach ($feeds as $f) $feedMap[(int)$f['order_id']] = $f;
        $res = [];
        foreach ($orders as $o) {
            $res[] = [
                '_id'        => (int)$o['id'],
                'user_id'    => (int)$o['user_id'],
                'status'     => $this->statusEnumToNum((string)$o['status']),
                'created_at' => (int)($o['create_time'] ?? 0),
                'items'      => $itemMap[(int)$o['id']] ?? [],
                'note'       => (string)($feedMap[(int)$o['id']]['comment'] ?? ($o['note'] ?? '')),
            ];
        }
        $this->success('', $res);
    }

    public function detail($orderId): void
    {
        $orderId = (int)$orderId;
        $o = OrderModel::where('id', $orderId)->find();
        if (!$o) {
            $this->success('', null);
            return;
        }
        $items = OrderItemModel::where('order_id', $orderId)->select()->toArray();
        $feed = FeedingRecordModel::where('order_id', $orderId)->find();
        $total = 0.0;
        $retItems = [];
        foreach ($items as $i) {
            $price = (float)$i['price'];
            $qty = (int)$i['quantity'];
            $total += $price * $qty;
            $retItems[] = ['name' => (string)$i['dish_name'], 'price' => $price, 'qty' => $qty];
        }
        $this->success('', [
            '_id'          => (int)$o['id'],
            'order_no'     => (string)$o['order_no'],
            'user_id'      => (int)$o['user_id'],
            'cook_id'      => (int)$o['cook_id'],
            'status'       => $this->statusEnumToNum((string)$o['status']),
            'note'         => (string)($o['note'] ?? ''),
            'created_at'   => (int)($o['create_time'] ?? 0),
            'items'        => $retItems,
            'total_amount' => $total,
            'feed_comment' => $feed ? (string)($feed['comment'] ?? '') : '',
        ]);
    }

    protected function genOrderNo(): string
    {
        $nn = new \DateTimeImmutable();
        $pad = fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT);
        return 'ORDER' . $nn->format('Y') . $pad($nn->format('m')) . $pad($nn->format('d')) . $pad($nn->format('H')) . $pad($nn->format('i')) . $pad($nn->format('s')) . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    public function fromDish(): void
    {
        $dishId = (int)$this->request->post('dishId', 0);
        $qty = (int)$this->request->post('qty', 1);
        $note = (string)$this->request->post('note', '');
        try {
            (new OrderValidate())->scene('fromDish')->check(['dishId' => $dishId, 'qty' => $qty, 'note' => $note]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        $d = DishModel::where('id', $dishId)->find();
        if (!$d) {
            $this->error('菜品不存在');
        }
        $uid = $this->currentUserId();
        $cookId = (int)($d['created_by'] ?? 1);
        $total = ((float)$d['price'] ?? 0) * ($qty ?: 1);
        $ts = time();
        $orderNo = $this->genOrderNo();
        $o = new OrderModel();
        $o->save(['order_no' => $orderNo, 'user_id' => $uid, 'cook_id' => $cookId, 'total_amount' => $total, 'status' => 'pending', 'note' => $note, 'create_time' => $ts, 'update_time' => $ts]);
        $oid = (int)$o->id;
        if (!$oid) {
            $this->error('创建订单失败');
        }
        $it = new OrderItemModel();
        $it->save(['order_id' => $oid, 'dish_id' => (int)$d['id'], 'dish_name' => (string)$d['name'], 'price' => (float)$d['price'], 'quantity' => $qty, 'create_time' => $ts, 'update_time' => $ts]);
        $this->success('', ['order_id' => $oid]);
    }

    public function fromFavorites(): void
    {
        $favoriteItems = $this->request->post('favoriteItems/a', []);
        if (!is_array($favoriteItems) || count($favoriteItems) === 0) {
            $this->error('心选列表为空');
        }
        $dishIds = [];
        foreach ($favoriteItems as $item) {
            if (isset($item['_id'])) $dishIds[] = (int)$item['_id'];
        }
        $dishes = DishModel::whereIn('id', $dishIds)->select()->toArray();
        $dishMap = [];
        foreach ($dishes as $d) $dishMap[(int)$d['id']] = $d;
        $totalAmount = 0.0;
        $firstDish = $dishMap[$dishIds[0]] ?? null;
        $cookId = (int)($firstDish['created_by'] ?? 1);
        $uid = $this->currentUserId();
        $ts = time();
        $orderNo = $this->genOrderNo();
        $orderItems = [];
        foreach ($favoriteItems as $item) {
            $dish = $dishMap[(int)$item['_id']] ?? null;
            if ($dish) {
                $price = (float)$dish['price'];
                $qty = (int)($item['qty'] ?? 1);
                $totalAmount += $price * $qty;
                $orderItems[] = [
                    'dish_id'   => (int)$dish['id'],
                    'dish_name' => (string)$dish['name'],
                    'price'     => $price,
                    'quantity'  => $qty,
                ];
            }
        }
        if (!$orderItems) {
            $this->error('没有有效的菜品');
        }
        $allNotes = [];
        foreach ($favoriteItems as $item) {
            if (!empty($item['note'])) $allNotes[] = (string)$item['note'];
        }
        $o = new OrderModel();
        $o->save(['order_no' => $orderNo, 'user_id' => $uid, 'cook_id' => $cookId, 'total_amount' => $totalAmount, 'status' => 'pending', 'note' => implode('；', $allNotes), 'create_time' => $ts, 'update_time' => $ts]);
        $oid = (int)$o->id;
        if (!$oid) {
            $this->error('创建订单失败');
        }
        $rows = [];
        foreach ($orderItems as $it) {
            $rows[] = [
                'order_id'   => $oid,
                'dish_id'    => (int)$it['dish_id'],
                'dish_name'  => (string)$it['dish_name'],
                'price'      => (float)$it['price'],
                'quantity'   => (int)$it['quantity'],
                'create_time'=> $ts,
                'update_time'=> $ts,
            ];
        }
        (new OrderItemModel())->saveAll($rows);
        $this->success('', ['order_id' => $oid]);
    }

    public function updateStatus($orderId): void
    {
        $orderId = (int)$orderId;
        $statusNum = (int)$this->request->post('status', 1);
        try {
            (new OrderValidate())->scene('updateStatus')->check(['status' => $statusNum]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        $status = $this->statusNumToEnum($statusNum);
        OrderModel::where('id', $orderId)->update(['status' => $status, 'update_time' => time()]);
        $this->success();
    }

    public function interactions($orderId): void
    {
        $orderId = (int)$orderId;
        $rows = InteractionModel::where('order_id', $orderId)->order('create_time asc')->select()->toArray();
        $list = [];
        foreach ($rows as $x) {
            $list[] = [
                'role'       => (int)($x['operator_role'] ?? 1),
                'action'     => (int)($x['action_type'] ?? 0),
                'content'    => (string)($x['content'] ?? ''),
                'score'      => isset($x['score']) ? (int)$x['score'] : null,
                'created_at' => (int)($x['create_time'] ?? 0),
            ];
        }
        $this->success('', $list);
    }

    public function addInteraction($orderId): void
    {
        $orderId = (int)$orderId;
        $operatorRole = (int)$this->request->post('operatorRole', 2);
        $actionType = (int)$this->request->post('actionType', 1);
        $content = (string)$this->request->post('content', '');
        $score = $this->request->post('score', null);
        $uid = $this->currentUserId();
        $ts = time();
        $im = new InteractionModel();
        $im->save([
            'order_id'     => $orderId,
            'operator_id'  => $uid,
            'operator_role'=> $operatorRole,
            'action_type'  => $actionType,
            'content'      => $content,
            'score'        => is_null($score) ? null : (int)$score,
            'create_time'  => $ts,
            'update_time'  => $ts,
        ]);
        $this->success('', ['_id' => (int)$im->id]);
    }
}
