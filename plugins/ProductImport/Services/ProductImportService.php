<?php
/**
 * ProductImportService.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2023-02-24 16:09:21
 * @modified   2023-02-24 16:09:21
 */

namespace Plugin\ProductImport\Services;

use Beike\Admin\Repositories\AttributeRepo;
use Beike\Admin\Services\ProductService;
use Beike\Models\AttributeDescription;
use Beike\Models\AttributeValueDescription;
use Beike\Models\Product;
use Beike\Models\ProductAttribute;
use Beike\Models\ProductDescription;
use Beike\Models\ProductSku;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Plugin\ProductImport\Exceptions\SkuVariantInvalidException;

class ProductImportService
{
    /**
     * 格式要求：
     * 1、每个模板的第一个sheet必须为主表，第二个sheet为主表对应的描述表，其他sheet为附表，key必须为不含前缀的表名
     * 2、每个sheet的第一个字段必须主键（主表）或外键（附表）
     * 3、每个字段的code必须与数据库字段名称一致
     * 4、每个sheet的key必须与创建方法中使用的key一致
     * 5、如果主表涉及多语言的话，第二个表为主表的描述表
     * @var array
     */
    protected array $template_product = [
        'product'           => [
            ['code' => 'id', 'name' => 'Product ID / SPU', 'cell_width' => 8, 'require' => true, 'multi_language' => false],
            ['code' => 'categories', 'name' => 'Categories', 'cell_width' => 8, 'require' => false, 'multi_language' => false, 'type' => 'comma-string'],
            ['code' => 'brand_id', 'name' => 'Brand ID', 'cell_width' => 8, 'require' => false, 'multi_language' => false, 'type' => 'int'],
            ['code' => 'images', 'name' => 'Images', 'cell_width' => 28, 'require' => false, 'multi_language' => false, 'type' => 'json'],
            ['code' => 'video', 'name' => 'Video', 'cell_width' => 28, 'require' => false, 'multi_language' => false],
            ['code' => 'weight', 'name' => 'Weight', 'cell_width' => 15, 'require' => false, 'multi_language' => false],
            ['code' => 'weight_class', 'name' => 'Weight Unit', 'cell_width' => 8, 'require' => false, 'multi_language' => false],
            ['code' => 'relateds', 'name' => 'Related IDs', 'cell_width' => 8, 'require' => false, 'multi_language' => false, 'type' => 'comma-string'],
            ['code' => 'position', 'name' => 'Position', 'cell_width' => 8, 'require' => false, 'multi_language' => false, 'type' => 'int'],
            ['code' => 'active', 'name' => 'Active', 'cell_width' => 8, 'require' => false, 'multi_language' => false, 'type' => 'int'],
            ['code' => 'name', 'name' => 'Name', 'cell_width' => 28, 'require' => true, 'multi_language' => true, 'type' => 'html'],
            ['code' => 'content', 'name' => 'Content', 'cell_width' => 38, 'require' => false, 'multi_language' => true, 'type' => 'html'],
            ['code' => 'meta_title', 'name' => 'Meta Title', 'cell_width' => 18, 'require' => false, 'multi_language' => true, 'type' => 'html'],
            ['code' => 'meta_description', 'name' => 'Meta Description', 'cell_width' => 18, 'require' => false, 'multi_language' => true, 'type' => 'html'],
            ['code' => 'meta_keywords', 'name' => 'Meta Keyword', 'cell_width' => 18, 'require' => false, 'multi_language' => true, 'type' => 'html'],
        ],
        'variants'          => [
            ['code' => 'product_id', 'name' => 'Product ID / SPU', 'cell_width' => 8, 'require' => true, 'multi_language' => false],
            ['code' => 'name', 'name' => 'Name', 'cell_width' => 12, 'require' => true, 'multi_language' => true],
            ['code' => 'value', 'name' => 'Value', 'cell_width' => 12, 'require' => true, 'multi_language' => true],
            ['code' => 'image', 'name' => 'Image', 'cell_width' => 20, 'require' => false, 'multi_language' => false],
        ],
        'product_skus'      => [
            ['code' => 'product_id', 'name' => 'Product ID / SPU', 'cell_width' => 8, 'require' => true, 'multi_language' => false],
            ['code' => 'variants', 'name' => 'Variants', 'cell_width' => 28, 'require' => false, 'multi_language' => false],
            ['code' => 'images', 'name' => 'Images', 'cell_width' => 15, 'require' => false, 'multi_language' => false, 'type' => 'json'],
            ['code' => 'model', 'name' => 'Model', 'cell_width' => 12, 'require' => false, 'multi_language' => false],
            ['code' => 'sku', 'name' => 'SKU', 'cell_width' => 12, 'require' => false, 'multi_language' => false],
            ['code' => 'price', 'name' => 'Price', 'cell_width' => 8, 'require' => false, 'multi_language' => false],
            ['code' => 'origin_price', 'name' => 'Origin Price', 'cell_width' => 8, 'require' => false, 'multi_language' => false],
            ['code' => 'cost_price', 'name' => 'Cost Price', 'cell_width' => 8, 'require' => false, 'multi_language' => false],
            ['code' => 'quantity', 'name' => 'Quantity', 'cell_width' => 8, 'require' => false, 'multi_language' => false],
        ],
        'product_attribute' => [
            ['code' => 'product_id', 'name' => 'Product ID / SPU', 'cell_width' => 8, 'require' => true, 'multi_language' => false],
            ['code' => 'attribute_group_id', 'name' => 'Attribute ID', 'cell_width' => 8, 'require' => false, 'multi_language' => false],
            ['code' => 'attribute_name', 'name' => 'Attribute Name', 'cell_width' => 28, 'require' => true, 'multi_language' => true],
            ['code' => 'value_name', 'name' => 'Attribute Value', 'cell_width' => 28, 'require' => true, 'multi_language' => true],
        ],
    ];

