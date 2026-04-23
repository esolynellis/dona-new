<?php

namespace Plugin\LangPackGenerator\Services;

use Beike\Repositories\SettingRepo;
use Beike\Services\TranslatorService;
use Plugin\LangPackGenerator\Services\interface\TranslateInterface;

class AezhushouService extends BaseService implements TranslateInterface
{

    protected array $lang_match = [
        'de'     => 'de',
        'en'     => 'en',
        'es'     => 'es',
        'fr'     => 'fr',
        'id'     => 'id',
        'it'     => 'it',
        'ja'     => 'ja',
        'ko'     => 'ko',
        'ru'     => 'ru',
        'zh-CHS' => 'zh',
        'zh-CHT' => 'zh-hk',
        'zh_hk'  => 'zh-hk',
        'zh_cn'  => 'zh-CN'
    ];
    /**
     * @param string $from
     * @param string $to
     * @param string $text
     * @return mixed
     */
    public function translate($from, $to, $text): string
    {
        $sf = parent::translate($from, $to, $text);
        if ( $sf !== '__PASS__' ){
            return $sf;
        }
        $from = $this->mapCode($from);
        $to   = $this->mapCode($to);

        $url  = 'https://translate.aezhushou.com/translate_a/single?client=gtx&sl=auto&tl=' . $to . '&dt=t&otf=1&ssel=0&tsel=0&kc=3&tk=918124.538136&q=' . urlencode($text);
        $json = @file_get_contents($url);

        $response = json_decode($json, true);

        $list   = $response[0] ?? [];
        $result = '';
        foreach ($list as $k => $v) {
            if ($v[0]) {
                $result .= $v[0] ?: $v;
            }

        }

        return $this->output($result);
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $texts
     * @return mixed
     */
    public function batchTranslate($from, $to, $texts): array
    {
        // TODO: Implement batchTranslate() method.
        return [];
    }



    public function getConfig(): array
    {
        return [
            'key'   => 'Aezhushou',
            'label' => '内置演示线路2',
            'status' => 1
        ];
    }
}
