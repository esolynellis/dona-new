<?php
/**
 * common.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2023-02-24 16:19:06
 * @modified   2023-02-24 16:19:06
 */

return [
    'title'                                => '商品批量导入',

    'index'                                => '功能首页',
    'export'                               => '商品导出',
    'import'                               => '商品导入',

    'import_excel_file'                    => '导入文件',

    'error_header_need_columns'            => 'Excel的表%s缺少字段：%s！',
    'error_file_required'                  => '请选择文件',
    'error_sheet_not_exist'                => '缺少sheet',
    'error_filed_required'                 => '表%s的第%s行的字段“%s”必填！',
    'error_post_max_size'                  => '文件大小超过 %s (查看php设置 \'post_max_size\')',
    'error_upload_max_filesize'            => '文件大小超过 %s (查看php设置 \'upload_max_filesize\')',
    'error_upload'                         => '导入的电子表格无效或其中的数据格式错误!',
    'error_variant'                        => '处理Excel表中Product ID / SPU为 :id 的数据时出现错误，',
    'error_variant_math'                   => 'product_skus工作表中的规格 :variant_str 在variants工作表的中无法找到匹配项',
    'error_product_skus_variants'          => 'product_skus表的Variants字段格式错误 Variants字段值: :variants 注意：规格名和规格值名不能包含符号\':\'和\';\'',
    'error_no_results'                     => '没有符合条件的数据',
    'error_skus_required'                  => 'product表的列[Product ID / SPU]值为 :id 的记录没有对应的[product_skus]表记录，请先完善数据！',

    'text_success'                         => '成功：您已成功导入您的数据',
    'text_upload_confirm'                  => '确认开始导入？',
    'text_export_pid'                      => '按商品 ID 导出',
    'text_export_page'                     => '分批导出',
    'text_required'                        => '*',

    'text_export'                          => '商品导出',
    'text_import'                          => '商品导入',
    'entry_export_way'                     => '商品导出方式：',
    'entry_start_id'                       => '商品 ID （从）：',
    'entry_end_id'                         => '商品 ID （到）：',
    'entry_number'                         => '导出数量：',
    'entry_index'                          => '导出批次：',

    'button_export'                        => '导出',
    'button_import'                        => '导入',
    'button_cancel'                        => '取消',

    'help_file'                            => '注意：
        <ol>
            <li>导入请使用Excel2007格式（扩展名为.xlsx），导入数据表格式参见导出的excel格式；</li>
            <li>商品分类id，在“分类管理”-“编辑分类”页面的url路径中可以看到；</li>'
            . ((function_exists('is_seller') && is_seller()) ? '' : '<li>如果是在服务器端批量上传商品图片，建议将图片文件放在public/catalog/ 目录下；</li>')
            . '<li>如果是在图片管理器中上传，可以右键“复制图像链接”查看图片路径；</li>
            <li>表格中 加 * 号为必填项；</li>
            <li>每张表格对应的名称如下：<br/>
                &emsp;product = 商品基础信息<br/>
                &emsp;variants = 规格和规格值<br/>
                &emsp;product_skus = 商品规格<br/>
                &emsp;product_attribute = 商品属性</li>
        </ol>',

    'des_product_id'                       => '商品ID, 新建商品输入含字母的字符串(保证商品唯一)，建议用SPU或者型号',
    'des_product_categories'               => '商品分类，以半角逗号\',\'隔开的分类ID',
    'des_product_brand_id'                 => '品牌ID',
    'des_product_images'                   => '商品图片路径' . PHP_EOL . '例如：catalog/demo/product/test.jpg,catalog/demo/product/test1.jpg',
    'des_product_video'                    => '商品视频路径',
    'des_product_weight'                   => '重量',
    'des_product_weight_class'             => '重量单位' . PHP_EOL . '(可选单位：kg/g/oz/lb)',
    'des_product_relateds'                 => '关联商品，以半角逗号\',\'隔开的商品id',
    'des_product_position'                 => '商品排序，填写整数数字',
    'des_product_active'                   => '商品状态' . PHP_EOL . '（0代表禁用，1代表启用）',
    'des_product_name'                     => '商品名称，输入对应语言的商品名称',
    'des_product_content'                  => '商品详情，输入对应语言的商品描述',
    'des_product_meta_title'               => '商品META TITLE，输入对应语言的META TITLE',
    'des_product_meta_description'         => '商品META DESCRIPTION，输入对应语言的META DESCRIPTION',
    'des_product_meta_keywords'            => '商品META KEYWORD，输入对应语言的META KEYWORD',

    'des_variants_product_id'              => '跟商品表中的\'Product ID / SPU\'对应',
    'des_variants_name'                    => '规格名，填写对应语言的规格名称',
    'des_variants_value'                   => '规格值，填写对应语言的规格值名称',
    'des_variants_image'                   => '图片路径，规格值对应的图片路径，有则填写',
    'des_product_skus_product_id'          => '跟商品表中的\'Product ID / SPU\'对应',
    'des_product_skus_variants'            => '规格',
    'des_product_skus_images'              => '规格商品图片路径，格式同商品图片',
    'des_product_skus_model'               => '型号',
    'des_product_skus_sku'                 => 'SKU',
    'des_product_skus_weight'              => '重量',
    'des_product_skus_price'               => '价格',
    'des_product_skus_origin_price'        => '原价',
    'des_product_skus_cost_price'          => '成本价',
    'des_product_skus_quantity'            => '数量',
    'des_product_attribute_product_id'     => '跟商品表中的\'Product ID / SPU\'对应',
    'des_product_attribute_attribute_group_id'=> '商品属性组ID, 属性组需要预先在系统创建好',
    'des_product_attribute_attribute_name' => '属性名，填写对应语言的属性名称',
    'des_product_attribute_value_name'     => '属性值，填写对应语言的属性值名称',
];
