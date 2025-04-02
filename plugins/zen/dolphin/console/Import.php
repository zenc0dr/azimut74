<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use Zen\Dolphin\Models\Pricetype;
use Zen\Dolphin\Models\Tarrif;
use DB;
use Zen\Dolphin\Classes\Core;

class Import extends Command
{

    protected $name = 'dolphin:import';

    protected $description = 'Импорт данных из файла CSV';

    protected $core;

    /*
    $date = ImportXLS::timeFormat(44018, 'd.m.Y');
    $this->output->writeln('Hello world!');
    */

    public $empty_tarrif = [
        'tour_id' => null,
        'operator_id' => null,
        'name' => null,
        'hotel_id' => null,
        'azcomfort_id' => null,
        'number_name' => null,
        'azpansion_id' => null,
    ];

    public $tarrif;

    public function handle()
    {
        $this->core = new Core;
        $this->loadSeviceLists();

        ####### DEBUG
        # DB::table('zen_dolphin_tarrifs')->truncate();
        # DB::table('zen_dolphin_prices')->truncate();
        # $this->output->writeln('Таблицы очищены');
        #############

        $rows = file(storage_path('app/dolphin_import.csv'));

        foreach ($rows as $row) {
            $this->rowAnalysis($row);
        }

        $this->addTariff();
    }

    public $pricetypes = [];

    public function loadSeviceLists()
    {
        $ptypes = Pricetype::get();
        foreach ($ptypes as $ptype) {
            $this->pricetypes[$ptype->code] = $ptype->id;
        }
    }

    private function debugMsg($msg)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        echo "debug: $msg\n";
    }


    public $prices_record = false;
    public array $prices = [];

    public function rowAnalysis($row)
    {
        $row = explode(';', trim($row));

        if (count($row) < 2) {
            return;
        }

        $essenсe_name = $row[0];

        if (!$essenсe_name) {
            return;
        }

        if ($essenсe_name === 'Тур') {
            $this->addTariff(); // Добавляет новый тариф, если $this->tarrif существует
            $this->prices_record = false;
            $this->tarrif = $this->empty_tarrif;
            $this->tarrif['tour_id'] = $row[1];
        }

        if ($row[1] == 'Тип цены') {
            $this->prices = [];
            $this->prices_record = true;
        }

        if ($this->prices_record) {
            $this->prices[] = $row;
        } else {
            $this->addProp($essenсe_name, $row[1]);
        }
    }

    public $names = [
        'Туроператор' => 'operator_id',
        'Название тарифа' => 'name',
        'Гостиница' => 'hotel_id',
        'Комфортность номера' => 'azcomfort_id',
        'Название номера' => 'number_name',
        'Тип питания' => 'azpansion_id'
    ];

    public function addProp($essenсe_name, $data)
    {
        if (!isset($this->names[$essenсe_name])) {
            return;
        }
        $this->tarrif[$this->names[$essenсe_name]] = $data;
    }

    # Делает цифровые значения целочисленными, очищая мусор
    public function prepareTariff(&$arr)
    {
        if (!$arr) {
            return;
        }
        foreach ($arr as $key => &$cell) {
            if ($key != 'name' && $key != 'number_name') {
                $cell = intval(preg_replace('/\D/', '', $cell));
            }
        }
    }

    public function addTariff()
    {
        if (!$this->tarrif) {
            return;
        }

        $this->prepareTariff($this->tarrif);

        # Скорее всего нужно будет добавить тут тариф и сделать save()
        # а уж потом добавлять цены
        # нужен будет tour_id и tariff_id

        $tariff = Tarrif::where('name', $this->tarrif['name'])
            ->where('tour_id', $this->tarrif['tour_id'])
            ->where('hotel_id', $this->tarrif['hotel_id'])
            ->where('azcomfort_id', $this->tarrif['azcomfort_id'])
            ->where('azpansion_id', $this->tarrif['azpansion_id'])
            ->first();

        if (!$tariff) {
            $tariff = new Tarrif;
        }
        $tariff->tour_id = $this->tarrif['tour_id'];
        $tariff->operator_id = $this->tarrif['operator_id'];
        $tariff->name = $this->tarrif['name'];
        $tariff->hotel_id = $this->tarrif['hotel_id'];
        $tariff->azcomfort_id = $this->tarrif['azcomfort_id'];
        $tariff->number_name = $this->tarrif['number_name'];
        $tariff->azpansion_id = $this->tarrif['azpansion_id'];
        $tariff->active = 1;
        $tariff->import = true; #!!! << Если стоит данный флаг, цены удалятся и не будут обрабатываться
        $tariff->save();


        $dates = $this->prices[0]; # Строка с датами
        $dates_count = count($dates); # Ячеек с датами
        $count_rows = count($this->prices); # Строк таблицы


        $insert = [];

        for ($line_i = 1; $line_i < $count_rows; $line_i++) {
            $price_line = $this->prices[$line_i]; # Строка с ценами (кроме первых двух)
            $azroom_name = $price_line[0]; # 2 местный
            $azroom_id = intval(preg_replace('/\D/', '', $azroom_name));

            $pricetype_code = $price_line[1]; # Взр
            $ptype = $this->PTParse($pricetype_code);

            # Перебираем все цены в строке с ценами (Начиная с третьей)
            for ($price_i = 2; $price_i < $dates_count; $price_i++) {
                if (!$dates[$price_i]) {
                    continue;
                }
                $insert[] = [
                    'date' => $this->core->dateToMysql($dates[$price_i]),
                    'tour_id' => $this->tarrif['tour_id'],
                    'tarrif_id' => $tariff->id,
                    'azroom_id' => $azroom_id,
                    'pricetype_id' => $ptype['pricetype_id'],
                    'age_min' => $ptype['age_min'],
                    'age_max' => $ptype['age_max'],
                    'price' => intval(trim($price_line[$price_i]))
                ];
            }
        }

        DB::table('zen_dolphin_prices')->insert($insert);

        //echo "Добавлен тариф id:{$tariff->id} - {$tariff->name}\n";
        //$this->output->writeln("Добавлен тариф id:{$tariff->id} - {$tariff->name}");

        $this->tarrif = null;
    }

    public function PTParse($pricetype_code)
    {
        $pricetype_code = trim(mb_strtolower($pricetype_code));
        preg_match('/\((\d+-\d+)\)/', $pricetype_code, $m);

        $age_min = null;
        $age_max = null;

        if ($m) {
            $pricetype_code = trim(str_replace($m[0], '', $pricetype_code));
            $ages = explode('-', $m[1]);
            $age_min = $ages[0];
            $age_max = $ages[1];
        }

        $pricetype_id = $this->pricetypes[$pricetype_code];

        $return = [
            'pricetype_id' => $pricetype_id,
            'age_min' => $age_min,
            'age_max' => $age_max,
        ];

        return $return;
    }
}
