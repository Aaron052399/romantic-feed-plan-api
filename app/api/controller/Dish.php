<?php

namespace app\api\controller;

use think\facade\Db;
use app\common\controller\Api;
use app\admin\model\Dish as DishModel;
use app\admin\model\dish\Tag as TagModel;
use app\admin\model\dish\TagRelation as TagRelationModel;
use app\admin\model\dish\Category as CategoryModel;
use app\common\library\Upload;
use app\api\validate\Dish as DishValidate;
use app\api\validate\Category as CategoryValidate;
use app\api\validate\Tag as TagValidate;

class Dish extends Api
{
    protected function mapTagToToggles(?string $tag): array
    {
        $t = (string)$tag;
        return [
            'is_favorite_toggle'  => $t === '宝宝最爱' ? 1 : 0,
            'is_new_toggle'       => $t === '新品' ? 1 : 0,
            'is_recommend_toggle' => $t === '今日推荐' ? 1 : 0,
        ];
    }

    protected function tagText(array $row): string
    {
        if (!empty($row['is_favorite_toggle'])) return '宝宝最爱';
        if (!empty($row['is_recommend_toggle'])) return '今日推荐';
        if (!empty($row['is_new_toggle'])) return '新品';
        return '';
    }

    public function list(): void
    {
        $rows = DishModel::where('delete_time', 0)->select()->toArray();
        $items = [];
        foreach ($rows as $x) {
            $items[] = [
                '_id'       => (int)$x['id'],
                'name'      => (string)$x['name'],
                'desc'      => (string)($x['description'] ?? ''),
                'price'     => (float)$x['price'],
                'tag'       => $this->tagText($x),
                'imageUrl'  => (string)($x['cover_image'] ?? ''),
                'eta'       => (int)($x['cook_time'] ?? 0),
                'published' => $x['delete_time'] == 0,
                'catId'     => $x['category_id'] ?? null,
            ];
        }
        $this->success('', $items);
    }

    public function listWithTags(): void
    {
        $rows = DishModel::where('delete_time', 0)->select()->toArray();
        $ids = array_column($rows, 'id');
        $rels = [];
        $tagNames = [];
        if ($ids) {
            $rels = TagRelationModel::whereIn('dish_id', $ids)->select()->toArray();
            $tagIds = array_values(array_unique(array_column($rels, 'tag_id')));
            if ($tagIds) {
                $tags = TagModel::whereIn('id', $tagIds)->select()->toArray();
                foreach ($tags as $t) $tagNames[$t['id']] = (string)$t['name'];
            }
        }
        $dishTagMap = [];
        foreach ($rels as $r) {
            $did = (int)$r['dish_id'];
            $arr = $dishTagMap[$did] ?? [];
            $name = $tagNames[$r['tag_id']] ?? null;
            if ($name) $arr[] = $name;
            $dishTagMap[$did] = $arr;
        }
        $items = [];
        foreach ($rows as $x) {
            $items[] = [
                '_id'       => (int)$x['id'],
                'name'      => (string)$x['name'],
                'desc'      => (string)($x['description'] ?? ''),
                'price'     => (float)$x['price'],
                'tag'       => $this->tagText($x),
                'imageUrl'  => (string)($x['cover_image'] ?? ''),
                'eta'       => (int)($x['cook_time'] ?? 0),
                'published' => $x['delete_time'] == 0,
                'catId'     => $x['category_id'] ?? null,
                'tags'      => $dishTagMap[(int)$x['id']] ?? [],
            ];
        }
        $this->success('', $items);
    }

    public function home(): void
    {
        $limit = (int)$this->request->param('limit', 4);
        $rows = DishModel::where('delete_time', 0)->select()->toArray();
        $items = [];
        foreach ($rows as $x) {
            $items[] = [
                '_id'       => (int)$x['id'],
                'name'      => (string)$x['name'],
                'desc'      => (string)($x['description'] ?? ''),
                'price'     => (float)$x['price'],
                'tag'       => $this->tagText($x),
                'imageUrl'  => (string)($x['cover_image'] ?? ''),
                'eta'       => (int)($x['cook_time'] ?? 0),
                'published' => $x['delete_time'] == 0,
                'catId'     => $x['category_id'] ?? null,
                'tags'      => [],
            ];
        }
        usort($items, function ($a, $b) {
            $score = function ($t) {
                if ($t === '宝宝最爱') return 3;
                if ($t === '今日推荐' || $t === '推荐') return 2;
                if ($t === '新品') return 1;
                return 0;
            };
            return $score($b['tag']) - $score($a['tag']);
        });
        $this->success('', array_slice($items, 0, max(0, $limit)));
    }

    public function detail($id): void
    {
        $id = (int)$id;
        $d = DishModel::where('id', $id)->find();
        if (!$d) $this->success('', null);
        $it = [
            '_id'         => (int)$d['id'],
            'name'        => (string)$d['name'],
            'desc'        => (string)($d['description'] ?? ''),
            'price'       => (float)$d['price'],
            'tag'         => $this->tagText($d->toArray()),
            'imageUrl'    => (string)($d['cover_image'] ?? ''),
            'catId'       => $d['category_id'] ?? null,
            'chefNote'    => '',
            'avgScore'    => (float)($d['avg_score'] ?? 0),
            'ratingCount' => (int)($d['rating_count'] ?? 0),
            'created_by'  => $d['created_by'] ?? null,
        ];
        $rels = TagRelationModel::where('dish_id', $id)->select()->toArray();
        $tagIds = array_values(array_unique(array_column($rels, 'tag_id')));
        $tags = [];
        if ($tagIds) {
            $ts = TagModel::whereIn('id', $tagIds)->select()->toArray();
            foreach ($ts as $t) $tags[] = (string)$t['name'];
        }
        $it['tags'] = $tags;
        $this->success('', $it);
    }

