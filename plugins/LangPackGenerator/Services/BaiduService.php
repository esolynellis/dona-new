<?php

namespace Plugin\LangPackGenerator\Services;

use Beike\Repositories\SettingRepo;
use Plugin\LangPackGenerator\Libraries\Youdao;
use Plugin\LangPackGenerator\Services\interface\TranslateInterface;

class BaiduService  extends BaseService implements TranslateInterface
{
    protected array $lang_match = [
        'zh_cn'  => 'zh',
        'yue'    => 'yue',
        'ja'     => 'jp',
        'en'     => 'en',
        'wyw'    => 'wyw',
        'ko'     => 'kor',
        'fr'     => 'fra',
        'es'     => 'spa',
        'th'     => 'th',
        'ar'     => 'ara',
        'ru'     => 'ru',
        'pt'     => 'pt',
        'de'     => 'de',
        'it'     => 'it',
        'el'     => 'el',
        'nl'     => 'nl',
        'pl'     => 'pl',
        'bg'     => 'bul',
        'et'     => 'est',
        'fi'     => 'fin',
        'cs'     => 'cs',
        'ro'     => 'rom',
        'sl'     => 'slo',
        'sv'     => 'swe',
        'hu'     => 'hu',
        'zh-CHT' => 'cht',
        'vi'     => 'vie',
        'zh_hk'  => 'zh-hk',
    ];

    /**
     * @param string $from
     * @param string $to
     * @param string $text
     * @return string
     */
    public function translate(  $from,   $to,   $text): string
    {
        $sf = parent::translate($from, $to, $text);
        if ( $sf !== '__PASS__' ){
            return $sf;
        }
        $appKey    = plugin_setting('lang_pack_generator.baidu_app_id', '');
        $appSecret = plugin_setting('lang_pack_generator.baidu_app_secret', '');
        $from      = $this->mapCode($from);
        $to        = $this->mapCode($to);

        $params    = [
            'q'     => $text,
            'from'  => 'auto',
            'to'    => $to,
            'appid' => $appKey,
            'salt'  => uniqid(),
        ];
        $signStr1  = $appKey . $text . $params['salt'];
        $signStr1  .= $appSecret;

        $params['sign'] = md5($signStr1);
        $query          = http_build_query($params);

        $url      = 'http://api.fanyi.baidu.com/api/trans/vip/translate?' . $query;
        $json     = @file_get_contents($url);
        $response = json_decode($json, true);
        if (!isset($response['trans_result'])) {
            throw new \Exception('百度翻译失败,错误码:' . $response['error_code'] . ', 内容:' . $this->getErrorCodeMessage($response['error_code']??''), $response['error_code']);
        }
        $list   = $response['trans_result'] ?? [];
        $result = '';
        foreach ($list as $k => $v) {
            if ($v['dst']) {

                $result .= $v['dst'] ?: $v;
            }

        }
        return $this->output($result);

    }

    public function getErrorCodeMessage($code)
    {
        $errors = [
            '52001' => 'API网络请求超时, 请重试 ',
            '52002' => '百度翻译服务系统错误, 请重试 ',
            '52003' => '未授权用户, 请检查appid是否正确或者服务是否开通 ',
            '54000' => '必填参数为空,  请检查是否少传参数  ',
            '54001' => ' 签名错误 ,  请检查您的签名生成方法   ',
            '54003' => ' 访问频率受限  ,  降低您的调用频率，或进行身份认证后切换为高级版/尊享版    ',
            '54004' => '账户余额不足, 请前往<a target="_blank" href="https://api.fanyi.baidu.com/api/trans/product/desktop">管理控制台</a>为账户充值 ',
            '54005' => '长query请求频繁, 请降低长query的发送频率，3s后再试',
            '58000' => ' 客户端IP非法, 检查个人资料里填写的IP地址是否正确，可前往<a target="_blank" href="https://api.fanyi.baidu.com/access/0/3">开发者信息-基本信息</a>修改',
            '58001' => '译文语言方向不支持, 检查译文语言是否在语言列表里, <a target="_blank" href="https://api.fanyi.baidu.com/doc/21">查看</a>',
            '58002' => '服务当前已关闭,请前往<a target="_blank" href="https://api.fanyi.baidu.com/choose">管理控制台</a>开启服务  ',
            '90107' => '认证未通过或未生效,  请前往<a target="_blank" href="https://api.fanyi.baidu.com/myIdentify">我的认证</a>查看认证进度 '
        ];
        return $errors[$code]??'未知错误';
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $texts
     * @return array
     */
    public function batchTranslate(  $from,   $to,   $texts): array
    {
        // TODO: Implement batchTranslate() method.
        return [];
    }



    public function getConfig(): array
    {
        return [
            'key'   => 'baidu',
            'label' => '百度翻译',
            'status' => 1
        ];
    }
}
