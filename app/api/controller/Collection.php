<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\dish\Collection as DishCollectionModel;
use app\admin\model\Dish as DishModel;

class Collection extends Api
{
    protected function currentUserId(): int
    {
        $h = (int)$this->request->header('X-Base-User-Id', 0);
        return $h > 0 ? $h : 1;
    }

    public function list(): void
    {
        $uid = $this->currentUserId();
        $rows = DishCollectionModel::where('user_id', $uid)->select()->toArray();
        if (!$rows) {
            $this->success('', []);
            return;
        }
        $ids = array_values(array_unique(array_column($rows, 'dish_id')));
        $dishes = DishModel::whereIn('id', $ids)->select()->toArray();
        $map = [];
        foreach ($dishes as $d) $map[(int)$d['id']] = $d;
        $items = [];
        foreach ($rows as $x) {
            $d = $map[(int)$x['dish_id']] ?? [];
            $items[] = [
                '_id'      => (int)($d['id'] ?? 0),
                'name'     => (string)($d['name'] ?? ''),
                'imageUrl' => (string)($d['cover_image'] ?? ''),
            ];
        }
        $this->success('', $items);
    }

    public function toggle($dishId): void
    {
        $uid = $this->currentUserId();
        $on = (bool)$this->request->post('on', true);
        $list = DishCollectionModel::where('user_id', $uid)->where('dish_id', (int)$dishId)->select()->toArray();
        if ($on) {
            if ($list) {
                $this->success('', true);
                return;
            }
            $m = new DishCollectionModel();
            $ts = time();
            $m->save(['user_id' => $uid, 'dish_id' => (int)$dishId, 'create_time' => $ts, 'update_time' => $ts]);
            $this->success('', true);
        } else {
            if (!$list) {
                $this->success('', true);
                return;
            }
            DishCollectionModel::where('id', (int)$list[0]['id'])->delete();
            $this->success('', true);
        }
    }
}

