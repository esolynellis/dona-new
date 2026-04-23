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
    'title'                        => 'Product Batch Import',

    'index'                        => 'Plugin Home',
    'export'                       => 'Product Export',
    'import'                       => 'Product Import',

    'import_excel_file'            => 'File by import',

    'error_header_need_columns'    => 'Column required! table: %s, column: %s',
    'error_file_required'          => 'Please select a file first',
    'error_sheet_not_exist'        => 'Missing sheet',
    'error_filed_required'         => 'Cell required! table: %s, line: %s, field: %s',
    'error_post_max_size'          => 'The file size exceeds the %s limit(view the parameter \'post_max_size\' in file php.ini)',
    'error_upload_max_filesize'    => 'The file size exceeds the %s limit(view the parameter \'upload_max_filesize\' in file php.ini)',
    'error_upload'                 => 'The imported excel is invalid or the data format error is in it!',
    'error_variant'                => 'An error occurred while processing the data with Product ID/SPU of: id in the Excel table,',
    'error_variant_math'           => 'The specification variant_str in the product_skus worksheet cannot find a match in the variables worksheet',
    'error_product_skus_variants'  => 'The format of the variables column in the product_skus sheet is incorrect! Variants: :variants Attention: Variant name and value name cannot contain \':\' and \';\'',
    'error_no_results'             => 'No eligible products',

    'text_success'                 => 'Success: You have successfully modified your database!',
    'text_upload_confirm'          => 'Confirm?',
    'text_export_pid'              => 'By Product ID',
    'text_export_page'             => 'By Batch',
    'text_required'                => '*',

    'text_export'                  => 'Product Batch Export',
    'text_import'                  => 'Product Batch Import',
    'entry_export_way'             => 'Export Method',
    'entry_start_id'               => 'Product Id From',
    'entry_end_id'                 => 'Product ID To',
    'entry_number'                 => 'Export Count',
    'entry_index'                  => 'Batch Index',

    'button_export'                => 'Export',
    'button_import'                => 'Import',
    'button_cancel'                => 'Cancel',

    'help_file' => 'Note:<br/>
        1. Please use Excel2007 format (extension .xlsx) for import. For the format of the imported data table, please refer to the exported Excel format;<br/>
        2. The product category id can be found in the URL path of the "Category Management"-"Edit Category" page;<br/>
        3. If you are uploading product images in batches on the server, it is recommended to put the image files in the public/catalog/ directory;<br/>
        4. If you are uploading in the image manager, you can right-click "Copy Image Link" to view the image path;<br/>
        5. The * in the table is required;<br/>
        6. The names of each table are as follows:<br/>
        &emsp;product = Basic information of the product<br/>
        &emsp;variants = Specifications and specification values<br/>
        &emsp;product_skus = Product specifications<br/>
        &emsp;product_attribute = Product attributes',

    'des_product_id' => 'Product ID, enter a string containing letters when creating a new product (to ensure the uniqueness of the product), it is recommended to use SPU or model',
    'des_product_categories' => 'Product categories, category IDs separated by half-width commas\',\'',
    'des_product_brand_id' => 'Brand ID',
    'des_product_images' => 'Product image path' . PHP_EOL . 'For example: catalog/demo/product/test.jpg,catalog/demo/product/test1.jpg',
    'des_product_video' => 'Product video path',
    'des_product_weight' => 'Weight',
    'des_product_weight_class' => 'Weight unit' . PHP_EOL . '(Optional units: kg/g/oz/lb)',
    'des_product_relateds' => 'Related products, product ID separated by commas\',\'',
    'des_product_position' => 'Product sorting, fill in integers',
    'des_product_active' => 'Product status' . PHP_EOL . '(0 for disabled, 1 for enabled)',
    'des_product_name' => 'Product name, enter the product name in the corresponding language',
    'des_product_content' => 'Product details, enter the product description in the corresponding language',
    'des_product_meta_title' => 'Product META TITLE, enter the META TITLE in the corresponding language',
    'des_product_meta_description' => 'Product META DESCRIPTION, enter the META DESCRIPTION in the corresponding language',
    'des_product_meta_keywords' => 'Product META KEYWORD, enter the META KEYWORD in the corresponding language',

    'des_variants_product_id' => 'Corresponding to the \'Product ID / SPU\' in the product table',
    'des_variants_name' => 'Specification name, fill in the specification name in the corresponding language',
    'des_variants_value' => 'Specification value, fill in the specification value name in the corresponding language',
    'des_variants_image' => 'Image path, the image path corresponding to the specification value, fill in if there is',
    'des_product_skus_product_id' => 'Corresponding to the \'Product ID / SPU\' in the product table',
    'des_product_skus_variants' => 'Specification',
    'des_product_skus_images' => 'Specification product image path, the format is the same as the product image',
    'des_product_skus_model' => 'Model',
    'des_product_skus_sku' => 'SKU',
    'des_product_skus_weight' => 'Weight',
    'des_product_skus_price' => 'Price',
    'des_product_skus_origin_price' => 'Original price',
    'des_product_skus_cost_price' => 'Cost price',
    'des_product_skus_quantity' => 'Quantity',
    'des_product_attribute_product_id' => 'Corresponding to \'Product ID / SPU\' in the product table',
    'des_product_attribute_attribute_group_id'=> 'Product attribute group ID, the attribute group needs to be created in the system in advance',
    'des_product_attribute_attribute_name' => 'Attribute name, fill in the attribute name of the corresponding language',
    'des_product_attribute_value_name' => 'Attribute value, fill in the attribute value name of the corresponding language',
];