    private int $data_start_line = 3; // 正式数据从第几行开始

    private array $languages = ['en', 'zh_cn'];

    private string $defaultLanguage = 'en';

    private int $count_one_time = 10; // 一次请求处理的商品数量

    private int $min_product_id = 0;

    private int $max_product_id = 0;

    private string $export_way = 'pid';

    private int $count_prepage = 10;

    private array $productIdsExport = [];

    private int $page;

    public function __construct()
    {
        if (version_compare(config('beike.version'), '1.6.0') >= 0) {
            $this->template_product['product_skus'][] = ['code' => 'weight', 'name' => 'Weight', 'cell_width' => 10, 'require' => false, 'multi_language' => false];
        }

        $this->languages = array_column(locales(), 'code');
        if (!in_array($this->defaultLanguage, $this->languages)) {
            $this->defaultLanguage = system_setting('base.locale');
        }
        foreach ($this->template_product as &$columns) {
            foreach ($columns as $field => $column) {
                if (! $column['multi_language']) {
                    continue;
                }
                foreach ($this->languages as $language_code) {
                    $new_column = $column;
                    $new_column['code'] .= ' ' . $language_code;
                    $new_column['name'] .= ' ' . $language_code;
                    $columns[] = $new_column;
                }
                unset($columns[$field]);
            }
        }
    }

    public static function getInstance(): self
    {
        return new self;
    }

    public function getCell($worksheet, $row, $col, $default_val = '')
    {
        $row += 1; // we use 0-based, PHPExcel used 1-based row index

        return ($worksheet->cellExistsByColumnAndRow($col, $row)) ? $worksheet->getCellByColumnAndRow($col, $row)->getValue() : $default_val;
    }

