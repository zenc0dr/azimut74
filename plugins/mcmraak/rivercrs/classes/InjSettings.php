<?php namespace Mcmraak\Rivercrs\Classes;

use October\Rain\Database\Model;
use View;
use Session;

class InjSettings extends Model
{
    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'mcmraak_rivercrs_injsettings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
    ];

    public static function getQuiz()
    {
        $button = null;

        if(Session::get('quize_passed')) {
            $button = View::make('mcmraak.rivercrs::inblocks.quiz_prize')->render();
        } else {
            $button = self::get('quiz_button_code');
        }

        $data = [
            'video' => self::get('quiz_video'),
            'button' => $button,
            'desc' => self::get('quiz_desc'),
        ];

        return View::make('mcmraak.rivercrs::inblocks.quiz', $data)->render();
    }

    public static function getReviews($json = false)
    {

        $data = [
            'title' => self::get('reviews_title'),
            'subtitle' => self::get('reviews_subtitle'),
            'reviews' => self::get('reviews'),
        ];

        if ($json) {
            $data['block_type'] = 'reviews';
            return json_encode($data, 256);
        }

        //dd($data);

        return View::make('mcmraak.rivercrs::inblocks.reviews', $data)->render();
    }

    public static function getClients()
    {
        $data = [
            'title' => self::get('title'),
            'subtitle' => self::get('subtitle'),
            'button' => self::get('button'),
            'blocks' => self::get('blocks'),
        ];
        return View::make('mcmraak.rivercrs::inblocks.clients', $data)->render();
    }
}