    public function add(): void
    {
        $payload = $this->request->post();
        try {
            (new DishValidate())->scene('add')->check($payload);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        $toggles = $this->mapTagToToggles($payload['tag'] ?? '');
        $m = new DishModel();
        $m->save([
            'name'                  => (string)($payload['name'] ?? ''),
            'price'                 => (float)($payload['price'] ?? 0),
            'description'           => (string)($payload['desc'] ?? ($payload['chefNote'] ?? '')),
            'cover_image'           => (string)($payload['imageUrl'] ?? ''),
            'cook_time'             => (int)($payload['eta'] ?? 0),
            'category_id'           => $payload['catId'] ?? null,
            'is_favorite_toggle'    => $toggles['is_favorite_toggle'],
            'is_new_toggle'         => $toggles['is_new_toggle'],
            'is_recommend_toggle'   => $toggles['is_recommend_toggle'],
        ]);
        $this->success('', ['_id' => (int)$m->id]);
    }

    public function update($id): void
    {
        $payload = $this->request->post();
        try {
            (new DishValidate())->scene('update')->check($payload);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        $toggles = $this->mapTagToToggles($payload['tag'] ?? '');
        $id = (int)$id;
        DishModel::where('id', $id)->update([
            'name'                  => (string)($payload['name'] ?? ''),
            'price'                 => (float)($payload['price'] ?? 0),
            'description'           => (string)($payload['desc'] ?? ($payload['chefNote'] ?? '')),
            'cover_image'           => (string)($payload['imageUrl'] ?? ''),
            'cook_time'             => (int)($payload['eta'] ?? 0),
            'category_id'           => $payload['catId'] ?? null,
            'is_favorite_toggle'    => $toggles['is_favorite_toggle'],
            'is_new_toggle'         => $toggles['is_new_toggle'],
            'is_recommend_toggle'   => $toggles['is_recommend_toggle'],
            'update_time'           => time(),
        ]);
        $this->success();
    }

    public function delete($id): void
    {
        $id = (int)$id;
        DishModel::where('id', $id)->update(['delete_time' => time(), 'update_time' => time()]);
        $this->success();
    }

    public function getTags($id): void
    {
        $id = (int)$id;
        $rels = TagRelationModel::where('dish_id', $id)->select()->toArray();
        $this->success('', $rels);
    }

    public function setTags($id): void
    {
        $id = (int)$id;
        $tagIds = $this->request->post('tagIds');
        TagRelationModel::where('dish_id', $id)->delete();
        if (is_array($tagIds) && count($tagIds)) {
            $rows = [];
            $ts = time();
            foreach ($tagIds as $tid) {
                $rows[] = ['dish_id' => (int)$id, 'tag_id' => (int)$tid, 'create_time' => $ts, 'update_time' => $ts];
            }
            (new TagRelationModel())->saveAll($rows);
        }
        $this->success();
    }

    public function categories(): void
    {
        $list = CategoryModel::order('sort asc')->select()->toArray();
        $this->success('', $list);
    }

    public function addCategory(): void
    {
        $name = (string)$this->request->post('name', '');
        try {
            (new CategoryValidate())->scene('add')->check(['name' => $name]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        $m = new CategoryModel();
        $m->save(['name' => trim($name), 'sort' => 0]);
        $this->success('', ['_id' => (int)$m->id]);
    }

    public function updateCategory($id): void
    {
        $id = (int)$id;
        $name = (string)$this->request->post('name', '');
        try {
            (new CategoryValidate())->scene('update')->check(['name' => $name]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        CategoryModel::where('id', $id)->update(['name' => trim($name), 'update_time' => time()]);
        $this->success();
    }

    public function deleteCategory($id): void
    {
        CategoryModel::where('id', (int)$id)->delete();
        $this->success();
    }

    public function tags(): void
    {
        $list = TagModel::select()->toArray();
        $this->success('', $list);
    }

    public function addTag(): void
    {
        $name = (string)$this->request->post('name', '');
        try {
            (new TagValidate())->scene('add')->check(['name' => $name]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        $m = new TagModel();
        $m->save(['name' => trim($name)]);
        $this->success('', ['_id' => (int)$m->id]);
    }

    public function updateTag($id): void
    {
        $id = (int)$id;
        $name = (string)$this->request->post('name', '');
        try {
            (new TagValidate())->scene('update')->check(['name' => $name]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        TagModel::where('id', $id)->update(['name' => trim($name), 'update_time' => time()]);
        $this->success();
    }

    public function deleteTag($id): void
    {
        TagModel::where('id', (int)$id)->delete();
        $this->success();
    }

    public function uploadImage(): void
    {
        $file = $this->request->file('file');
        $driver = $this->request->param('driver', 'local');
        $topic = 'dish';
        $upload = new Upload();
        $attachment = $upload->setFile($file)->setDriver($driver)->setTopic($topic)->upload(null, 0, 0);
        $this->success('', ['url' => $attachment['url'] ?? '', 'file' => $attachment]);
    }
}
