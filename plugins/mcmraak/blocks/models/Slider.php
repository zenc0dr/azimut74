<?php namespace Mcmraak\Blocks\Models;

use Model;

/**
 * Model
 */
class Slider extends Model
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
    public $table = 'mcmraak_blocks_slider';

	public $thisId;
	public $slidesDump;

	public function getLinkOptions()
    {
        $cmsPages = \Cms\Classes\Page::listInTheme('azimut-tur', true);
        $toursPages = \Srw\Catalog\Models\Items::get();
		$cruisesPages = \Mcmraak\Rivercrs\Models\Cruises::get();
		$referencePages = \Mcmraak\Rivercrs\Models\Reference::get();
		$motorshipsPages = \Mcmraak\Rivercrs\Models\Motorships::get();
		$temporaryTours = \Zen\Om\Models\Item::get();
		$temporaryTourscaterory = \Zen\Om\Models\Category::get();
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
		foreach ($cruisesPages as $val) {
            //$options[$item->viewBag['url']] = $item->viewBag['title'];

            //$options[$key] = $key;
            $title = $val->attributes['name'];
            $url = '/cruises/'.$val->attributes['slug'];
            $options[$url] = $url." [$title]";
            //dd($toursPages);
        }
		foreach ($referencePages as $val) {
            //$options[$item->viewBag['url']] = $item->viewBag['title'];

            //$options[$key] = $key;
            $title = $val->attributes['name'];
            $url = '/references/'.$val->attributes['slug'];
            $options[$url] = $url." [$title]";
            //dd($toursPages);
        }
		foreach ($motorshipsPages as $val) {
            //$options[$item->viewBag['url']] = $item->viewBag['title'];

            //$options[$key] = $key;
            $title = $val->attributes['name'];
            $url = '/cruises/motorship/'.$val->attributes['id'];
            $options[$url] = $url." [$title]";
            //dd($toursPages);
        }
		foreach ($temporaryTours as $val) {
            //$options[$item->viewBag['url']] = $item->viewBag['title'];

            //$options[$key] = $key;
            $title = $val->attributes['name'];
            $url = $val->attributes['url_cache'];
            $options[$url] = $url." [$title]";
            //dd($toursPages);
        }
		foreach ($temporaryTourscaterory as $val) {
            //$options[$item->viewBag['url']] = $item->viewBag['title'];

            //$options[$key] = $key;
            $title = $val->attributes['name'];
            $url = $val->attributes['url_cache'];
            $options[$url] = $url." [$title]";
            //dd($toursPages);
        }

        return $options;

        //return Category::lists('name', 'id');
    }

	# Get data for repeater items (Slides)
    public function getSlidesAttribute($value)
    {
		return json_decode($value, true);
    }

	public function setSlidesAttribute($value)
    {
		$this->attributes['slides'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }
}