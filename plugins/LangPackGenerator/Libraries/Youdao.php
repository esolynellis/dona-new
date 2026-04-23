<?php
/**
 * Youdao.php
 *
 * @copyright  2023 HL-MALL.com - All Rights Reserved
 * @link       https://HL-MALL.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-09-04 15:31:23
 * @modified   2023-09-04 15:31:23
 */

namespace Plugin\LangPackGenerator\Libraries;

class Youdao
{
    // 文本翻译
    public const API_URL = 'https://openapi.youdao.com/api';

    // 富文本翻译
    public const API_HTML_URL = 'https://openapi.youdao.com/translate_html';

    public const CURL_TIMEOUT = 2000;

    /**
     * @var string 应用ID
     */
    private string $appKey = '';

    /**
     * @var string 应用密钥
     */
    private string $appSecret = '';

    private string $baseUrl = '';

    private bool $isHtml;

    /**
     * @param string $appKey
     * @param string $appSecret
     * @param bool $html
     * @throws \Exception
     */
    public function __construct(string $appKey = '', string $appSecret = '', bool $html = false)
    {

        $this->appKey    = $appKey;
        $this->appSecret = $appSecret;
        if ($html) {
            $this->baseUrl = self::API_HTML_URL;
        } else {
            $this->baseUrl = self::API_URL;
        }
        $this->isHtml = $html;
    }

    /**
     * @param $text
     * @param $from
     * @param $to
     * @return string
     * @throws \Exception
     */
    public function translate($text, $from, $to): string
    {
        if (empty($text) || !is_string($text)) {
            return '';
        }
        $response = $this->translateSingle($text, $from, $to);

        if ($this->isHtml) {
            $result = $response['data']['translation'] ?? '';
            $result = html_entity_decode($result);
        } else {
            $result = $response['translation'][0] ?? '';
        }

        $errorCode = $response['errorCode'] ?? '';
        if ($errorCode) {
            $codeMessage  = $this->getCodeMessage($errorCode);
            $errorMessage = "有道翻译错误:{$errorCode}:{$codeMessage}, 请查看: https://ai.youdao.com/DOCSIRMA/html/trans/api/wbfy/index.html#section-14";
            throw new \Exception($errorMessage);
        }

        return $result ?: $text;
    }

