<?php

namespace Plugin\LangPackGenerator\Services;

use Illuminate\Support\Facades\Log;
use Plugin\LangPackGenerator\Libraries\LangPackGenerator;
use Plugin\LangPackGenerator\Services\interface\TranslateInterface;

class DeepSeekService extends BaseService implements TranslateInterface {
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
        // 将简体中文语言: 欧洲站夏季新款时尚休闲短裤热裤女裤运动家具纯棉韩版宽松百搭裤, 准确的翻译成接近现实生活中的英文语言
        $message = "将这段".$this->mapCode($from)."文本翻译成".$this->mapCode($to).",仅输出翻译结果文本:{$text}";
        $data = [
            'messages' => [
                [
                    "content"=> $message,
                    "role"=>'user'
                ],

            ]
        ];

        $response = $this->httpRequest($this->params($data));
        return $this->output($response['choices'][0]['message']['content'] ?? '');
    }

    public function params($data)
    {
        $data['model']    = 'deepseek-chat';
        $data['stream'] = false;
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
            'response_format' => ['json_object']
        ];
        $response = $this->httpRequest($this->params($data));
        return $response['choices'][0]['message']['content'] ?? [];
    }

    /**
     * 转化代码
     * @param $code
     * @return string
     */
    public function mapCode($code): string
    {
        $name = $code;
        $list = LangPackGenerator::getLangCodeList($this->getConfig()['key']);
        foreach ($list as $key => $name){
            if ($code == $key){
                break;
            }
        }
        return $name;
    }

    /**
     * 获取类配置描述
     * @param $code
     * @return string
     */
    public function getConfig(): array
    {
        return [
            'key'   => 'deepSeek',
            'label' => 'DeepSeek Ai翻译',
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

        $url    = 'https://api.deepseek.com/chat/completions';
        $config = $this->generateAuthorization($url);

        $header = [
            'authorization: Bearer ' . $config['Authorization'],
            'content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 总超时时间为 10 秒
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // 连接超时时间为 5 秒

        $data = curl_exec($ch);
        if (curl_error($ch)) {
            return null;
        }
        curl_close($ch);

        return json_decode($data, true);
    }

    function generateAuthorization($url)
    {
        $appKey    = plugin_setting('lang_pack_generator.deepseek_app_key', '');
         return [
            "Authorization" => $appKey,
        ];
    }
}
