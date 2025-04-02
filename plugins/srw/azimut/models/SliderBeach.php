<?php namespace Srw\Azimut\Models;

use Model;

/**
 * Model
 */
class SliderBeach extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
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
    public $table = 'srw_azimut_sliderbeach';

	public function getLinkOptions()
    {
        $cmsPages = \Cms\Classes\Page::listInTheme('azimut-tur', true);
        $toursPages = \Srw\Catalog\Models\Items::get();
        //dd($cmsPages);

        $options = [];
        $options[null] = 'Нет страницы'; //выводим в dropdown списке элемент нет страницы
        foreach ($cmsPages as $val) {
            //$options[$item->viewBag['url']] = $item->viewBag['title'];

            //$options[$key] = $key;
            $title = $val->settings['title'];
            $url = $val->settings['url'];
            $options[$url] = $url." [$title]";

        }
        foreach ($toursPages as $val) {
            //$options[$item->viewBag['url']] = $item->viewBag['title'];

            //$options[$key] = $key;
            $title = $val->attributes['name'];
            $url = '/tours'.$val->attributes['slug'];
            $options[$url] = $url." [$title]";
            //dd($toursPages);
        }

        return $options;

        //return Category::lists('name', 'id');
    }
}