    public function getCodeMessage($code)
    {
        $codes = [
            '101'   => '缺少必填的参数,首先确保必填参数齐全，然后确认参数书写是否正确。',
            '102'   => '不支持的语言类型',
            '103'   => '翻译文本过长',
            '104'   => '不支持的API类型',
            '105'   => '不支持的签名类型',
            '106'   => '不支持的响应类型',
            '107'   => '不支持的传输加密类型',
            '108'   => '应用ID无效，注册账号，登录后台创建应用并完成绑定，可获得应用ID和应用密钥等信息',
            '109'   => 'batchLog格式不正确',
            '110'   => '无相关服务的有效应用,应用没有绑定服务应用，可以新建服务应用。注：某些服务的翻译结果发音需要tts服务，需要在控制台创建语音合成服务绑定应用后方能使用。',
            '111'   => '开发者账号无效',
            '112'   => '请求服务无效',
            '113'   => 'q不能为空',
            '114'   => '不支持的图片传输方式',
            '116'   => 'strict字段取值无效，请参考文档填写正确参数值',
            '201'   => '解密失败，可能为DES,BASE64,URLDecode的错误',
            '202'   => '签名检验失败,如果确认应用ID和应用密钥的正确性，仍返回202，一般是编码问题。请确保翻译文本 q 为UTF-8编码.',
            '203'   => '访问IP地址不在可访问IP列表',
            '205'   => '请求的接口与应用的平台类型不一致，确保接入方式（Android SDK、IOS SDK、API）与创建的应用平台类型一致。如有疑问请参考入门指南',
            '206'   => '因为时间戳无效导致签名校验失败',
            '207'   => '重放请求',
            '301'   => '辞典查询失败',
            '302'   => '翻译查询失败',
            '303'   => '服务端的其它异常',
            '304'   => '会话闲置太久超时',
            '308'   => 'rejectFallback参数错误',
            '309'   => 'domain参数错误',
            '310'   => '未开通领域翻译服务',
            '401'   => '账户已经欠费，请进行账户充值',
            '402'   => 'offlinesdk不可用',
            '411'   => '访问频率受限,请稍后访问',
            '412'   => '长请求过于频繁，请稍后访问',
            '1001'  => '无效的OCR类型',
            '1002'  => '不支持的OCR image类型',
            '1003'  => '不支持的OCR Language类型',
            '1004'  => '识别图片过大',
            '1201'  => '图片base64解密失败',
            '1301'  => 'OCR段落识别失败',
            '1411'  => '访问频率受限',
            '1412'  => '超过最大识别字节数',
            '2003'  => '不支持的语言识别Language类型',
            '2004'  => '合成字符过长',
            '2005'  => '不支持的音频文件类型',
            '2006'  => '不支持的发音类型',
            '2201'  => '解密失败',
            '2301'  => '服务的异常',
            '2411'  => '访问频率受限,请稍后访问',
            '2412'  => '超过最大请求字符数',
            '3001'  => '不支持的语音格式',
            '3002'  => '不支持的语音采样率',
            '3003'  => '不支持的语音声道',
            '3004'  => '不支持的语音上传类型',
            '3005'  => '不支持的语言类型',
            '3006'  => '不支持的识别类型',
            '3007'  => '识别音频文件过大',
            '3008'  => '识别音频时长过长',
            '3009'  => '不支持的音频文件类型',
            '3010'  => '不支持的发音类型',
            '3201'  => '解密失败',
            '3301'  => '语音识别失败',
            '3302'  => '语音翻译失败',
            '3303'  => '服务的异常',
            '3411'  => '访问频率受限,请稍后访问',
            '3412'  => '超过最大请求字符数',
            '4001'  => '不支持的语音识别格式',
            '4002'  => '不支持的语音识别采样率',
            '4003'  => '不支持的语音识别声道',
            '4004'  => '不支持的语音上传类型',
            '4005'  => '不支持的语言类型',
            '4006'  => '识别音频文件过大',
            '4007'  => '识别音频时长过长',
            '4201'  => '解密失败',
            '4301'  => '语音识别失败',
            '4303'  => '服务的异常',
            '4411'  => '访问频率受限,请稍后访问',
            '4412'  => '超过最大请求时长',
            '5001'  => '无效的OCR类型',
            '5002'  => '不支持的OCR image类型',
            '5003'  => '不支持的语言类型',
            '5004'  => '识别图片过大',
            '5005'  => '不支持的图片类型',
            '5006'  => '文件为空',
            '5201'  => '解密错误，图片base64解密失败',
            '5301'  => 'OCR段落识别失败',
            '5411'  => '访问频率受限',
            '5412'  => '超过最大识别流量',
            '9001'  => '不支持的语音格式',
            '9002'  => '不支持的语音采样率',
            '9003'  => '不支持的语音声道',
            '9004'  => '不支持的语音上传类型',
            '9005'  => '不支持的语音识别 Language类型',
            '9301'  => 'ASR识别失败',
            '9303'  => '服务器内部错误',
            '9411'  => '访问频率受限（超过最大调用次数）',
            '9412'  => '超过最大处理语音长度',
            '10001' => '无效的OCR类型',
            '10002' => '不支持的OCR image类型',
            '10004' => '识别图片过大',
            '10201' => '图片base64解密失败',
            '10301' => 'OCR段落识别失败',
            '10411' => '访问频率受限',
            '10412' => '超过最大识别流量',
            '11001' => '不支持的语音识别格式',
            '11002' => '不支持的语音识别采样率',
            '11003' => '不支持的语音识别声道',
            '11004' => '不支持的语音上传类型',
            '11005' => '不支持的语言类型',
            '11006' => '识别音频文件过大',
            '11007' => '识别音频时长过长，最大支持30s',
            '11201' => '解密失败',
            '11301' => '语音识别失败',
            '11303' => '服务的异常',
            '11411' => '访问频率受限,请稍后访问',
            '11412' => '超过最大请求时长',
            '12001' => '图片尺寸过大',
            '12002' => '图片base64解密失败',
            '12003' => '引擎服务器返回错误',
            '12004' => '图片为空',
            '12005' => '不支持的识别图片类型',
            '12006' => '图片无匹配结果',
            '13001' => '不支持的角度类型',
            '13002' => '不支持的文件类型',
            '13003' => '表格识别图片过大',
            '13004' => '文件为空',
            '13301' => '表格识别失败',
            '15001' => '需要图片',
            '15002' => '图片过大（1M）',
            '15003' => '服务调用失败',
            '17001' => '需要图片',
            '17002' => '图片过大（1M）',
            '17003' => '识别类型未找到',
            '17004' => '不支持的识别类型',
            '17005' => '服务调用失败'
        ];
        return $codes[$code]??'未知错误';
    }

