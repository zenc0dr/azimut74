<?php namespace Zen\Dolphin\Models;

use Model;
use View;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Carbon\Carbon;

/**
 * Model
 */
class Log extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'zen_dolphin_log';

    public $rules = [
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    static function add($opts)
    {
        if(!is_array($opts)) {
            $text = $opts;
            $opts = [];
            $opts['text'] = $text;
        }

        $default = [
            'type' => 'info', # Тип: info, error
            'source' => 'system', # Источник
            'text' => '', # Текст ошибки
            'dump' => null, # Дамп сопуствующий
        ];

        $opts = array_merge($default, $opts);

        $dump = ($opts['dump'])?[$opts['dump']]:null;

        if($dump) {
            $dump = json_encode($dump, JSON_UNESCAPED_UNICODE);
            //$dump = gzdeflate($dump, 9); #TODO: Не всегда читается
        }

        $log = new self;
        $log->text = $opts['text'];
        $log->type = $opts['type'];
        $log->source = $opts['source'];
        $log->dump = $dump;
        $log->save();

        return $log->id;
    }

    static function addException($ex, $source='system', $text=null)
    {
        $line = $ex->getLine();
        $message = $ex->getMessage();
        $trace = $ex->getTrace();
        $file = $ex->getFile();
        $error = "Ошибка: $message в файле $file:$line";
        $text = $text ?? $error;
        self::add([
            'type' => 'Exception',
            'source' => $source,
            'text' => $text,
            'dump' => [
                'error' => $error,
                'trace' => $trace
            ]
        ]);
    }

    private function rotateLog()
    {
        self::where('created_at', '<', Carbon::now()->subMonth()->toDateString())->delete();
    }

    function getInfoAttribute()
    {
        $data = [
            'text' => $this->text,
            'type' => $this->type,
            'source' => $this->source,
            'dump' => $this->printDump()
        ];

        return View::make('zen.dolphin::backend.log_info', ['data' => $data])->render();
    }

    function printDump()
    {
        $dump = $this->dump;
        if(!$dump) return;
        #$dump = @gzinflate($dump); #TODO: Не всегда читается
        #if(!$dump) return 'Ошибка чтения дампа';

        $dump = json_decode($dump, 1);
        $dumper = new HtmlDumper;
        $cloner = new VarCloner();
        $cloner->setMaxItems(100000);
        return $dumper->dump($cloner->cloneVar($dump[0]), true);
    }

    function afterCreate()
    {
        $this->rotateLog(); # Очистка устаревших записей
    }
}
