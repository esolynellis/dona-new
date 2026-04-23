<?php

namespace Plugin\LangPackGenerator\Services;

use Illuminate\Support\Facades\Cache;
use Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic;
use Plugin\LangPackGenerator\Services\interface\TranslateInterface;

class AutoModelService extends BaseService implements TranslateInterface {
    /**
     * @return \Plugin\LangPackGenerator\Services\BaseService
     * @throws \Exception
     */
    public function instance()
    {
        $list = LangPackGeneratorLogic::getServiceList();
        foreach ($list as $item){
            $lang_pack_generator_current = Cache::get('lang_pack_generator_current');
            if (!$lang_pack_generator_current){
                $class = LangPackGeneratorLogic::getMasterPlatform($item['id']);
                if (new AutoModelService() instanceof $class){
                    continue;
                }
                return new $class ;
            }
        }
        $class = LangPackGeneratorLogic::getMasterPlatform();

        if (new AutoModelService instanceof $class){
            return new AezhushouService();
        }
        return new $class ;
    }
    /**
     * 单条翻译
     * @param string $from 源语言(国家编码简写)
     * @param string $to   翻译语言(国家编码简写)
     * @param string $text 翻译文本
     * @return string
     */
    public function translate($from, $to, $text): string
    {
        $list = LangPackGeneratorLogic::getServiceList();
        foreach ($list as $item){
            $lang_pack_generator_current = Cache::get('lang_pack_generator_current');
            if (!$lang_pack_generator_current){
                $to = $this->instance()->translate($from, $to, $text);
                return $to;
            }
        }
        return $this->instance()->translate($from, $to, $text);
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
         return $this->instance()->batchTranslate($from, $to, $texts);
    }

    /**
     * 转化代码
     * @param $code
     * @return string
     */
    public function mapCode($code): string
    {
        return   $this->instance()->mapCode($code);
    }

    /**
     * 获取类配置描述
     * @param $code
     * @return string
     */
    public function getConfig(): array
    {
        return [
            'key'   => 'autoModel',
            'label' => '自动',
            'is_new' => 1,
            'status' => 1
        ];
    }


}
