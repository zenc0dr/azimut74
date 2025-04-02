<?php namespace Zen\Putpics\Api;

class Parser
{
    # http://azimut.dc/zen/putpics/api/parser:test
    public function test()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://hotels4.p.rapidapi.com/properties/get-hotel-photos?id=1178275040",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: hotels4.p.rapidapi.com",
                "x-rapidapi-key: 4a5876e87fmsh7c6dbbc72b359cap1ba794jsn6944f7ad90c9"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            dd(json_decode($response, true));
        }
    }
}

