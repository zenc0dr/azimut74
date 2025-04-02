<?php namespace Zen\Dolphin\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use Zen\Dolphin\Traits\MultiGenerator;
use Twig;

/**
 * Model
 */
class Block extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;
    use MultiGenerator;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_blocks';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];


    public $belongsToMany = [
        'folders' => [
            'Zen\Dolphin\Models\Folder',
            'table'    => 'zen_dolphin_blocks_folders_pivot',
            'key'      => 'block_id',
            'otherKey' => 'folder_id',
        ],
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }


    # CODE
    public $codeDump;

    function getCodeAttribute()
    {
        $path = plugins_path("zen/dolphin/infoblocks/code_{$this->id}.php");
        return @file_get_contents($path);
    }
    function setCodeAttribute($value)
    {
        $this->codeDump = $value;
    }

    function saveCode()
    {
        if(!$this->codeDump) return;
        $path = plugins_path("zen/dolphin/infoblocks/code_{$this->id}.php");
        file_put_contents($path, $this->codeDump);
    }




    public $twigDump;

    function getTwigAttribute()
    {
        $path = plugins_path("zen/dolphin/infoblocks/twig_{$this->id}.htm");
        return @file_get_contents($path);
    }

    function setTwigAttribute($value)
    {
       $this->twigDump = $value;
    }

    function saveTwig()
    {
        if(!$this->twigDump) return;
        $path = plugins_path("zen/dolphin/infoblocks/twig_{$this->id}.htm");
        file_put_contents($path, $this->twigDump);
    }

    function render($input)
    {
        $template = []; # Тут $input преобразуется в $template
        eval($this->code);
        if(!$template) return;
        $output = Twig::parse($this->twig, $template);
        if(!$output) return;
        return "<div infoblock-id='{$this->id}'>$output</div>";
    }



    /* getBlocksFoldersOptions() */

    public
        $this_key = 'block_id',
        $FolderDump;

    function getFoldersMultiOptions()
    {
        return $this->optionsMultiGenerator(Folder::class);
    }

    function getFoldersMultiAttribute()
    {
        return $this->getMultiGenerator('zen_dolphin_blocks_folders_pivot', 'folder_id');
    }

    function setFoldersMultiAttribute($value)
    {
        $this->setMultiGenerator('Folder', $value);
    }

    function afterSave()
    {
        $generator_options = [
            [
                'model' => 'Folder',
                'pivot' => 'zen_dolphin_blocks_folders_pivot',
                'key' => 'folder_id'
            ],
        ];

        $this->saveMultiGenerator($generator_options, 'block_id');
        $this->saveCode();
        $this->saveTwig();
    }

}
