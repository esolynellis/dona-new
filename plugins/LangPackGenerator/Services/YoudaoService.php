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

namespace Plugin\LangPackGenerator\Services;

use Beike\Services\TranslatorService;
use Plugin\LangPackGenerator\Libraries\Youdao;
use Plugin\LangPackGenerator\Services\interface\TranslateInterface;


class YoudaoService extends BaseService implements TranslateInterface {
    private Youdao $translator;

    protected array $lang_match = [
        'ar'    => 'ar',
        'de'    => 'de',
        'en'    => 'en',
        'es'    => 'es',
        'fr'    => 'fr',
        'id'    => 'id',
        'it'    => 'it',
        'ja'    => 'ja',
        'ko'    => 'ko',
        'ru'    => 'ru',
        'zh_cn' => 'zh-CHS',
        'zh_hk' => 'zh-CHT',
        'hi'    => 'hi',
    ];

    public function __construct()
    {
        parent::__construct();
        $appKey = plugin_setting('lang_pack_generator.app_id', '');
        $appSecret = plugin_setting('lang_pack_generator.app_secret', '');
        $this->translator = new Youdao($appKey, $appSecret);
    }

    /**
     * @throws \Exception
     */
    public function translate($from, $to, $text): string
    {
        $sf = parent::translate($from, $to, $text);
        if ( $sf !== '__PASS__' ){
            return $sf;
        }
        $from = $this->mapCode($from);
        $to = $this->mapCode($to);
        return $this->output($this->translator->translate($text, $from, $to));
    }

    /**
     * 批量翻译
     *
     * @param string $from
     * @param string $to
     * @param string $texts
     * @return array
     * @throws \Exception
     */
    public function batchTranslate($from, $to, $texts): array
    {
        $from = $this->mapCode($from);
        $to = $this->mapCode($to);

        return $this->translator->translateBatch($texts, $from, $to);
    }

    public function getConfig(): array
    {
        return [
            'key'   => 'youdao',
            'label' => '有道翻译',
            'status' => 1
        ];
    }
}
