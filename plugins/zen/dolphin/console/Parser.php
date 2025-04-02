<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Http;
use Zen\Cabox\Classes\Cabox;
use Zen\Dolphin\Models\Hotel;

class Parser extends Command
{
    protected $name = 'dolphin:parser';
    protected $description = 'Задачи по парсингу';
    public
        $cabox,
        $proxy_list,
        $last_proxy = '14.207.177.105:8080'
    ;

    public function handle()
    {
        $this->parseHotelsFromDolphin();
    }

    public $hotels_count_all = 0, $hotels_count_added = 0;
    function parseHotelsFromDolphin()
    {
        $this->output->writeln('Начинаю парсинг');

        $this->cabox = new Cabox('dolphin.service');

        $this->proxy_list = file(storage_path('proxylist.txt'));
        $this->cleanProxyList();
        $this->getLastProxy();

        $csv_file = file(storage_path('dolphin-hotels.csv'));
        $this->hotels_count_all = count($csv_file);


        foreach ($csv_file as $line) {
            $this->addHotel($line);
        }
    }

    function getLastProxy()
    {
        $last_proxy = trim(file_get_contents(storage_path('last_proxy')));
        $i=0;
        foreach ($this->proxy_list as $line) {
            if($line == $last_proxy) {
                $this->proxy_index = $i;
            }
            $i++;
        }
    }

    function cleanProxyList()
    {
        $clean_list = [];
        foreach ($this->proxy_list as $line) {
            preg_match('/\d{2,}\.\d+\.\d+\.\d+:\d+/', $line, $m);
            if(@$m[0]) {
                $clean_list[] = trim($m[0]);
            }
        }
        $this->proxy_list = $clean_list;
    }

    function isExclude($hotel_id)
    {
        if(in_array($hotel_id, $this->exclude_hotels_isd)) return true;
    }

    function isHotelExists($hotel_id)
    {
        $hotel = Hotel::where('eid', $hotel_id)->first();
        if($hotel) return true;
    }

    public $hotel_id;
    function addHotel($line)
    {
        $line = trim($line);
        $line = explode("\t", $line);
        $this->hotel_id = trim($line[0]);

        if($this->isHotelExists($this->hotel_id)) return;

        $in_cabox = $this->cabox->get("hotel_id:{$this->hotel_id}");
        if($in_cabox) return;

        $url = $line[1];
        $lon = @$line[2]; # Долгота N
        $lat = @$line[3]; # Широта E

        //$url = 'https://xn--e1angi.xn----7sbveuzmbgd.xn--p1ai/hotels?samo_action=hotel&HOTELINC='.$hotel_id;

        //$html_string = $this->cabox->get("hotel_id:$hotel_id");

        /*

        if(!$html_string) {

            $html_page = Http::get($url);
            $html_page = iconv('windows-1251', 'UTF-8', $html_page);
            $html_string = str_replace("\n", '', $html_page);

            if(strpos($html_string, 'id="recaptcha"') !== false)  {
                $continue = $this->output->ask("Сттаница #$hotel_id Заблокирована, повторить?", 'n');
                $html_page = Http::get($url);
                $html_page = iconv('windows-1251', 'UTF-8', $html_page);
                $html_string = str_replace("\n", '', $html_page);
                if(strpos($html_string, 'id="recaptcha"') !== false)  {
                    die('Всё равно заблокирована');
                }
            }

            $this->cabox->put("hotel_id:$hotel_id", $html_string);

            $start_name = $this->contentParser('<div class="starname" style="color:#222;">', '</div>', $html_string);

            if(!$start_name) {

                $url_cabox = "http://azimut.dc/zen/dolphin/api/Debug@viewHotelPage?id=$hotel_id";

                $this->output->writeln("Страница не читается: $url");
                $this->output->writeln("Страница в кеше: $url_cabox");


                $to_exclude = $this->output->ask("Добавить id $hotel_id в исключения?", 'n');

                if($to_exclude == 'y') {
                    file_put_contents(storage_path('dolphin-hotels.csv'), $hotel_id, FILE_APPEND);
                    $this->cabox->del("hotel_id:$hotel_id");
                    return;
                } else {
                    return;
                }
            }

        }

        $start_name = $this->contentParser('<div class="starname" style="color:#222;">', '</div>', $html_string);

        echo "start_name = $start_name\n";

        */

        $this->parsePage();



    }

    /**
     * @param $begin - Вхождение до строки
     * @param $end - Вхождение после строки
     * @return string
     */
    function contentParser($begin, $end, $html_string)
    {
        $a = explode($begin, $html_string);
        if(!isset($a[1])) return false;
        $b = explode($end, $a[1]);
        if(!isset($b[0])) return false;
        return trim($b[0]);
    }

    public $proxy_index = 0;
    function parsePage()
    {
        $this->url = 'https://xn--e1angi.xn----7sbveuzmbgd.xn--p1ai/hotels?samo_action=hotel&HOTELINC='.$this->hotel_id;
        $html = $this->curlQuery();

        if(!$html) {
            $this->proxy_index++;
            $this->parsePage();
        } else {
            if(strpos($html, '<title>Каталог гостиниц</title>') === false) {
                echo $this->url ." данные не валидны\n";
                $this->proxy_index++;
                $this->parsePage();
                return;
            }

            if(strpos($html, 'id="recaptcha"') !== false) {
                echo $this->url ." страница заблокирована\n";
                $this->proxy_index++;
                $this->parsePage();
                return;
            }

            # Тут всё ок
            $this->cabox->put("hotel_id:{$this->hotel_id}", $html);
            $this->hotels_count_added++;
            echo $this->url ." страница добавлена [{$this->hotels_count_added} из {$this->hotels_count_all}]\n";
        }
    }

    function curlQuery()
    {
        if(!isset($this->proxy_list[$this->proxy_index])) {
            echo "Прокси закончились\n";
            die;
        };


        $proxy = $original_proxy = trim($this->proxy_list[$this->proxy_index]);

        file_put_contents(storage_path('last_proxy'), $proxy);

        $proxy = explode(':', $proxy);
        $url = $this->url;

        echo "Попытка спарсить $url - $original_proxy\n ";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PROXY, $proxy[0]);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy[1]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $HTML = curl_exec($ch); curl_close($ch);
        $HTML = iconv('windows-1251', 'UTF-8', $HTML);
        $HTML = str_replace("\n", '', $HTML);
        return (string) $HTML;
    }


}
