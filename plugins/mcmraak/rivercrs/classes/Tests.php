<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Classes\Parser;
use Mcmraak\Rivercrs\Classes\ParserLog;
use Exception;
use Log;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Waybills as Waybill;
use Mcmraak\Rivercrs\Models\Pricing;
use Mcmraak\Rivercrs\Models\Towns as Town;
use Mcmraak\Rivercrs\Models\Decks as Deck;
use Mcmraak\Rivercrs\Traits\Gama;
use Mcmraak\Rivercrs\Traits\Germes;
use Mcmraak\Rivercrs\Traits\Infoflot;
use Mcmraak\Rivercrs\Traits\Volga;
use Mcmraak\Rivercrs\Traits\Waterway;
use System\Models\File;
use DB;
use Input;
use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\Idmemory as ID;
use Mcmraak\Rivercrs\Models\Log as JLog;
use Mcmraak\Rivercrs\Classes\Ids;
use System\Helpers\Cache;
use Config;
use App;
use Queue;
use View;
use Session;

class Tests
{

    # Тест метода cleanNames для теплоходов
    # http://azimut.dc/rivercrs/debug/Tests@motorshipsTest
    function motorshipsTest()
    {
        $ships = Ship::cleanNames();

        dd(count($ships));
    }

    # Просмотр цен на заезд
    # http://azimut.dc/rivercrs/debug/Tests@testCheckinPrices
    function testCheckinPrices()
    {
        $checkin = Checkin::find(29748);
        dd($checkin->getPrice());
    }

    # Тестирование преобразователя времени для Excel
    # http://azimut.dc/rivercrs/debug/Tests@testExcelTime
    function testExcelTime()
    {
        # unixtime считает от 01.01.1970
        # excel считает от 01.01.1900
        #$a = strtotime('01.01.1970 00:00:00');
        #$b = strtotime('01.01.1900 00:00:00');
        #$c = $a - $b;
        #dd($c); // unixtime больше на 2208985458 секунд

        //$date_a = Carbon::parse('01.01.1900 00:00:00');
        //$date_b = Carbon::parse('01.01.1970 00:00:00');
        //$diff_seconds = $date_b->diffInSeconds($date_a);
        //dd($diff_seconds); // 2208985458
        $ex_time = 43600.979166667; // 15.05.2019 23:30:00
        //$ex_time = 43619.2916666667; // 03.06.2019 07:00:00

        //$unix_time =  * 86400;

        $ex_time = (string) $ex_time;
        $ex_time = explode('.', $ex_time);
        $days = (int) $ex_time[0];
        $days_in_seconds = ($days*86400) - 2208834000;
        $ex_seconds = floatval('0.'.$ex_time[1]) * 86400;
        $days_in_seconds += $ex_seconds+3600;
        dd(date('d.m.Y H:i:s', $days_in_seconds));
        dd($ex_time);
    }

    # Просмотр расписания Волга Волга
    # http://azimut.dc/rivercrs/debug/Tests@testVolgaShelude
    function testVolgaShelude(){
        $checkin = Checkin::find(24672);
        return \Mcmraak\Rivercrs\Controllers\VolgaSettings::getVolgaExcursion($checkin);
    }

    # Тестирование системы логов
    # http://azimut.dc/rivercrs/debug/Tests@testJLOG
    function testJLOG(){
        JLog::addLog([
            'title' => 'Тест лога',
            'method' => 'Service@testJLOG',
            'type' => 'test',
            'url' => 'http://8ber.ru',
            'eds_code' => 'xxx',
            'text' => 'Дополнительная информация'
        ]);
        echo "Запись создана";
    }