    /**
     * @param $data
     * @param $from
     * @param $to
     * @return array
     * @throws \Exception
     */
    public function translateBatch($data, $from, $to): array
    {
        $this->baseUrl = 'https://openapi.youdao.com/v2/api';
        if (!$data || !is_array($data)) {
            return [];
        }

        $text   = implode('&', $data);
        $result = $this->translate($text, $from, $to);
        return $result;
    }

    /**
     * @param $text
     * @param $from
     * @param $to
     * @return mixed
     */
    public function translateSingle($text, $from, $to): mixed
    {
        $salt             = $this->createGuid();
        $args             = [
            'q'      => $text,
            'appKey' => $this->appKey,
            'salt'   => $salt,
        ];
        $args['from']     = $from;
        $args['to']       = $to;
        $args['signType'] = 'v3';
        $currentTime      = strtotime('now');
        $args['curtime']  = $currentTime;
        $signStr          = $this->appKey . $this->truncate($text) . $salt . $currentTime . $this->appSecret;
        $args['sign']     = hash('sha256', $signStr);
        $args['vocabId']  = '您的用户词表ID';
        $ret              = $this->call($this->baseUrl, $args);

        return json_decode($ret, true);
    }

    /**
     * uuid generator
     *
     * @return string
     */
    private function createGuid(): string
    {
        $microTime = microtime();
        [$a_dec, $a_sec] = explode(' ', $microTime);
        $dec_hex = dechex($a_dec * 1000000);
        $sec_hex = dechex($a_sec);
        $this->ensureLength($dec_hex, 5);
        $this->ensureLength($sec_hex, 6);
        $guid = $dec_hex;
        $guid .= $this->createGuidSection(3);
        $guid .= '-';
        $guid .= $this->createGuidSection(4);
        $guid .= '-';
        $guid .= $this->createGuidSection(4);
        $guid .= '-';
        $guid .= $this->createGuidSection(4);
        $guid .= '-';
        $guid .= $sec_hex;
        $guid .= $this->createGuidSection(6);

        return $guid;
    }

    /**
     * @param $string
     * @param $length
     * @return void
     */
    private function ensureLength(&$string, $length): void
    {
        $strlen = strlen($string);
        if ($strlen < $length) {
            $string = str_pad($string, $length, '0');
        } elseif ($strlen > $length) {
            $string = substr($string, 0, $length);
        }
    }

    /**
     * @param $characters
     * @return string
     */
    private function createGuidSection($characters): string
    {
        $return = '';
        for ($i = 0; $i < $characters; $i++) {
            $return .= dechex(mt_rand(0, 15));
        }

        return $return;
    }

    /**
     * @param $q
     * @return string
     */
    private function truncate($q): string
    {
        $len = $this->absLength($q);

        return $len <= 20 ? $q : (mb_substr($q, 0, 10) . $len . mb_substr($q, $len - 10, $len));
    }

    /**
     * @param $str
     * @return int
     */
    private function absLength($str): int
    {
        if (empty($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        }
        preg_match_all('/./u', $str, $ar);

        return count($ar[0]);

    }

    /**
     * 发起网络请求
     *
     * @param $url
     * @param $args
     * @param string $method
     * @param int $timeout
     * @param array $headers
     * @return bool|mixed|string
     */
    private function call($url, $args = null, string $method = 'post', int $timeout = self::CURL_TIMEOUT, array $headers = []): mixed
    {
        $ret = false;
        $i   = 0;
        while ($ret === false) {
            if ($i > 1)
                break;
            if ($i > 0) {
                sleep(1);
            }
            $ret = $this->callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }

        return $ret;
    }

    /**
     * @param $url
     * @param $args
     * @param $method
     * @param $withCookie
     * @param $timeout
     * @param $headers
     * @return bool|string
     */
    private function callOnce($url, $args = null, $method = 'post', $withCookie = false, $timeout = self::CURL_TIMEOUT, $headers = []): bool|string
    {
        $ch   = curl_init();
        $data = $this->convert($args);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            if ($data) {
                if (stripos($url, '?') > 0) {
                    $url .= "&$data";
                } else {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    /**
     * @param $args
     * @return mixed|string
     */
    private function convert(&$args): mixed
    {
        $data = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $data .= $key . '[' . $k . ']=' . rawurlencode($v) . '&';
                    }
                } else {
                    $data .= "$key=" . rawurlencode($val) . '&';
                }
            }

            return trim($data, '&');
        }

        return $args;
    }
}
