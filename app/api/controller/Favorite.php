<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\favorite\Dish as FavoriteDishModel;
use app\admin\model\Dish as DishModel;

class Favorite extends Api
{
    protected function currentUserId(): int
    {
        $h = (int)$this->request->header('X-Base-User-Id', 0);
        return $h > 0 ? $h : 1;
    }

    public function list(): void
    {
        $uid = $this->currentUserId();
        $favs = FavoriteDishModel::where('user_id', $uid)->select()->toArray();
        if (!$favs) {
            $this->success('', []);
            return;
        }
        $dishIds = array_values(array_unique(array_column($favs, 'dish_id')));
        $dishes = DishModel::whereIn('id', $dishIds)->select()->toArray();
        $map = [];
        foreach ($dishes as $d) $map[(int)$d['id']] = $d;
        $items = [];
        foreach ($favs as $f) {
            $d = $map[(int)$f['dish_id']] ?? [];
            $items[] = [
                '_id'   => (int)($d['id'] ?? 0),
                'name'  => (string)($d['name'] ?? ''),
                'desc'  => (string)($d['description'] ?? ''),
                'price' => (float)($d['price'] ?? 0),
                'qty'   => (int)($f['quantity'] ?? 1),
                'note'  => '',
            ];
        }
        $this->success('', $items);
    }

    public function increment($dishId): void
    {
        $uid = $this->currentUserId();
        $delta = (int)$this->request->param('delta', 1);
        $list = FavoriteDishModel::where('user_id', $uid)->where('dish_id', (int)$dishId)->select()->toArray();
        $qty = 0;
        if ($list) {
            $it = $list[0];
            $qty = (int)($it['quantity'] ?? 0) + ($delta ?: 1);
            FavoriteDishModel::where('id', (int)$it['id'])->update(['quantity' => $qty, 'update_time' => time()]);
        } else {
            $qty = $delta ?: 1;
            $m = new FavoriteDishModel();
            $ts = time();
            $m->save(['user_id' => $uid, 'dish_id' => (int)$dishId, 'quantity' => $qty, 'create_time' => $ts, 'update_time' => $ts]);
        }
        $all = FavoriteDishModel::where('user_id', $uid)->select()->toArray();
        $total = 0;
        foreach ($all as $i) $total += (int)($i['quantity'] ?? 0);
        $this->success('', ['qty' => $qty, 'total' => $total]);
    }

    public function updateQty($dishId): void
    {
        $uid = $this->currentUserId();
        $qty = (int)$this->request->post('qty', 1);
        $list = FavoriteDishModel::where('user_id', $uid)->where('dish_id', (int)$dishId)->select()->toArray();
        if (!$list) {
            $this->success('', 0);
            return;
        }
        $it = $list[0];
        FavoriteDishModel::where('id', (int)$it['id'])->update(['quantity' => $qty, 'update_time' => time()]);
        $this->success('', $qty);
    }

    public function delete($dishId): void
    {
        $uid = $this->currentUserId();
        $list = FavoriteDishModel::where('user_id', $uid)->where('dish_id', (int)$dishId)->select()->toArray();
        if (!$list) {
            $this->success('', false);
            return;
        }
        FavoriteDishModel::where('id', (int)$list[0]['id'])->delete();
        $this->success('', true);
    }
}

