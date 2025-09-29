<?php

namespace Plugin\LangPackGenerator\Services;

use Plugin\LangPackGenerator\Libraries\LangPackGenerator;
use Plugin\LangPackGenerator\Services\interface\TranslateInterface;

class PapagoService extends BaseService implements TranslateInterface {


    protected array $lang_match = [
        'kor'     => 'ko',
        'en'     => 'en',
        'ja'     => 'ja',
        'zh_cn'  => 'zh-CN',
        'zh-CHS' => 'zh-CN',
        'zh-CHT' => 'zh-TW',
        'zh_hk'  => 'zh-TW',
        'es'     => 'es',
        'fr'     => 'fr',
        'de'     => 'de',
        'ru'     => 'ru',
        'pt'     => 'pt',
        'it'     => 'it',
        'vi'     => 'vi',
        'th'     => 'th',
        'id'     => 'id',
        'hi'     => 'hi',
        'ar'     => 'ar',
    ];

    /**
     * 单条翻译
     * @param string $from 源语言(国家编码简写)
     * @param string $to   翻译语言(国家编码简写)
     * @param string $text 翻译文本
     * @return string
     */
    public function translate($from, $to, $text): string
    {
        $sf = parent::translate($from, $to, $text);
        if ( $sf !== '__PASS__' ){
            return $sf;
        }
        $data = [
            "locale" => $this->mapCode($from),
            "source" => $this->mapCode($from),
            "target" => $this->mapCode($to),
            "text"   => $text,
        ];
        $response = $this->httpRequest($this->params($data));
        return $this->output($response['translatedText'] ?? $text);
    }

    public function params($data)
    {
        $data['deviceId']    = $this->generateUUID();
        $data['usageAgreed'] = false;
        $data['dict']        = false;
        $data['dictDisplay'] = 30;
        $data['honorific']   = false;
        $data['instant']     = true;
        $data['paging']      = false;
        return $data;
    }

    /**
     * 多条翻译
     * @param string $from  源语言(国家编码简写)
     * @param string $to    翻译语言(国家编码简写)
     * @param string $texts 翻译文本
     * @return array
     */
    public function batchTranslate($from, $to, $texts): array
    {
        $data     = [
            "locale" => $this->mapCode($from),
            "source" => $this->mapCode($from),
            "target" => $this->mapCode($to),
            "text"   => $texts,
        ];
        $response = $this->httpRequest($this->params($data));
        return $response['translatedText'] ?? [];
    }


    /**
     * 获取类配置描述
     * @param $code
     * @return string
     */
    public function getConfig(): array
    {
        return [
            'key'    => 'papago',
            'label'  => 'PapaGo 体验线路',
            'is_new' => 1,
            'status' => 1
        ];
    }

    /**
     * @param string $url
     * @param array  $params
     * @param bool   $post
     * @return string
     */
    function httpRequest($params = [])
    {

        $url    = 'https://papago.naver.com/apis/nsmt/translate';
        $config = $this->generateAuthorization($url);

        $header = [
            'priority: u=1, i',
            'device-type: pc',
            'pragma: no-cache',
            'authorization:' . $config['Authorization'],
            'timestamp: ' . $config['Timestamp'],
            'x-apigw-partnerid: papago',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'content-type: application/x-www-form-urlencoded; charset=UTF-8'
        ];

        // var b = function(a, e) {
        //                 var t = Object(T.a)() , n = (new Date).getTime() + e - A;
        //                 return {
        //                     Authorization: "PPG " + t + ":" + p.a.HmacMD5(t + "\n" + a.split("?")[0] + "\n" + n, "v1.8.8_3ab8f7c2df").toString(p.a.enc.Base64),
        //                     Timestamp: n
        //                 }
        //             }
        // dd($header);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $data = curl_exec($ch);
        if (curl_error($ch)) {
            return null;
        }
        curl_close($ch);

        return json_decode($data, true);
    }

    function generateUUID()
    {
        $uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
        $e    = 0;

        $uuid = preg_replace_callback('/[xy]/', function($matches) use (&$e) {
            $t    = ($e + 16 * mt_rand() / mt_getrandmax()) % 16 | 0;
            $e    = floor($e / 16);
            $char = $matches[0];
            return $char === 'x' ? dechex($t) : dechex(3 & $t | 8);
        },                            $uuid);

        return $uuid;
    }

    function generateAuthorization($url)
    {

        // 假设 T.a() 是一个返回字符串的函数
        $uuid = $this->generateUUID();

        // 获取当前时间戳并加上 e 减去 A
        // 假设 A 是一个全局变量或常量
        $n = self::getMillisecond() - 100;

        // 假设 p_a_HmacMD5 和 p_a_enc_Base64 是相应的 PHP 函数
        $hmac   = hash_hmac('md5', $uuid . "\n" . explode("?", $url)[0] . "\n" . $n, "v1.8.8_3ab8f7c2df", true);
        $base64 = base64_encode($hmac);
        return [
            "Authorization" => "PPG " . $uuid . ":" . $base64,
            "Timestamp"     => $n
        ];
    }

}
