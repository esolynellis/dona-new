<?php

namespace Plugin\LangPackGenerator\Services\interface;

use Illuminate\Translation\PotentiallyTranslatedString;

interface TranslateInterface extends \Beike\Services\TranslatorService
{
    /**
     * 单条翻译
     * @param string $from 源语言(国家编码简写)
     * @param string $to 翻译语言(国家编码简写)
     * @param string $text 翻译文本
     * @return string
     */
    public function translate( $from,  $to,  $text): string;

    /**
     * 多条翻译
     * @param string $from 源语言(国家编码简写)
     * @param string $to 翻译语言(国家编码简写)
     * @param string $texts 翻译文本
     * @return array
     */
    public function batchTranslate( $from,  $to,  $texts): array;

    /**
     * 转化代码
     * @param $code
     * @return string
     */
    public function mapCode($code): string;

    /**
     * 获取类配置描述
     * @param $code
     * @return string
     */
    public function getConfig(): array;
}
