<?php namespace Mcmraak\Rivercrs\Classes;

class Ids
{
    public $db = [], $db_path, $time, $id;

    function __construct($db_name, $time=false)
    {
        $this->db_path = base_path('storage/rivercrs_cache/'.$db_name.'.ids');
        if(!file_exists($this->db_path)) return;
        $this->db = unserialize(file_get_contents($this->db_path));
        $this->time = ($time)?$time*60:false;
    }

    function save()
    {
        file_put_contents($this->db_path, serialize($this->db));
    }

    function clean()
    {
        unlink($this->db_path);
    }

    function add($id, $update=false, $time_mark=true, $error=false)
    {
        if(!$update && isset($this->db[$id])) return;
        if($time_mark) {
            $this->db[$id]['t'] = time();
        } else {
            $this->db[$id] = [];
        }

        if($error) $this->db[$id]['e'] = 1;

        $this->save();
    }

    function test($id)
    {
        if(isset($this->db[$id])) {
            if($this->time) {
                if(isset($this->db[$id]['t'])) {
                    $diff = time() - $this->db[$id]['t'];
                    if($diff < $this->time) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
        return false;
    }


    function testAndCreate($id, $data=null) {

        $this->id = $id;

        $db_change = false;
        $id_exist = false;

        if(isset($this->db[$id])) {
            if($this->time) {
                if(isset($this->db[$id]['t'])) {
                    $diff = time() - $this->db[$id]['t'];
                    if($diff < $this->time) {
                        $id_exist = true;
                    } else {
                        $db_change = true;
                    }
                } else {
                    $db_change = true;
                }
            } else {
                $id_exist = true;
            }
        } else {
            $db_change = true;
            $this->db[$id] = [];
        }

        if($db_change) {
            $this->db[$id]['t'] = time();
            if($data) {
                $this->db[$id]['data'] = $data;
            }
            $this->save();
        }

        return $id_exist;
    }

    function first($id)
    {
        return (object) $this->db[$id];
    }

    function updateData($id, $data)
    {
        $this->db[$id]['data'] = $data;
        $this->save();
    }

    function addIds($prefix, $ids, $time_mark = true)
    {
        foreach ($ids as $id) {
            if($time_mark) {
                $this->db[$prefix.$id]['t'] = time();
            } else {
                $this->db[$prefix.$id] = [];
            }
        }
        $this->save();
    }

    function addError($id = null) {
        $id = ($id)?:$this->id;
        $this->db[$id]['e'] = 1;
        $this->save();
    }

    function ifError($id)
    {
        if(@$this->db[$id]['e']) return true;
        return false;
    }

    function isValid($id)
    {
        if(isset($this->db[$id]) && !@$this->db[$id]['e']) return true;
    }

    function liveIds($ids, $prefix='')
    {
        $return = [];
        foreach ($ids as $id) {
            if(!isset($this->db[$prefix.$id])) {
                $return[] = $id;
            }
        }
        return $return;
    }

    function validIds($ids, $prefix='')
    {
        $return = [];
        foreach ($ids as $id) {
            if(isset($this->db[$prefix.$id]) && !@$this->db[$prefix.$id]['e']) {
                $return[] = $id;
            }
        }
        return $return;
    }

    function get($prefix, $ignore_errors=false)
    {
        $return = [];
        foreach ($this->db as $id => $val) {
            if(strpos($id, $prefix)!==false && ($ignore_errors || !isset($val['e']))) {
                $return[] = str_replace($prefix, '', $id);
            }
        }
        return $return;
    }

    function like($key_entry=null, $ignore_errors=true)
    {
        $return = [];
        foreach ($this->db as $id => $val) {
            if((!$key_entry || strpos($id, $key_entry)!==false) && ($ignore_errors || !isset($val['e']))) {
                $return[$id] = $val;
            }
        }
        return $return;
    }

    function likeErrors($key_entry=null)
    {
        $return = [];
        foreach ($this->db as $id => $val) {
            if((!$key_entry || strpos($id, $key_entry)!==false) && isset($val['e'])) {
                $return[$id] = $val;
            }
        }
        return $return;
    }

    function delete($id)
    {
        unset($this->db[$id]);
        $this->save();
    }

    function deleteLike($key_entry, $preview=false)
    {
        $previewArr = [];
        foreach ($this->db as $id => $val) {
            if(strpos($id, $key_entry)!==false) {
                if($preview) {
                    $previewArr[$id] = $this->db[$id];
                } else {
                    unset($this->db[$id]);
                }

            }
        }
        if($preview) dd($previewArr);

        $this->save();
    }

    function stats()
    {
        $return = [];
        foreach ($this->db as $key => $val) {
            //$item = explode();
            $idName = explode(':', $key)[0];
            if(!isset($return[$idName])) {
                $return[$idName] = 0;
            }
            $return[$idName]++;
        }
        return $return;
    }
}