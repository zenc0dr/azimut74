<?php namespace Zen\History\Api;

use Zen\History\Classes\Helpers\Core;
use Zen\History\Models\Link;
use Zen\History\Models\LinkType;
use Carbon\Carbon;
use Zen\History\Classes\Transformers\FavoriteTransformer;
use Zen\History\Classes\Transformers\SearchTransformer;

class History extends Core
{
    #Показываем всё избранное кроме поиска
    public function getFavoriteItems()
    {
        $visiter_id = $this->input('visiterId');
        if ($visiter_id == null) return;
        $links = [];
        $sourse_links = Link::where([['visiter_id', $visiter_id],['type_id', '!=', 1],['is_delete', false]])
            ->orderBy('updated_at','desc')->get();


        if (!count($sourse_links)) {
            $links['items'] = [];
            return $links;
        }

        foreach ($sourse_links as $link) {
            $links['items'][] = [
                'id' => $link->id,
                'inner_id' => $link->inner_id,
                'type_id' => $link->type->id,
                'type' => $link->type->code,
                'url' => $link->url,
                'title' => $link->title,
                'days' => $link->days,
                'date' =>  Carbon::parse($link->created_at)->format('d.m.y'),
                'other' => json_decode($link->transform_data,256)
            ];
        }
        return $links;
    }

    #Удаляем всё избранное
    public function removeFavAll()
    {
        $visiter_id = $this->input('visiterId');
        $links = [];
        $sourse_links = Link::where([['visiter_id', $visiter_id],['type_id', '!=', 1],['is_delete', false]])
            ->orderBy('updated_at','desc')->get();


        if (!count($sourse_links)) {
            $links['items'] = [];
            return $links;
        }

        foreach ($sourse_links as $link) {
            $link->is_delete = true;
            $link->save();
        }
        return $links['items'] = [];
    }


    public function setFavoriteItems()
    {
        $preset = [];
        $preset['visiter_id'] = $this->input('visiterId');
        $preset['type_code'] = $this->input('typeCode');
        $preset['element_id'] = $this->input('id');
        $preset['url'] = $this->input('url');
        $preset['title'] = $this->input('title');
        $preset['days'] = $this->input('days');
        $preset['other'] = $this->input('other');
        if ($preset['visiter_id'] == null) return;

        $type_id = LinkType::where('code', $preset['type_code'])->lists('id');

        $link_find = Link::where([
            ['visiter_id', $preset['visiter_id']],
            ['inner_id', $preset['element_id']],
            ['type_id', $type_id[0]],
            ['url', $preset['url']]
        ])->first();



        if ($link_find) {
            $is_delete = $link_find->is_delete;
            $link_find->is_delete = !$is_delete;
            $link_find->save();
        } else {
            $fav_trans = new FavoriteTransformer($preset);
            $transform_data = $fav_trans->transformTypeData();
            Link::create([
                'url' => $transform_data['url'],
                'title' => $transform_data['title'],
                'type_id' => $type_id[0],
                'visiter_id' => $preset['visiter_id'],
                'inner_id' => $preset['element_id'],
                'days' => $transform_data['days'],
                'transform_data' => $transform_data['other']
            ]);
        }
    }

    public function setSearchHistoryItems()
    {
        $visiter_id = $this->input('visiterId');
        if ($visiter_id == null) return;
        $preset = $this->input('preset');

        if ($preset) {
            $link_find = Link::where([
                ['visiter_id', $visiter_id],
                ['url', $preset],
                ['type_id', 1]
            ])->first();

            if (!$link_find) {
                $search_trans = new SearchTransformer($preset);

                $transform_data = $search_trans->transformTypeData();
                if ($transform_data) {
                    $days = 'Не указано';
                    if (count($transform_data['days'])) {
                        $days = implode(',', $transform_data['days']);
                    }
                    Link::create([
                        'title' => $transform_data['name'],
                        'transform_data' => json_encode($transform_data, 256),
                        'type_id' => 1,
                        'visiter_id' => $visiter_id,
                        'url' => $preset,
                        'days' => $days
                    ]);
                }
            } else {
                $link_find->is_delete = false;
                $link_find->save();
            }
        }

        return $this->getSearchHistoryItems();
    }


    public function getSearchHistoryItems()
    {
        $visiter_id = $this->input('visiterId');
        if ($visiter_id == null) return;
        $links = [];
        $sourse_links = Link::where([['visiter_id', $visiter_id], ['type_id', 1], ['is_delete', false]])
        ->orderBy('updated_at', 'desc')->get();


        if (!count($sourse_links)) {
            $links['items'] = [];
            return $links;
        }

        foreach ($sourse_links as $link) {
            $links['items'][] = [
                'id' => $link->id,
                'url' => $link->url,
                'title' => $link->title,
                'days' => $link->days,
                'transform_data' => json_decode($link->transform_data,256),
                'date' => Carbon::parse($link->created_at)->format('d.m.y')
            ];
        }
        return $links;
    }

    public function removeSearchHistoryItems()
    {
        $remove_id = $this->input('id');

        $link = Link::find($remove_id);

        $result['status'] = false;
        if ($link) {
            $link->is_delete = true;
            $link->save();
            $result['status'] = true;
        }
        return $result;
    }

    public function removeSearchAll()
    {
        $visiter_id = $this->input('visiterId');
        $links = [];
        $sourse_links = Link::where([['visiter_id', $visiter_id], ['type_id', 1], ['is_delete', false]])
            ->orderBy('updated_at', 'desc')->get();

        if (!count($sourse_links)) {
            $links['items'] = [];
            return $links;
        }

        foreach ($sourse_links as $link) {
            $link->is_delete = true;
            $link->save();
        }
        return $links['items'] = [];
    }





}