    /**
     * @throws \Exception
     */
    public function formatData($data, $template, $table_name): array
    {
        $columns = $template[$table_name];
        $header  = array_shift($data); // 第一行是表头
        $diff    = array_diff(array_column($columns, 'name'), $header);
        if ($diff) {
            // throw new \Exception(sprintf(trans('ProductImport::common.error_header_need_columns'), $table_name, implode(',', $diff)));  // excel表缺少字段$diff
        }

        // 跳过表头和正问之间的描述和demo行
        for ($i = 2; $i < $this->data_start_line; $i++) {
            array_shift($data);
        }

        $result         = [];
        $name_code_maps = array_column($columns, null, 'name');
        foreach ($data as $line) {
            //$line = array_combine($map, $line);
            $row = [];
            foreach ($line as $key => $cell) {
                if (isset($name_code_maps[$header[$key]]['code'])) {
                    if (($name_code_maps[$header[$key]]['type'] ?? '') == 'html') {
                        $row[$name_code_maps[$header[$key]]['code']] = $cell;
                    } elseif (($name_code_maps[$header[$key]]['type'] ?? '') == 'json') {
                        $values = $cell ? explode(',', $cell) : [];
                        $values = array_map(function ($item) {
                            return trim($item);
                        }, $values);
                        $row[$name_code_maps[$header[$key]]['code']] = ! $cell ? [] : $values;
                    } elseif (($name_code_maps[$header[$key]]['type'] ?? '') == 'int') {
                        $row[$name_code_maps[$header[$key]]['code']] = (int) ($cell ?: 0);
                    } elseif (($name_code_maps[$header[$key]]['type'] ?? '') == 'comma-string') {
                        $row[$name_code_maps[$header[$key]]['code']] = ! $cell ? [] : array_map(function ($item) {
                            return trim($item);
                        }, explode(',', str_replace('，', ',', $cell)));
                    } else {
                        $row[$name_code_maps[$header[$key]]['code']] = $cell;
                    }
                }
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * @throws Exception|\Exception
     */
    public function validate($datas, $type)
    {
        foreach ($datas as $table => $data) {
            $columns        = $this->{'template_' . $type}[$table];
            $required_map   = array_column($columns, 'require', 'code');
            $field_name_map = array_column($columns, 'name', 'code');

            foreach ($data as $index => $item) {
                foreach ($item as $code => $value) {
                    if ($required_map[$code] && $value === '') { // 如果必填且$value为空
                        throw new \Exception(sprintf(trans('ProductImport::common.error_filed_required'), $table, $index + $this->data_start_line, $field_name_map[$code])); // $index为0的数据实际上是excel第3行
                    }
                }
            }
        }
    }

    public function validateServiceLogic($data): void
    {
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function download($post = [])
    {
        // set appropriate timeout limit
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        if (isset($post['export_way'])) {
            $this->export_way = $post['export_way'];
            if ($post['export_way'] == 'pid') {
                $this->min_product_id = (int) $post['min'] ?? 0;
                $this->max_product_id = (int) $post['max'] ?? 0;
            } elseif ($post['export_way'] == 'page') {
                $this->count_prepage = (int) $post['min'] ?: 10;
                $this->page          = (int) $post['max'] ?: 1;
            } else {
                echo 'export_way error!';
            }
        }

        // create a new workbook
        $workbook = new Spreadsheet();

        // set default font name and size
        $workbook->getDefaultStyle()->getFont()->setName('Arial');
        $workbook->getDefaultStyle()->getFont()->setSize(10);
        $workbook->getDefaultStyle()->getAlignment()->setIndent(1);

        // creating the worksheet
        $i = 0;
        foreach ($this->{'template_product'} as $table => $columns) {
            $func      = $this->convertUnderline('get_' . $table);
            $sheetData = $this->{$func}();
            if ($i > 0) {
                $workbook->createSheet();
            }
            $workbook->setActiveSheetIndex($i++);
            $worksheet = $workbook->getActiveSheet();
            $worksheet->setTitle($table);
            $this->populateWorksheet($worksheet, $sheetData, $columns, $table);
            $worksheet->freezePane('B3');
        }

        $workbook->setActiveSheetIndex(0);


        // 创建一个 Xlsx 文件写入器
        return response()->streamDownload(function() use ($workbook) {
            $writer = new Xlsx($workbook);
            if (ob_get_length()) {
                ob_clean();
            }
            $writer->save('php://output');
        }, 'Product_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * @param $worksheet
     * @param $data , 要写入工作表的数据， 每个记录中的字段顺序必须和$template一致
     * @param $columns
     * @param $tableName
     */
    private function populateWorksheet($worksheet, $data, $columns, $tableName): void
    {
        if (! $data) {
            $data = [];
        }
        $dataColumns = array_keys(reset($data) ?: []);
        $columns     = $this->getColumnsContains($columns, $dataColumns);

        // Set the column widths
        $j = 1;
        foreach ($columns as $item) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth($item['cell_width']);
        }

        // The heading row
        $i = 1;
        $j = 1;
        foreach ($columns as $item) {
            $this->setCell($worksheet, $i, $j++, $item['name']);
        }
        $i++;
        $j = 1;
        foreach ($columns as $item) {
            $code          = $this->getCodeExcludeLanguage($item['code']);
            $text_required = $item['require'] ? trans('ProductImport::common.text_required') : '';
            $this->setCell($worksheet, $i, $j++, $text_required . trans('ProductImport::common.des_' . $tableName . '_' . $code));
        }
        $worksheet->getRowDimension($i)->setRowHeight(30);

        // The actual product discounts data
        $i++;
        foreach ($data as $row) {
            $worksheet->getRowDimension($i)->setRowHeight(13);
            $j = 1;
            foreach ($columns as $item) {
                $cell_value = $row[$item['code']] ?? '';
                if (isset($item['type'])) {
                    if ($item['type'] == 'html') {
                        $cell_value = $cell_value;
                    } elseif ($item['type'] == 'json') {
                        $cell_value = $cell_value ? implode(',', $cell_value) : '';
                    }
                }
                $this->setCell($worksheet, $i, $j++, $cell_value);
            }
            $i++;
        }
    }

    private function getColumnsContains($columns, $contains): array
    {
        $result = [];

        foreach ($columns as $column) {
            if (in_array($column['code'], $contains) || !$contains) {
                $result[] = $column;
            }
        }

        return $result;
    }

    /**
     * @param $code
     * @return string
     */
    private function getCodeExcludeLanguage(string $code): string
    {
        $code = trim($code);
        foreach ($this->languages as $language) {
            if (substr($code, -strlen($language)) === $language) {
                $code = substr($code, 0, -strlen($language));
            }
        }

        return trim($code);
    }

    private function setCell($worksheet, $row/*1-based*/, $col/*0-based*/, $val): void
    {
        $sel_index = $this->IntToChr($col) . $row;
        $worksheet->setCellValueExplicit($sel_index, $val, DataType::TYPE_STRING);
    }

    private function IntToChr($index): string
    {
        $index--;
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= $this->IntToChr(floor($index / 26));
        }

        return $str . chr($index % 26 + 65);
    }

    /**
     * @param $file_path
     * @return float
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     * @throws \Exception
     */
    public function upload($file_path): float
    {
        // parse uploaded spreadsheet file
        $inputFileType = IOFactory::identify($file_path);
        $objReader     = IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $reader = $objReader->load($file_path);

        $data = [];
        $i    = 0;
        foreach ($this->template_product as $index => $item) {
            $data[$index] = $this->getSheet($reader, $index, $i);
            $i++;
        }
        $this->validate($data, 'product');
        $this->validateServiceLogic($data);
        $data = $this->refactorData($data);
        $data = $this->perfectData($data, 'product');
        // dd($data);
        cache()->set($this->getCacheKey(), $data);

        return ceil(count($data) / $this->count_one_time);
    }

    /**
     * 将数据重组为调用model层原生方法所需的结构
     * @param $data
     * @return array
     */
    public function refactorData($data): array
    {
        foreach ($data as &$items) {
            foreach ($items as $i => $item) {
                foreach ($item as $field => $value) {
                    $lang_code = substr($field, strrpos($field, ' ') + 1);
                    if (in_array($lang_code, $this->languages)) {
                        $items[$i]['descriptions'][$lang_code][substr($field, 0, strrpos($field, ' '))] = $value;
                        unset($items[$i][$field]);
                    }
                }
            }
        }

        $result          = array_shift($data);
        $primary_id_name = 'id';
        $result          = array_column($result, null, $primary_id_name);

        unset($items);
        foreach ($data as $table => $items) {
            foreach ($items as $item) {
                if (! isset($result[$item['product_id']])) { // 主表都没有数据则忽略其他表的改商品数据
                    continue;
                }
                $result[$item['product_id']][$table][] = $item;
            }
        }

        return $result;
    }

    /**
     * 将数据补充其他必要数据，比如date_added,date_modify等调用创建函数必须要，但是excel导入没有提供的字段。使数据达到可以调用model层创建方法的标准
     * @param $data
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function perfectData($data, $type): array
    {
        $result = [];
        switch ($type) {
            case 'product':
                foreach ($data as $item) {
                    if (! isset($item['variants']) || ! $item['variants']) {
                        $variants = [];
                    } else {
                        $variants = $this->analysisVariants($item['variants']);
                    }
                    $item['variables'] = json_encode($variants);

                    $first        = true;
                    $productSkus  = $item['product_skus'] ?? [];
                    $item['skus'] = [];
                    foreach ($productSkus as $index => $product_skus) {
                        $item['skus'][$index]               = $product_skus;
                        $item['skus'][$index]['is_default'] = (bool) $first;
                        $first                              = false;

                        try {
                            $item['skus'][$index]['variants'] = empty($product_skus['variants']) ? '' : $this->analysisVariantStr($product_skus['variants'], $variants);
                        } catch (SkuVariantInvalidException $e) {
                            throw new \Exception(trans('ProductImport::common.error_variant', ['id' => $item['id']]) . $e->getMessage());
                        }
                    }
                    if (!$item['skus']) {
                        throw new \Exception(trans('ProductImport::common.error_skus_required', ['id' => $item['id']]));
                    }
                    unset($item['product_skus']);

                    if (isset($item['product_attribute']) && $item['product_attribute']) {
                        $item['attributes'] = array_map(function ($item) {
                            // 根据传入参数["zh_cn" => ["attribute_name" => "处理器", "value_name" => "酷睿I5"], "en" => ["attribute_name" => "CPU", "value_name" => "Core I5"]， 获取
                            //(如没有则创建)对应的属性ID和属性值ID，返回['attribute_id' => 3, 'attribute_value_id' => 34]
                            $attributeAndValueIds = $this->getOrCreateAttributeAndValueId($item);

                            return [
                                'attribute_id'       => $attributeAndValueIds['attribute_id'],
                                'attribute_value_id' => $attributeAndValueIds['attribute_value_id'],
                            ];
                        }, $item['product_attribute']);
                        unset($item['product_attribute']);
                    }

                    $result[] = $item;
                }
        }

        return $result;
    }

    /**
    /* 根据传入参数["zh_cn" => ["attribute_name" => "处理器", "value_name" => "酷睿I5"], "en" => ["attribute_name" => "CPU", "value_name" => "Core I5"]， 获取(如没有则创建)对应的
     * 属性ID和属性值ID，返回['attribute_id' => 3, 'attribute_value_id' => 34]
     * @param $data
     * @return array
     */
    public function getOrCreateAttributeAndValueId($data): array
    {
        $attributeGroupId = $data['attribute_group_id'] ?? 1;
        $data = $data['descriptions'];
        $locale                    = $this->defaultLanguage;
        $attributeCode             = $data[$locale]['attribute_name'];
        $valueCode                 = $data[$locale]['value_name'];
        $mapAttributeNameToId      = $this->getMapIdToNameForAttribute();
        $mapAttributeValueNameToId = $this->getMapIdToNameForAttributeValue();

        if (! isset($mapAttributeNameToId[$attributeCode])) {
            $name = [];
            foreach ($data as $lang => $des) {
                $name[$lang] = $des['attribute_name'];
            }

            $attribute = AttributeRepo::create([
                'attribute_group_id' => $attributeGroupId,
                'sort_order'         => 0,
                'name'               => $name,
            ]);
            $mapAttributeNameToId[$attributeCode] = $attribute->id;
        }
        $valueCode = $mapAttributeNameToId[$attributeCode] . '-' . $valueCode;
        if (! isset($mapAttributeValueNameToId[$valueCode])) {
            $name = [];
            foreach ($data as $lang => $des) {
                $name[$lang] = $des['value_name'];
            }

            $attributeValue = AttributeRepo::createValue([
                'attribute_id' => $mapAttributeNameToId[$attributeCode],
                'name'         => $name,
            ]);
            $mapAttributeValueNameToId[$valueCode] = $mapAttributeNameToId[$attributeCode] . '-' . $attributeValue->id;
        }
        $attributeValueIdStr = $mapAttributeValueNameToId[$valueCode];

        return [
            'attribute_id'       => $mapAttributeNameToId[$attributeCode],
            'attribute_value_id' => substr($attributeValueIdStr, stripos($attributeValueIdStr, '-') + 1),
        ];
    }

    /**
     * @param $variantsStr --要解析的字符串，格式："Size:S;Color:Green"
     * @param $variants --规格基础数据，格式：[['name'=>['en'=>'Size','zh_cn'=>'尺码'],['values'=>[['image'=>'','name'=>['en'=>'M','zh_cn'=>'M']],[...]]]],[...]]
     * @return array --product_sku表的variants存储的数据格式，格式：[0,1]
     * @throws \Exception
     */
    public function analysisVariantStr($variantsStr, $variants): array
    {
        $mapVariantIndex = []; // 存储某个类型在变量$variants中的索引
        $mapValueIndex   = []; // 存储某个类型值在变量$variants中的索引
        foreach ($variants as $index => $variant) {
            $mapVariantIndex[$variant['name'][$this->defaultLanguage]] = $index;
            $mapValueIndex[$variant['name'][$this->defaultLanguage]]   = [];
            foreach ($variant['values'] as $index2 => $value) {
                $mapValueIndex[$variant['name'][$this->defaultLanguage]][$value['name'][$this->defaultLanguage]] = $index2;
            }
        }

        $result      = [];
        $variantsArr = explode(';', $variantsStr);
        foreach ($variantsArr as $variant) {
            $variant = trim($variant);
            $variant = explode(':', $variant);
            if (count($variant) != 2) {
                throw new \Exception(trans('ProductImport::common.error_product_skus_variants', ['variants' => $variantsStr]));
            }
            if (! isset($mapValueIndex[$variant[0]][$variant[1]])) {
                throw new SkuVariantInvalidException(trans('ProductImport::common.error_variant_math', ['variant_str' => $variantsStr]));
            }
            $result[$mapVariantIndex[$variant[0]]] = $mapValueIndex[$variant[0]][$variant[1]];
        }

        return $result;
    }

    /**
     * @param $variants
     * @return array
     */
    public function analysisVariants($variants): array
    {
        /* 输出格式：
            [
                '颜色' => [
                    'name'   => [
                        2 => '颜色',
                        3 => 'color'
                    ],
                    'values' => [
                        [
                            'image' => 'red.jpg',
                            'name' => [
                                2 => '红色',
                                3 => 'red'
                            ]
                        ],
                        [
                            'image' => 'green.jpg',
                            'name' => [
                                2 => '绿色',
                                3 => 'green'
                            ]
                        ]
                    ],
                ],
                [],
                []
            ];
        */
        $result = [];
        foreach ($variants as $variant) {
            $description = $variant['descriptions'];
            $variantCode = $description[$this->defaultLanguage]['name'];
            $valueCode   = $description[$this->defaultLanguage]['value'];
            $name        = [];
            $valueName   = [];
            foreach ($description as $languageId => $item) {
                $name[$languageId]      = $item['name']  ?? '';
                $valueName[$languageId] = $item['value'] ?? '';
            }
            $value = [
                'image' => $variant['image'],
                'name'  => $valueName,
            ];
            if (! isset($result[$variantCode])) {
                $result[$variantCode] = [
                    'name'   => $name,
                    'values' => [
                        $valueCode => $value,
                    ],
                ];
            } else {
                $result[$variantCode]['values'][] = $value;
            }
        }

        $result = array_map(function ($item) {
            $item['values'] = array_values($item['values']);

            return $item;
        }, $result);

        return array_values($result);
    }

    public function import($page): void
    {
        $data = cache()->get($this->getCacheKey());
        // dd($data);
        $data = array_slice($data, $this->count_one_time * ($page - 1), $this->count_one_time, true);

        $ids       = array_column($data, 'id');
        $ids_exist = Product::query()->whereIn('id', $ids)->pluck('id')->toArray();

        foreach ($data as $item) {
            if (function_exists('is_seller') && is_seller()) {
                $item['skus'] = array_map(function ($item) {
                    $item['sku'] = 'S' . current_seller_user()->seller_id . '-' . $item['sku'];
                    return $item;
                }, $item['skus']);
            }

            if (function_exists('is_seller') && is_seller()) {
                $serviceClass = 'Beike\Seller\Services\ProductService';
            } else {
                $serviceClass = 'Beike\Admin\Services\ProductService';
            }

            if (! is_numeric($item['id']) || ! in_array($item['id'], $ids_exist)) {
                (new $serviceClass())->create($item);
            } else {
                (new $serviceClass())->update(Product::find($item['id']), $item);
            }
        }
    }

    private function getCacheKey()
    {
        if (function_exists('is_seller') && is_seller()) {
            return 'gd_import_seller' . current_seller_user()->id;
        }
        return 'gd_import' . current_user()->id;
    }

    /**
     * @param $reader
     * @param $table_index
     * @param $index // 从0开始，0为第一个sheet
     * @param bool $transpose
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    private function getSheet($reader, $table_index, $index, bool $transpose = false): array
    {
        if ($reader->getSheetCount() <= $index) {
            // throw new \Exception(trans('ProductImport::common.error_sheet_not_exist'));
        }

        $data      = $reader->getSheetByName($table_index);
        if (! $data) {
            return [];
        }
        $result    = [];
        $row_count = $data->getHighestRow();
        $col_count = Coordinate::columnIndexFromString($data->getHighestColumn());
        if ($transpose) {
            for ($j = 1; $j <= $col_count; $j++) {
                $row = [];
                for ($i = 0; $i < $row_count; $i++) {
                    $cell_value = trim($this->getCell($data, $i, $j));
                    $row[]      = $cell_value;
                }
                $result[] = $row;
            }
        } else {
            for ($i = 0; $i < $row_count; $i++) {
                $row = [];
                for ($j = 1; $j <= $col_count; $j++) {
                    $cell_value = trim($this->getCell($data, $i, $j));
                    $row[]      = $cell_value;
                }
                $result[] = $row;
            }
        }

        $result = $this->utf8($result);
        $result = $this->formatData($result, $this->template_product, $table_index);

        return $result;
    }

    private function getProduct(): array
    {
        $builder = Product::query();
        if (function_exists('is_seller') && is_seller()) {
            $builder->where('seller_id', current_seller_user()->seller_id);
        }
        $builder->leftJoin('product_categories as pc', 'pc.product_id', 'products.id')
            ->leftJoin('categories', 'pc.category_id', 'categories.id')
            ->leftJoin('product_relations as pr', 'pr.product_id', 'products.id')
            ->groupBy('products.id');

        $categoriesSql = 'GROUP_CONCAT( DISTINCT CAST(pc.category_id AS CHAR(11)) SEPARATOR "," )';
        $relationsSql = 'GROUP_CONCAT( DISTINCT CAST(pr.relation_id AS CHAR(11)) SEPARATOR "," )';
        // 如果定义了函数getDBDriver，且数据库类型时不是mysql，则按postgreSql的语法重新定义&categoriesSql和$relationsSql
        if (function_exists('getDBDriver') && getDBDriver() != 'mysql') {
            $categoriesSql = 'STRING_AGG( DISTINCT CAST(pc.category_id AS CHAR(11)), \',\' )';
            $relationsSql = 'STRING_AGG( DISTINCT CAST(pr.relation_id AS CHAR(11)), \',\' )';
        }
        if (version_compare(config('beike.version'), '1.3.5', '>=')) {
            $builder->select(['products.id', DB::raw($categoriesSql . ' AS categories'), 'products.brand_id', 'products.images', 'products.video', DB::raw($relationsSql . ' AS relations'), 'products.position', 'products.weight', 'products.weight_class', 'products.active']);
        } else {
            $builder->select(['products.id', DB::raw($categoriesSql . ' AS categories'), 'products.brand_id', 'products.images', 'products.video', DB::raw($relationsSql . ' AS relations'), 'products.position', 'products.active']);
        }
        if ($this->export_way == 'pid') {
            $builder->whereBetween('products.id', [$this->min_product_id, $this->max_product_id]);
        } elseif ($this->export_way == 'page') {
            $builder->limit($this->count_prepage)->offset(($this->page - 1) * $this->count_prepage);
        }
        $products = $builder->get()->toArray();
        if (! $products) {
            throw new \Exception(trans('ProductImport::common.error_no_results'));
        }
        $this->productIdsExport = array_column($products, 'id');

        return $this->flattenDescriptions($products, $this->getProductDescription());
    }

    private function getProductDescription(): array
    {
        $results = [];
        foreach (array_chunk($this->productIdsExport, 2000) as $chunk) {
            $chunkResults = ProductDescription::query()
                ->whereIn('locale', $this->languages)
                ->whereIn('product_id', $chunk)
                ->orderBy('product_id')
                ->get()
                ->toArray();

            foreach ($chunkResults as $result) {
                $results[] = $result;
            }
        }
        return $results;
    }

    private function getVariants(): array
    {
        $variants = [];
        foreach (array_chunk($this->productIdsExport, 2000) as $chunk) {
            $chunkResults = Product::query()->select('id', 'variables')->whereIn('products.id', $chunk)->get()->toArray();
            foreach ($chunkResults as $result) {
                $variants[] = $result;
            }
        }

        $result = [];
        foreach ($variants as $variant) {
            $variables = $variant['variables'] ?? [];
            foreach ($variables as $variable) {
                foreach ($variable['values'] as $value) {
                    $item = [
                        'product_id' => $variant['id'],
                        'image'      => $value['image'],
                    ];
                    foreach ($variable['name'] as $lang => $name) {
                        $item["name $lang"] = $name;
                    }
                    foreach ($value['name'] as $lang => $name) {
                        $item["value $lang"] = $name;
                    }

                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    private function getProductSkus(): array
    {
        $skus = [];
        foreach (array_chunk($this->productIdsExport, 2000) as $chunk) {
            $chunkResults = ProductSku::query()->whereIn('product_id', $chunk)->get()->toArray();
            foreach ($chunkResults as $result) {
                $skus[] = $result;
            }
        }

        $mapVariantIndexToLabel = $this->getProductVariantIndexToLabelMap();
        $skus                   = array_map(function ($item) use ($mapVariantIndexToLabel) {
            $item['variants']   = $item['variants'] ?: '';
            if (! $item['variants']) {
                return $item;
            }
            $variant          = $mapVariantIndexToLabel[$item['product_id']][implode('-', $item['variants'])] ?? '';
            $item['variants'] = $variant;

            return $item;
        }, $skus);

        if (function_exists('is_seller') && is_seller()) {
            $skus = array_map(function ($o) {
                $o['sku'] = substr($o['sku'], strlen('S' . current_seller_user()->seller_id . '-'));
                return $o;
            }, $skus);
        }

        return $skus;
    }

    private function getProductVariantIndexToLabelMap(): array
    {
        $variables = [];
        foreach (array_chunk($this->productIdsExport, 2000) as $chunk) {
            $chunkResults = Product::query()->select('id', 'variables')->whereIn('products.id', $chunk)->get()->toArray();
            foreach ($chunkResults as $result) {
                $variables[] = $result;
            }
        }

        $map = [];
        foreach ($variables as $variant) {
            $map[$variant['id']] = $this->getVariantIndexToLabelMapByProduct($variant['variables'] ?? []);
        }

        return $map;
    }

    private function getVariantIndexToLabelMapByProduct($variables): array
    {
        if (! $variables) {
            return [];
        }
        $firstVariable = array_shift($variables);

        return $this->getVariantIndexToLabelMap($firstVariable, $variables);
    }

    private function getVariantIndexToLabelMap($variable, $variables, $map = []): array
    {
        $locale = $this->defaultLanguage;
        if (count($variables) > 1) {
            $firstVariable = array_shift($variables);

            $map = $this->getVariantIndexToLabelMap($firstVariable, $variables);

            $newMap = [];
            foreach ($variable['values'] as $indexValue => $value) {
                foreach ($map as $mapKey => $mapValue) {
                    $newMap["$indexValue-$mapKey"] = $variable['name'][$locale] . ':' . $value['name'][$locale] . ';' . $mapValue;
                }
            }

            return $newMap;
        }

        if (! $variables) {
            foreach ($variable['values'] as $indexValue => $value) {
                    $map["$indexValue"] = $variable['name'][$locale] . ':' . $value['name'][$locale];
            }
        } else {
            $variable1 = array_shift($variables);
            foreach ($variable['values'] as $indexValue => $value) {
                foreach ($variable1['values'] as $indexValue1 => $value1) {
                    $map["$indexValue-$indexValue1"] = $variable['name'][$locale] . ':' . $value['name'][$locale] . ';' . $variable1['name'][$locale] . ':' . $value1['name'][$locale];
                }
            }
        }

        return $map;
    }

    private function getProductAttribute(): array
    {
        $productAttributes = [];
        foreach (array_chunk($this->productIdsExport, 2000) as $chunk) {
            $chunkResults = ProductAttribute::query()->with(['attribute.descriptions', 'attributeValue.descriptions'])->whereIn('product_id', $chunk)->get();
            foreach ($chunkResults as $result) {
                $productAttributes[] = $result;
            }
        }

        $result = [];
        foreach ($productAttributes as $productAttribute) {
            $item = [
                'product_id' => $productAttribute['product_id'],
                'attribute_group_id' => $productAttribute->attribute->attribute_group_id,
            ];
            foreach ($productAttribute->attribute->descriptions as $productAttributeDescription) {
                $locale                         = $productAttributeDescription['locale'];
                $item["attribute_name $locale"] = $productAttributeDescription['name'];
            }
            foreach ($productAttribute->attributeValue->descriptions as $productAttributeValueDescription) {
                $locale                     = $productAttributeValueDescription['locale'];
                $item["value_name $locale"] = $productAttributeValueDescription['name'];
            }
            $result[] = $item;
        }

        return $result;
    }

    private function getMapIdToNameForAttribute(): array
    {
        $attributes = AttributeDescription::query()->where('locale', $this->defaultLanguage)->select(['attribute_id', 'name'])->get()->toArray();

        return array_column($attributes, 'attribute_id', 'name');
    }

    private function getMapIdToNameForAttributeValue(): array
    {
        $attributeValues = AttributeValueDescription::query()->from('attribute_value_descriptions', 'avd')
            ->leftJoin('attribute_values as av', 'av.id', 'avd.attribute_value_id')
            ->where('locale', $this->defaultLanguage)
            ->select(['attribute_id', 'attribute_value_id', 'name'])
            ->get()
            ->map(function ($item) {
                $item->attribute_value_index = $item->attribute_id . '-' . $item->attribute_value_id;
                $item->name                  = $item->attribute_id . '-' . $item->name;

                return $item;
            })
            ->toArray();

        return array_column($attributeValues, 'attribute_value_index', 'name');
    }

    /**
     * @param $items , 该参数第一列必须为主键
     * @param $descriptions
     * @return array
     */
    private function flattenDescriptions($items, $descriptions): array
    {
        if (! $items) {
            return [];
        }

        $items = array_column($items, null, 'id');

        foreach ($descriptions as $description) {
            $id = $description['product_id'];
            if (! in_array($description['locale'], $this->languages)) {
                continue;
            }
            $langCode = $description['locale'];
            unset($description['locale']);
            foreach ($description as $key => $value) {
                if (isset($items[$id])) {
                    $items[$id][$key . ' ' . $langCode] = $value;
                }
            }
        }

        return $items;
    }

    private function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);

        return $str;
    }

    private function utf8($data)
    {
        if (is_array($data)) {
            foreach ($data as $item) {
                $this->utf8($item);
            }

        } else {
            $encode = mb_detect_encoding($data, ['GB2312', 'GBK', 'BIG5', 'ASCII', 'UTF-8']);
            if ($encode != 'UTF-8') {
                $data = mb_convert_encoding($data, 'UTF-8', $encode);
            }
        }

        return $data;
    }

    public static function getBytes($val)
    {
        $val = trim($val);

        switch (strtolower(substr($val, -1))) {
            case 'm': $val = (int) substr($val, 0, -1) * 1048576;

break;
            case 'k': $val = (int) substr($val, 0, -1) * 1024;

break;
            case 'g': $val = (int) substr($val, 0, -1) * 1073741824;

break;
            case 'b':
                switch (strtolower(substr($val, -2, 1))) {
                    case 'm': $val = (int) substr($val, 0, -2) * 1048576;

break;
                    case 'k': $val = (int) substr($val, 0, -2) * 1024;

break;
                    case 'g': $val = (int) substr($val, 0, -2) * 1073741824;

break;
                    default: break;
                }

break;
            default: break;
        }

        return $val;
    }
}
