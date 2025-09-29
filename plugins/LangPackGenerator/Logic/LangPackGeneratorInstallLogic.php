<?php

namespace Plugin\LangPackGenerator\Logic;

class LangPackGeneratorInstallLogic {
    public static function install_hook_file()
    {
        $file_str = '$language[\'code\'] ';
        $hookFiles = [
            'resources/beike/admin/views/pages/page_categories/form.blade.php' => [
                [

                    'query' => "//div[@id=\"tab-{{ {$file_str} }}\"]",
                    'put_content' => "@hook('admin.page_category.info.before')"
                ]
            ],
            'resources/beike/admin/views/pages/design/builder/component/text_i18n.blade.php' => [
                [
                    'query' => "//div[@class='i18n-inner']",
                    'put_content' => "@hook('admin.menu.before')"
                ]
            ],

        ];

        foreach ($hookFiles as $filePath => $fileConfigs){
            foreach ($fileConfigs as $configKey => $fileConfigItem){
                $putContent = $fileConfigItem['put_content'];
                $query = $fileConfigItem['query'];

                $filePath = base_path($filePath);
                $htmlContent = file_get_contents($filePath);
                $hackEncoding = '<?xml encoding="UTF-8">';
                $dom = new \DOMDocument('1.0', 'UTF-8');

                @$dom->loadHTML($hackEncoding.$htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // 忽略错误，因为HTML可能不完整

                // 创建 XPath 查询对象
                $xpath = new \DOMXPath($dom);
                $divs = $xpath->query($query);

                $needsSave = true; // 标记是否需要保存文件
                $existingHook = $xpath->query(".//text()[contains(., \"{$putContent}\")]");

                if ($existingHook->length > 0) {
                    $needsSave = false; // 标记需要保存文件
                }
                foreach ($divs as $div) {
                    // 获取父级元素的前一个兄弟节点
                    $previousSibling = $div->previousSibling;

                    // 初始化缩进量
                    $indentation = '';

                    // 如果前一个兄弟节点存在且是文本节点，则获取其内容以确定缩进量
                    if ($previousSibling && $previousSibling->nodeType === XML_TEXT_NODE) {
                        $indentation = trim($previousSibling->wholeText, "\n");
                    }
                    // 创建一个新的文本节点
                    $newText = $dom->createTextNode("\n  {$indentation}{$putContent}");

                    // 在找到的 div 元素内部的第一行插入新创建的文本节点
                    if ($div->firstChild) {
                        $div->insertBefore($newText, $div->firstChild);
                    } else {
                        $div->appendChild($newText);
                    }
                }
                if ($needsSave) {
                    // 保存修改后的 HTML 内容回文件
                    $newHtmlContent = $dom->saveHTML();
                    $newHtmlContent = str_replace('<?xml encoding="UTF-8">', '', $newHtmlContent);
                    $newHtmlContent = html_entity_decode($newHtmlContent);
                    dd($query,$newHtmlContent);
                    file_put_contents($filePath, $newHtmlContent);
                }
            }

        }



    }
}
