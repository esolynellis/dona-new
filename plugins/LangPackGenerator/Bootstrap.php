<?php
/**
 * Bootstrap.php
 *
 * @copyright  2023 HL-MALL.com - All Rights Reserved
 * @link       https://HL-MALL.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-09-04 16:04:23
 * @modified   2023-09-04 16:04:23
 */

namespace Plugin\LangPackGenerator;


use Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic;
use Plugin\LangPackGenerator\Services\AezhushouService;
use Plugin\LangPackGenerator\Services\AutoModelService;
use Plugin\LangPackGenerator\Services\DeepSeekService;
use Plugin\LangPackGenerator\Services\factory\TranslateFactory;
use Plugin\LangPackGenerator\Services\PapagoService;
use Plugin\Youdao\Services\YoudaoService;

class Bootstrap
{

    public function editorScript()
    {
        return <<<SCRIPT
<script>
    $(function (e) {

         window.addEventListener('message', function (event) {
             switch (event.data.type) {
                case 'UPDATE_TINYMCE':

                        let updateContent = event.data.content;
                      const token = document.querySelector('meta[name="csrf-token"]').content;

                        $.ajax({
                          url: 'admin/lang_pack_generator_decode_unescapeHtml',
                          type: 'POST',
                          timeout:600000, //设置超时的时间10s
                          headers: {'X-CSRF-TOKEN': token},
                          data: {html:updateContent},
                          success: function (res) {
                                tinyMCE.editors[event.data.tinyID].setContent(res);
                                tinyMCE.editors[event.data.tinyID].save()
                          }
                        })

                    break;
                case 'GET_TINYMCE_CONTENT':
                       console.log("接收到获取文本内容请求：",event.data)
                       let content = tinyMCE.editors[event.data.tinyID].getContent();
                       event.source.postMessage({ type: 'GET_TINYMCE_CALLBACK', content: content, tinyID:event.data.tinyID }, '*');
                    // tinymce.setContent(event.data.content);
                    break;
            }
        }, false);

    })
  </script>
SCRIPT;
    }
    public function boot()
    {
        // $d = new AutoModelService();
        // dd($d->translate('zh_cn','en',"你好,世界"));
        /**
         * 文本i18n组件
         * 10:resources/beike/admin/views/pages/design/builder/component/text_i18n.blade.php
         */
        add_hook_blade('admin.menu.before', function ($callback, $output, $data){
            LangPackGeneratorLogic::checkManualInstalled();
            $data['component'] = 'menu';
            return  view('LangPackGenerator::components/script', $data);
        });
        /**
         * 文章分类
         * 44:resources/beike/admin/views/pages/page_categories/form.blade.php
         */
        add_hook_blade('admin.page_category.info.before', function ($callback, $output, $data){
            LangPackGeneratorLogic::checkManualInstalled();
            $data['component'] = 'page_category';
            return  view('LangPackGenerator::components/script', $data);
        });
        /**
         * 文章表单
         * 42:resources/beike/admin/views/pages/pages/form.blade.php
         */
        add_hook_blade('admin.page.info.before', function ($callback, $output, $data){
            LangPackGeneratorLogic::checkManualInstalled();
            $data['component'] = 'article';
            return  view('LangPackGenerator::components/script', $data);
        });

        /**
         * <1.5.5 - 商品属性组
         *
         * 53:resources/beike/admin/views/pages/attributes/index.blade.php
         * 48:resources/beike/admin/views/pages/attribute_group/index.blade.php
         * 19,84:resources/beike/admin/views/pages/attributes/form.blade.php
         */
        add_hook_blade('admin.product.sku.edit.item.after', function ($callback, $output, $data){
            $data['component'] = 'common';
            return  view('LangPackGenerator::components/script',$data);
        });

        /**
         * 1.5.5 - 属性保存页面
         * 84:plugins/LangPackGenerator/Install/files/v1.5.5/resources/beike/admin/views/pages/attributes/form.blade.php
         */
        add_hook_blade('admin.product.attributes.values.edit.dialog.name.after', function ($callback, $output, $data){
            $data['component'] = 'common';
            return  view('LangPackGenerator::components/script',$data);
        });
        /**
         * 1.5.5 - 属性保存页面
         * 19:plugins/LangPackGenerator/Install/files/v1.5.5/resources/beike/admin/views/pages/attributes/form.blade.php
         */
        add_hook_blade('admin.product.attributes.edit.name.after', function ($callback, $output, $data){
            $data['component'] = 'common';
            return  view('LangPackGenerator::components/script',$data);
        });


        /**
         * 1.5.5 - 产品属性
         * 53:plugins/LangPackGenerator/Install/files/v1.5.5/resources/beike/admin/views/pages/attributes/index.blade.php
         */
        add_hook_blade('admin.product.attributes.add.dialog.name.after', function ($callback, $output, $data){
            $data['component'] = 'common';
            return  view('LangPackGenerator::components/script',$data);
        });

        /**
         * 1.5.5 商品属性组
         * 48:plugins/LangPackGenerator/Install/files/v1.5.5/resources/beike/admin/views/pages/attribute_group/index.blade.php
         */
        add_hook_blade('admin.product.attributes.group.edit.dialog.name.after', function ($callback, $output, $data){
            $data['component'] = 'common';
            return  view('LangPackGenerator::components/script',$data);
        });
        /**
         * 商品翻译脚本注入:/admin/products
          */
        add_hook_blade('admin.product.content.after', function($callback, $output, $data) {
            LangPackGeneratorLogic::checkManualInstalled();
            $data['component'] = 'product';
            return  $output.view('LangPackGenerator::components/script', $data);
        });

        add_hook_blade('admin.product.form.footer', function($callback, $output, $data) {
            return $output.$this->editorScript();
        });

        add_hook_blade('admin.page.form.footer', function($callback, $output, $data) {
            return $output.$this->editorScript();
        });

        add_hook_filter('admin.service.translator', function ($data) {

            return LangPackGeneratorLogic::getMasterPlatform();
        });

        add_hook_filter('admin.sidebar.setting.prefix', function ($data) {
            $data[] = 'lang_pack_generator';

            return $data;
        });

        add_hook_filter('admin.sidebar.setting_routes', function ($data) {
            $data[] = [
                'route'    => 'LangPackGenerator.index',
                'prefixes' => ['lang_pack_generator'],
                'title'    => trans('LangPackGenerator::common.lang pack generator') ,
            ];

            return $data;
        });

        add_hook_filter('role.permissions.plugin', function ($data) {
            $data[] = [
                'title'       => trans('LangPackGenerator::common.lang pack generator'),
                'permissions' => [
                    [
                        'code' => 'lang_pack_generator',
                        'name' => trans('LangPackGenerator::common.lang pack generator'),
                    ],
                ],
            ];

            return $data;
        });

        add_hook_filter('admin.components.sidebar.menus', function ($data) {
            $data[] = [
                'route'    => 'LangPackGenerator.index',
                'title'    => trans('语言'),
                'icon'     => 'bi bi-gear',
                'prefixes' => ['LangPackGenerator','lang_pack_generator_replace'],
                'children' => [
                    [
                        'route'    => 'LangPackGenerator.index',
                        'prefixes' => ['LangPackGenerator'],
                        'title'    => trans('LangPackGenerator::common.lang pack generator') ,
                    ],
                    [
                        'route'    => 'lang_pack_generator_replace',
                        'prefixes' => ['lang_pack_generator_replace'],
                        'title'    => trans('替换语言') ,
                    ],
                ],
            ];
            return $data;
        });
    }
}

