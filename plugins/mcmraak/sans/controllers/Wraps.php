<?php namespace Mcmraak\Sans\Controllers;
use Mcmraak\Sans\Models\WrapType;
use DB;
use Request;
use Log;

class Wraps
{
    public static function getData(){
        $data = Request::all();
        $page_id = $data['page_id'];
        $resort_id = $data['resort_id'];
        $wrap_types = WrapType::orderBy('sort_order')->get();
        $return = [];
        foreach ($wrap_types as $type){

            $selected = DB::table('mcmraak_sans_wraps')
                ->where('page_id',$page_id)
                ->where('type_id', $type->id)
                ->first();

            DB::unprepared("SET sql_mode = ''");
            $options = DB::table('mcmraak_sans_wraps')
                ->where('type_id', $type->id)
                ->where('resort_id', $resort_id)
                ->groupBy('name')
                ->orderBy('name')
                ->get()
                ->pluck('name')
                ->toArray();


            $return[] = [
                'type' => $type->name,
                'selected' => ($selected) ? $selected->name : '',
                'options' => $options
            ];

        }

        return json_encode($return, JSON_UNESCAPED_UNICODE);
        #return print_r($return,1);
    }

    public static function select()
    {
        $data = Request::all();
        $page_id = $data['page_id'];
        $resort_id = $data['resort_id'];
        $name = $data['option'];
        $type_name = $data['type_name'];
        $type_type = WrapType::where('name', $type_name)->first();
        $type_id = $type_type->id;

        $wrap = DB::table('mcmraak_sans_wraps')
            ->where('page_id',$page_id)
            ->where('type_id', $type_id)
            ->where('resort_id', $resort_id)
            ->where('name', $name)
            ->first();
        if($wrap) {
            DB::table('mcmraak_sans_wraps')
                ->where('page_id',$page_id)
                ->where('type_id', $type_id)
                ->where('resort_id', $resort_id)
                ->where('name', $name)
                ->delete();
        } else {
            DB::table('mcmraak_sans_wraps')
                ->where('page_id',$page_id)
                ->where('type_id', $type_id)
                ->where('resort_id', $resort_id)
                ->delete();

            DB::table('mcmraak_sans_wraps')
                ->insert([
                    'page_id' => $page_id,
                    'type_id' => $type_id,
                    'resort_id' => $resort_id,
                    'name' => $name,
                ]);
        }

        # return print_r($data, 1);
    }

    public static function add()
    {
        $data = Request::all();
        $page_id = $data['page_id'];
        $resort_id = $data['resort_id'];
        $name = $data['option'];
        $type_name = $data['type_name'];
        $type_type = WrapType::where('name', $type_name)->first();
        $type_id = $type_type->id;

        $wrap = DB::table('mcmraak_sans_wraps')
            ->where('page_id',$page_id)
            ->where('type_id', $type_id)
            ->where('resort_id', $resort_id)
            ->where('name', $name)
            ->first();
        if($wrap) return;

        DB::table('mcmraak_sans_wraps')
            ->where('page_id',$page_id)
            ->where('type_id', $type_id)
            ->where('resort_id', $resort_id)
            ->delete();


        DB::table('mcmraak_sans_wraps')
            ->insert([
                'page_id' => $page_id,
                'type_id' => $type_id,
                'resort_id' => $resort_id,
                'name' => $name,
            ]);

        # return print_r($data,1);
    }
}