<?php namespace Zen\Uongate\Store;

class Uon extends Store
{
    private string $api_url;
    private string $api_key;

    public function get()
    {
        $api_url = $this->setting('api_url');
        $api_key = $this->setting('api_key');
        if (!$api_url || !$api_key) {
            die;
        }
        $this->api_url = $api_url;
        $this->api_key = $api_key;
        return $this;
    }

    public function query($query, $post = false)
    {
        $query = $this->api_url. '/' .$this->api_key . '/' . $query;

        if (isset($post['u_email']) and !preg_match('/[^@]+@[^@]+\.*+/', $post['u_email'])) {
            unset($post['u_email']);
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $query,
            CURLOPT_POST => $post !== false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));

        if ($post) {
            $this->saveQuery([
                'url' => $query,
                'post' => $post
            ]);
            $post = http_build_query($post);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }

        $response = curl_exec($curl);
        # dd($response); # {"result":200,"id":34144}
        curl_close($curl);

        return json_decode($response, true);
    }

    public function saveQuery($data)
    {
        $json = json_encode($data, 256|128);
        $path = temp_path('uon-queries');
        if (!file_exists($path)) {
            mkdir($path);
        }
        $time = time();
        $file_name = "$path/$time.json";
        file_put_contents($file_name, $json);
    }
}
