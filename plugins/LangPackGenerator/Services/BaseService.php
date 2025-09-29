<?php

namespace Plugin\LangPackGenerator\Services;

use Beike\Repositories\SettingRepo;
use DOMDocument;
use DOMXPath;
use Plugin\LangPackGenerator\Libraries\LangPackGenerator;
use Plugin\LangPackGenerator\Services\interface\TranslateInterface;

class BaseService implements TranslateInterface
{
    public $type = '';
    public $t1 = 0;
    public $t2 = 0;
    protected array $lang_match = [

    ];
    public function __construct()
    {
        $this->t1 = self::getMillisecond();
    }

    public function validate($from, $to, $text)
    {

        $source_to = $to;
        $source_from = $from;
        $to = $this->mapCode($to);
        $from = $this->mapCode($from);

        if (!$to  ){
            return ['key' => 'to', 'source' => $source_to,'value' => $to];
        }
        if ( !$from){
            return ['key' => 'from', 'source' => $source_from,'value' => $from];

        }
        return true;
    }
    public function output($data)
    {

        $this->t2 = self::getMillisecond();
        $name = get_called_class();
        $classNameWithNamespace = $name;
        $classNameWithoutNamespace = basename(str_replace('\\', '/', $classNameWithNamespace));
        // 检查配置是否正常
        $className = str_replace('Service', '', $classNameWithoutNamespace);

        if (!$data){
            self::ms($className, 0, 0);
        }else{
            self::ms($className, $this->t1, $this->t2);
        }
        return $data;
    }
    public static function ms($name, $msT1, $msT2)
    {
        $delayTime = $msT2 - $msT1;
        $platformStatus = plugin_setting('lang_pack_generator.platform_status', []);
        $name  = ucfirst($name);
        $platformStatus[$name] = [
            'ms' => $delayTime,
        ];
        SettingRepo::update('plugin', 'lang_pack_generator', ['platform_status' => $platformStatus]);
    }
    static function getMillisecond() {
        [$microSeconds, $seconds] = explode(' ', microtime());
        $microSeconds = (float) $microSeconds;
        $seconds = (float) $seconds;

        // 将微秒转换为毫秒
        $milliseconds = ($seconds * 1000) + ($microSeconds * 1000);

        return floor($milliseconds);
    }

    public function translate($from, $to, $text): string
    {
        $validate = $this->validate($from, $to, $text);

        if ($validate !== true) {
            $config = $this->getConfig();
            $name = $config['label'];
            throw new \Exception("编码不存在，请到设置->[语言映射]-> [{$name}] 绑定到【系统语言编码({$validate['source']})】");
        }
        if ($this->hasHtmlTags($text)) {
            // 走富文本翻译
            return $this->richTextTranslate($this->mapCode($from), $this->mapCode($to), $text);
        }
        return '__PASS__';
    }

    public function batchTranslate($from, $to, $texts): array
    {
        // TODO: Implement batchTranslate() method.
        return [];
    }

    /**
     * 转化代码
     * @param $code
     * @return string
     */
    public function mapCode($code): string
    {

        $platform_config = $this->getConfig();
        $platform_id = $platform_config['key'];
        $lang_conversion_list = plugin_setting('lang_pack_generator.lang_conversion_list', []);
        if ($lang_conversion_list){
            foreach ($lang_conversion_list as $item){
                if ($item['master'] == $code){
                    $code =  $item[$platform_id]??$code;
                    break;
                }
            }
        }


        $list = LangPackGenerator::getLangCodeList($this->getConfig()['key']);

        if (in_array($code, array_keys($list))){
            return $code;
        }
        return $this->lang_match[$code] ?? '';
    }

    public function getConfig(): array
    {
        // TODO: Implement getConfig() method.
        return [];
    }

    public function richTextTranslate($from, $to, $html)
    {

        $hackEncoding = '<?xml encoding="UTF-8">';
        $dom = new DOMDocument('1.0', 'UTF-8');

        @$dom->loadHTML($hackEncoding.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // 忽略错误，因为HTML可能不完整

        // 创建 XPath 查询对象
        $xpath = new DOMXPath($dom);

        // 获取所有包含文本的元素节点
        $elements = $xpath->query('//*/text()[normalize-space()]');
        // 遍历所有文本节点
        $translatedContents = [];
        foreach ($elements as $element) {
            // 获取文本内容
            $content = trim($element->nodeValue);
            if ($content !== '') {
                 $translatedContents[] = $content;
            }
        }


        $resultContent = $this->translate($from, $to, implode("\n", $translatedContents));
        $resultContentList = explode("\n", $resultContent);
        $i = 0;

        foreach ($elements as $element) {
            // 获取文本内容
            $content = trim($element->nodeValue);
            if ($content !== '') {

                // 获取父元素
                $parent = $element->parentNode;

                // 获取父元素的样式属性
                $styleAttr = $parent->getAttribute('style');

                // 替换原始内容
                $element->nodeValue = $resultContentList[$i];;

                // 重新设置样式属性
                $parent->setAttribute('style', $styleAttr);
                $i++;
            }
        }
        // 从根节点开始替换
        // $this->replaceChineseText($from, $to, $dom->documentElement);

        // 输出修改后的HTML
        $html = $dom->saveHTML();
        $html = str_replace($hackEncoding, '', $html);

        return $html;

    }

    // 递归函数来遍历所有节点
    function replaceChineseText($from, $to, $node) {
        $nodeValue = trim($node->nodeValue);

        dump($nodeValue);
        if ($node->nodeType == XML_TEXT_NODE) {
            // 使用正则表达式替换所有中文字符
            // $text = preg_replace('/[\x{4e00}-\x{9fa5}]+/u', '英文', $node->nodeValue);



            if ($nodeValue && $nodeValue != "\n"){

                $text = $this->translate($from, $to,  $nodeValue);
                $node->nodeValue = $text;
            }

        }

        foreach ($node->childNodes as $childNode) {

            $this->replaceChineseText($from, $to, $childNode);
        }
    }

    function hasHtmlTags($str) {
        if ($this->type === 'html'){
            return true;
        }
        if ($this->type === 'text'){
            return false;
        }
        return $str !== strip_tags($str);
    }

}
