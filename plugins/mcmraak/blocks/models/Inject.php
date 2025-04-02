<?php namespace Mcmraak\Blocks\Models;

use Model;
use DB;

/**
 * Model
 */
class Inject extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_blocks_injects';

    public $belongsTo = [
        'template' => [
            'Mcmraak\Blocks\Models\Template',
            'key' => 'template_id'
        ],
    ];

    public function getTemplateIdOptions() {
        return [0 => '- -'] + Template::lists('name', 'id');
    }

    public $scopesDump;
    public function getScopeIdOptions()
    {
        return [0 => '- -'] + Scope::lists('name', 'id');
    }

    public function getScopesAttribute()
    {
        $scopes = DB::table('mcmraak_blocks_injects_scopes')
            ->where('inject_id', $this->id)
            ->orderBy('inject_id')
            ->get();

        if(!$scopes) return;

        $repeater = [];
        foreach($scopes as $record)
        {
            $repeater[] = [
                'scope_id' => $record->scope_id,
                'sequence' => $record->sequence
            ];
        }

        return $repeater;
    }

    public function setScopesAttribute($value)
    {
        $this->scopesDump = $value;
    }

    public function saveScopes()
    {
        $this->deleteThis();

        $insert = [];
        foreach ($this->scopesDump as $record) {
            $insert[] =
                [
                    'inject_id' => $this->id,
                    'scope_id' => $record['scope_id'],
                    'sequence' => $record['sequence']
                ];
        }
        DB::table('mcmraak_blocks_injects_scopes')
            ->insert($insert);
    }

    /* Helpers */
    public function deleteThis(){
        DB::table('mcmraak_blocks_injects_scopes')
            ->where('inject_id', $this->id)
            ->delete();
    }

    /* Events */
    public function afterSave()
    {
        $this->saveScopes();
    }

    public function afterDelete()
    {
        $this->deleteThis();
    }
}