    # http://azimut.dc/rivercrs/debug/Tests@readMail
    function readMail()
    {
        $connect = imap_open("{imap.yandex.ru:143/novalidate-cert}", "noreply@xn----7sbveuzmbgd.xn--p1ai", "mbvmhyubrpgqivad");
        //$headers = imap_headers($connect);

        $new_mails = imap_search($connect, 'ALL'); //Получим ID непрочитанных писем
        $new_mails = implode(",", $new_mails); //Соберём все ID в строчку через запятую

        $overview = imap_fetch_overview($connect,$new_mails,0); //Получаем инфу из заголовков сообщений

        foreach ($overview as $ow) { //пробегаем по полученному массиву. Каждый элемент массива - новое письмо
            $subject = iconv_mime_decode($ow->subject, 0, "UTF-8"); //Получаем тему письма и сразу декодируем её
            //потому что скорее всего у темы не будет читаемой человеком кодировки

            //$body = imap_fetchbody($connect,$ow->msgno,1); //Получаем содержимое письма. У него скорее всего уже будет
            //$body = iconv_mime_decode($body, 0, "UTF-8");

            $struct = imap_fetchstructure($connect,$ow->msgno);
            $body = imap_fetchbody($connect,$ow->msgno,1);
            $body = explode('Content-Transfer-Encoding: base64', $body)[1];
            $body = str_replace("\r\n", '', $body);
            $body = base64_decode($body);

            echo $body;

            /*
            if (trim($struct->encoding)=='4')
                $body = imap_qprint($body);
            if (trim($struct->encoding)=='3')
                $body = imap_base64($body);
            $parms = $struct->parameters[0];
            $encoding = $parms->value;
            $body = iconv($encoding,"UTF-8",$body);
            */



            echo "Subject: $subject <br />";
            echo "Body: $body <br /><br />";
        }



        imap_close($connect);

        /*
        dd($headers);
        foreach ($headers as $header) {
            dd($header);
            $subject = iconv_mime_decode($header,0,"UTF-8");
            echo $subject.'<br>';
        }
        */
    }

    # http://azimut.dc/rivercrs/debug/Tests@testCabinPivots
    function testCabinPivots()
    {
        Config::set('database.connections.azimut_backup', array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => 'azimut_backup',
            'username'  => 'zen',
            'password'  => 'zen',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));

        $pivots = DB::connection('azimut_backup')->table('mcmraak_rivercrs_cabins as cabins')
            ->whereNotNull('cabins.infoflot_name')
            ->join(
                'mcmraak_rivercrs_incabin_pivot as pivot',
                'pivot.cabin_id', '=', 'cabins.id'
            )
            ->select('pivot.id')
            ->count();
        dd($pivots);
    }

    # http://azimut.dc/rivercrs/debug/Tests@infoflotIssue1
    function infoflotIssue1()
    {
        //$page = Input::get('page');

        $page = 4;


        $parser = new Parser;
        $ships = $parser->cacheWarmUp('infoflot-ships', 'array', ['page'=> $page, 'limit' => 100], 7, 0, 1, 1);

        if(isset($ships['answer']['error'])) {
            echo $ships['answer']['error'];
        };

        $ships = @$ships['answer']['data'];

        dd(@$ships[65]);


        $i = 0;
        foreach ($ships as $ship) {
            echo "$i - ".$ship['name'].'<br>';
            $i++;
        }
    }

    # http://azimut.dc/rivercrs/debug/Tests@waterwayIssue1
    function waterwayIssue1()
    {
        dd(ID::isExist('waterway', 9495));
    }

    # http://azimut.dc/rivercrs/debug/Tests@testCabins
    function testCabins()
    {
        return;
        $cabin = Cabin::where('infoflot_name', 'А2н')
            ->where('motorship_id', 27)
            ->first();

        $cabin->save();

        echo "Сохранено";

    }

    # http://azimut.dc/rivercrs/debug/Tests@testReviews
    function testReviews()
    {
        echo \Mcmraak\Rivercrs\Classes\InjSettings::getReviews();
    }

    # http://azimut.dc/rivercrs/debug/Tests@testQuizPriz
    function testQuizPriz()
    {
        //dd(Session::get('quize_passed'));
        echo \Mcmraak\Rivercrs\Classes\InjSettings::getQuiz();
    }

}
