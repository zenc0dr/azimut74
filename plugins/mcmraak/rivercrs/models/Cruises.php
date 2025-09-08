<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use Mcmraak\Rivercrs\Models\Towns;
use Mcmraak\Rivercrs\Models\Motorships;

/**
 * Model
 */
class Cruises extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_cruises';

    /* Relations */

    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    public $hasMany = [
        'transits' => [
            'Mcmraak\Rivercrs\Models\Transit',
            'key' => 'parent_id',
            'order' => 'sort_order',
        ],
    ];


    public function getTransitIdOptions()
    {
        return Transit::orderBy('sort_order')->lists('name', 'id');
    }


    public function getTown1Options()
    {
        return [0 => 'Не выбрано'] + Towns::lists('name', 'id');
    }
    public function getTown2Options()
    {
        return [0 => 'Не выбрано'] + Towns::lists('name', 'id');
    }

    public function getSeoArticlesAttribute($value)
    {
        $data = json_decode($value, true);
        
        // Применяем мутатор к текстовым полям в JSON-данных
        if (is_array($data)) {
            foreach ($data as &$article) {
                if (isset($article['seo_title'])) {
                    $article['seo_title'] = $this->applyMutator($article['seo_title']);
                }
                if (isset($article['seo_text'])) {
                    $article['seo_text'] = $this->applyMutator($article['seo_text']);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Применяет мутатор метатегов к тексту
     */
    protected function applyMutator($text)
    {
        if (empty($text)) {
            return $text;
        }
        
        // Получаем активные коды мутатора
        $codes = \Mcmraak\Blocks\Models\Code::where('active', 1)->get();
        
        // Применяем замены
        foreach ($codes as $code) {
            $text = str_replace("[[{$code->code}]]", $code->replace, $text);
        }
        
        // Удаляем неопознанные метатеги
        $text = preg_replace('/\[\[[a-zа-я0-9 \.,!_]+\]\]/ui', '', $text);
        
        return $text;
    }

    public function setSeoArticlesAttribute($value)
    {
        $this->attributes['seo_articles'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    public function getShipIdOptions()
    {
        return [0 => ' -- '] + Motorships::lists('name', 'id');
    }

}