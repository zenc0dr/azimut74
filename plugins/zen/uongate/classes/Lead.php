<?php namespace Zen\Uongate\Classes;

class Lead extends Core
{
    public static function push($data)
    {
        //$source = self::getSourceName($data['source']);

        $data['Amo.Integration']['source'] = $data['source']; # По проз

        # todo Для отладки
//        file_put_contents(storage_path('leads_push.json'), json_encode([
//            'data' => $data,
//            'Amo.Integration' => $data['Amo.Integration']
//        ], 128 | 256));
//        return;

        $data = [
//            'source' => $source,
//            'u_name' => $data['name'],
//            'u_phone_mobile' => $data['phone'],
//            'u_email' => $data['email'],
//            'note' => $data['note'],
            'Amo.Integration' => $data['Amo.Integration']
        ];

        //file_put_contents(storage_path('lead.json'), json_encode([], 128 | 256));
        self::stream($data);
    }

    private static function stream($data)
    {
        #TODO(zenc0rd):DEBUG
//        $time = now()->format('d.m.Y H:i:s');
//        file_put_contents(
//            storage_path('logs/amo-queries.log'),
//            "$time: Отправлены данные в поток" . PHP_EOL,
//            FILE_APPEND
//        );

        master()->log(
            'Данные с формы для AMO',
            $data
        );

//        $name = now()->format('dmYHis');
//        file_put_contents(
//            storage_path("temp/uon-queries/$name.json"),
//            json_encode($data, 128 | 256)
//        );

        $data = self::json($data, true);
        $key = md5($data);
        $path = temp_path('uongate/leads');

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $path = "$path/$key";
        file_put_contents($path, $data);
        self::artisanExec("uongate:lead_push --key=$key");
    }

    public static function query($data)
    {

        $amo_integration = $data['Amo.Integration'];
        unset($data['Amo.Integration']);

        #self::store('Uon')->get()->query('lead/create.json', $data);

        #TODO(zenc0rd):DEBUG
//        $time = now()->format('d.m.Y H:i:s');
//        file_put_contents(
//            storage_path('logs/amo_queries.log'),
//            "$time: Отправлены данные в АМО" . PHP_EOL,
//            FILE_APPEND
//        );

        master()->log(
            'Данные отправленные в AMO',
            $amo_integration
        );

        # Дополнительно посылаем данные в AMO
        \Http::post('https://tglk.ru/in/4PVwZs6rrSd6QRB5', function ($http) use ($amo_integration) {
            $http->data($amo_integration);
        });
    }

    private static function getSourceName($source_code)
    {
        $db = file_get_contents(base_path('plugins/zen/uongate/db/sources.json'));
        $db = self::fromJson($db);
        foreach ($db as $source) {
            if ($source['code'] === $source_code) {
                return $source['name'];
            }
        }
    }
}
