<?php namespace Srw\Core\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Srw\Core\Models\Core as Fixit;
use Request;
use Response;

/**
 * Space Back-end Controller
 */
class Core extends Controller
{
    private static $domain;
    private static $inner;
    private static $outer;

    public static function slug ($object)
    {
        return @Request::segments()[1];
        //return $object->param('slug?');
    }

    public static function jsonToggle ($json)
    {
        $json = preg_replace("/##\d+##/", 'null', $json);
        $json = str_replace('}null]','}]',$json);
        $json = str_replace('[null]','null',$json);
        $json = str_replace('}{','},{',$json);
        $json = str_replace('/','\/',$json);
        $json = "[$json]";
        return json_decode($json);
    }

    public static function fix()
    {

        self::getDomain();
        if(self::test_0()) return; // .local
        self::getInner();
        self::getOuter();

        if(self::$outer !== null){
            if(self::$inner == self::$outer) return;
        }

        $activation = self::osQuery();

        if($activation === 1) die(file_get_contents(__DIR__.'/Space.html'));
        if($activation === 2) die(file_get_contents(__DIR__.'/Space.html'));
        if($activation == self::$inner) {
            Fixit::insert([
                'domain' => self::$domain,
                'key' => $activation
            ]);
            return;
        }
        die(file_get_contents(__DIR__.'/Space.html'));
    }
    private static function getDomain ()
    {
        $domain = Request::getHost();
        if(strpos($domain,"www.")===0){$domain = substr($domain, 4);} // faster
        self::$domain = $domain;
    }

    private static function getInner()
    {
        $inner = hash_hmac("md5", self::$domain, 'hash_hmac');
        $inner = mb_strtoupper($inner, 'UTF-8');
        $inner = substr($inner, -10);
        self::$inner = $inner;
    }
    private static function getOuter()
    {
        $outer = Fixit::where('domain', self::$domain)->value('key');
        if(!$outer){
            self::$outer = null;
        } else {
            self::$outer = $outer;
        }
    }
    private static function test_0 () {
        if(preg_match('/\.local$/', self::$domain)) return true;
    }
    private static function osQuery ()
    {
        $activation = '';
        $domain = self::$domain;
        if( $curl = curl_init() ) {
            curl_setopt($curl, CURLOPT_URL, 'http://october-studio.ru/activation');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, "domain=$domain");
            $activation = curl_exec($curl);

            curl_close($curl);
         }
         return $activation;
    }
}
