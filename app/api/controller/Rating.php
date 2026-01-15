<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\dish\Rating as RatingModel;
use app\admin\model\Dish as DishModel;
use app\admin\model\order\Interaction as InteractionModel;
use app\api\validate\Rating as RatingValidate;

class Rating extends Api
{
    protected function currentUserId(): int
    {
        $h = (int)$this->request->header('X-Base-User-Id', 0);
        return $h > 0 ? $h : 1;
    }

    public function list($dishId): void
    {
        $limit = (int)$this->request->param('limit', 20);
        $rows = RatingModel::where('dish_id', (int)$dishId)->order('create_time desc')->limit($limit)->select()->toArray();
        $list = [];
        foreach ($rows as $x) {
            $list[] = [
                'user_id'    => (int)$x['user_id'],
                'score'      => (int)$x['score'],
                'comment'    => (string)($x['comment'] ?? ''),
                'created_at' => (int)($x['create_time'] ?? 0),
            ];
        }
        $this->success('', $list);
    }

    public function submit($dishId): void
    {
        $uid = $this->currentUserId();
        $orderId = (int)$this->request->post('orderId', 0);
        $score = (int)$this->request->post('score', 0);
        $comment = (string)$this->request->post('comment', '');
        try {
            (new RatingValidate())->scene('submit')->check(['orderId' => $orderId, 'score' => $score, 'comment' => $comment]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        $ts = time();
        $existing = RatingModel::where('order_id', $orderId)->where('user_id', $uid)->where('dish_id', (int)$dishId)->find();
        if ($existing) {
            RatingModel::where('id', (int)$existing['id'])->update(['score' => $score, 'comment' => $comment, 'update_time' => $ts]);
        } else {
            $m = new RatingModel();
            $m->save(['order_id' => $orderId, 'user_id' => $uid, 'dish_id' => (int)$dishId, 'score' => $score, 'comment' => $comment, 'create_time' => $ts, 'update_time' => $ts]);
        }
        $d = DishModel::where('id', (int)$dishId)->find();
        if ($d) {
            $cnt = (int)($d['rating_count'] ?? 0);
            $avg = (float)($d['avg_score'] ?? 0);
            $ncnt = $existing ? $cnt : ($cnt + 1);
            $navg = $ncnt ? round((($avg * $cnt + $score) / $ncnt), 1) : 0.0;
            DishModel::where('id', (int)$dishId)->update(['rating_count' => $ncnt, 'avg_score' => $navg, 'update_time' => $ts]);
        }
        $im = new InteractionModel();
        $im->save([
            'order_id'     => $orderId,
            'operator_id'  => $uid,
            'operator_role'=> 1,
            'action_type'  => 6,
            'content'      => $comment,
            'score'        => $score,
            'create_time'  => $ts,
            'update_time'  => $ts,
        ]);
        $this->success('', true);
    }
}
