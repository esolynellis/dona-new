<?php

namespace Beike\Admin\Http\Controllers;

use Beike\Admin\Http\Requests\ProductRequest;
use Beike\Admin\Http\Resources\ProductAttributeResource;
use Beike\Admin\Http\Resources\ProductResource;
use Beike\Admin\Http\Resources\ProductSimple;
use Beike\Admin\Repositories\TaxClassRepo;
use Beike\Admin\Services\ProductService;
use Beike\Libraries\Weight;
use Beike\Models\Product;
use Beike\Models\Brand;
use Beike\Repositories\CategoryRepo;
use Beike\Repositories\FlattenCategoryRepo;
use Beike\Repositories\LanguageRepo;
use Beike\Repositories\ProductRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected string $defaultRoute = 'products.index';

    public function index(Request $request)
    {
        $requestData    = $request->all();
        if (! isset($requestData['sort'])) {
            $requestData['sort']  = 'products.updated_at';
            $requestData['order'] = 'desc';
        }
        $productList    = ProductRepo::list($requestData);
        $products       = ProductResource::collection($productList);
        $productsFormat =  $products->jsonSerialize();

        session(['page' => $request->get('page', 1)]);

        $data = [
            'categories'      => CategoryRepo::flatten(locale()),
            'products_format' => $productsFormat,
            'products'        => $products,
            'type'            => 'products',
        ];

        $data = hook_filter('admin.product.index.data', $data);

        if ($request->expectsJson()) {
            return $productsFormat;
        }

        return view('admin::pages.products.index', $data);
    }

    public function trashed(Request $request)
    {
        $requestData            = $request->all();
        $requestData['trashed'] = true;
        $productList            = ProductRepo::list($requestData);
        $products               = ProductResource::collection($productList);
        $productsFormat         =  $products->jsonSerialize();

        $data = [
            'categories'      => CategoryRepo::flatten(locale()),
            'products_format' => $productsFormat,
            'products'        => $products,
            'type'            => 'trashed',
        ];

        $data = hook_filter('admin.product.trashed.data', $data);

        if ($request->expectsJson()) {
            return $products;
        }

        return view('admin::pages.products.index', $data);
    }

    public function create(Request $request)
    {
        return $this->form($request, new Product());
    }

    public function store(ProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $requestData = $request->all();
            $actionType  = $requestData['action_type'] ?? '';
            $product     = (new ProductService)->create($requestData);

            $data = [
                'request_data' => $requestData,
                'product'      => $product,
            ];

            hook_action('admin.product.store.after', $data);

            DB::commit();
            return redirect()->to($actionType == 'stay' ? admin_route('products.create') : admin_route('products.index'))->with('success', trans('common.created_success'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect(admin_route('products.create'))
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(Request $request, Product $product)
    {
        return $this->form($request, $product);
    }

    public function update(ProductRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();
            $requestData = $request->all();
            $actionType  = $requestData['action_type'] ?? '';
            $product     = (new ProductService)->update($product, $requestData);

            $data = [
                'request_data' => $requestData,
                'product'      => $product,
            ];
            hook_action('admin.product.update.after', $data);
            $page = session('page', 1);

            DB::commit();
            return redirect()->to($actionType == 'stay' ? admin_route('products.edit', [$product->id]) : admin_route('products.index', ['page' => $page]))->with('success', trans('common.updated_success'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect(admin_route('products.edit', $product))->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, Product $product)
    {
        $product->delete();
        hook_action('admin.product.destroy.after', $product);

        return json_success(trans('common.deleted_success'));
    }

    public function restore(Request $request)
    {
        $productId = $request->id ?? 0;
        Product::withTrashed()->find($productId)->restore();

        hook_action('admin.product.restore.after', $productId);

        return ['success' => true];
    }

    /**
     * @param Request $request
     * @param Product $product
     * @return mixed
     * @throws \Exception
     */
    protected function form(Request $request, Product $product)
    {
        if ($product->id) {
            $descriptions = $product->descriptions->keyBy('locale');
            $categoryIds  = $product->categories->pluck('id')->toArray();
            $product->load('brand', 'attributes');
        }

        $product    = hook_filter('admin.product.form.product', $product);
        $taxClasses = TaxClassRepo::getList();
        array_unshift($taxClasses, ['title' => trans('admin/builder.text_no'), 'id' => 0]);

        $data = [
            'product'               => $product,
            'descriptions'          => $descriptions ?? [],
            'category_ids'          => $categoryIds  ?? [],
            'product_attributes'    => ProductAttributeResource::collection($product->attributes),
            'relations'             => ProductResource::collection($product->relations)->resource,
            'languages'             => LanguageRepo::all(),
            'tax_classes'           => $taxClasses,
            'weight_classes'        => Weight::getWeightUnits(),
            'source'                => [
                'flatten_categories' => FlattenCategoryRepo::getCategoryList(),
                'categories'         => CategoryRepo::flatten(locale(), false),
            ],
            '_redirect'          => $this->getRedirect(),
        ];

        $data = hook_filter('admin.product.form.data', $data);

        return view('admin::pages.products.form.form', $data);
    }

    public function name(int $id)
    {
        $name = ProductRepo::getName($id);

        return json_success(trans('common.get_success'), $name);
    }

    /**
     * 根据商品ID批量获取商品名称
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNames(Request $request): JsonResponse
    {
        $productIds = explode(',', $request->get('product_ids'));
        $name       = ProductRepo::getNames($productIds);

        return json_success(trans('common.get_success'), $name);
    }

    public function autocomplete(Request $request)
    {
        $products = ProductRepo::autocomplete($request->get('name') ?? '');

        return json_success(trans('common.get_success'), $products);
    }

    /**
     * @throws \Exception
     */
    public function latest(Request $request): JsonResponse
    {
        $limit          = $request->get('limit', 10);
        $productList    = ProductRepo::getBuilder(['active' => 1, 'sort' => 'id'])->limit($limit)->get();
        $products       = ProductSimple::collection($productList)->jsonSerialize();

        return json_success(trans('common.get_success'), $products);
    }

    public function updateStatus(Request $request)
    {
        ProductRepo::updateStatusByIds($request->get('ids'), $request->get('status'));

        return json_success(trans('common.updated_success'), []);
    }

    public function destroyByIds(Request $request)
    {
        $productIds = $request->get('ids');
        ProductRepo::DeleteByIds($productIds);

        hook_action('admin.product.destroy_by_ids.after', $productIds);

        return json_success(trans('common.deleted_success'), []);
    }

    public function trashedClear()
    {
        ProductRepo::forceDeleteTrashed();
    }
    /**
     * 导出商品翻译信息
     */
    public function exportTranslations(Request $request)
    {
        $ids = $request->input('ids', []);
        $supportedLocales = ['zh_cn', 'en', 'mn', 'ru']; // 支持的语言

        $query = DB::table('product_descriptions as pd')
            ->leftJoin('products as p', 'pd.product_id', '=', 'p.id')
            ->select(
                'pd.product_id',
                'pd.locale',
                'pd.name',
                'pd.content',
                'pd.meta_title',
                'pd.meta_description',
                'pd.meta_keywords',
                'pd.gunit_max_des',
                'pd.gunit_midd_des',
                'pd.gunit_min_des',
                'pd.min_purchasing_unit_des',
                'p.gunit_max',
                'p.gunit_midd',
                'p.gunit_min',
                DB::raw('(SELECT name FROM product_descriptions WHERE product_id = p.id AND locale = "zh_cn" LIMIT 1) as product_name_zh_cn')
            )
            ->orderBy('pd.product_id')
            ->orderBy('pd.locale');

        if (!empty($ids)) {
            $query->whereIn('pd.product_id', explode(',', $ids));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return back()->with('error', __('admin/product.no_data_to_export'));
        }

        // 按商品ID分组，找出每个商品已有的语言翻译
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row->product_id][] = $row;
        }

        $fileName = 'product_translations_' . date('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($groupedData, $supportedLocales) {
            $file = fopen('php://output', 'w');

            // 添加 BOM 头解决中文乱码
            fwrite($file, "\xEF\xBB\xBF");

            // 表头
            fputcsv($file, [
                'product_id',
                'locale',
                'name',
                'content',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'gunit_max_des',
                'gunit_midd_des',
                'gunit_min_des',
                'min_purchasing_unit_des',
                'product_name_zh_cn',
                'gunit_max',
                'gunit_midd',
                'gunit_min'
            ]);

            // 处理数据，确保每个商品都有所有语言的行
            foreach ($groupedData as $productId => $translations) {
                $existingLocales = array_column($translations, 'locale');

                foreach ($supportedLocales as $locale) {
                    $translation = $this->findTranslation($translations, $locale);

                    if ($translation) {
                        // 已有翻译，输出实际数据
                        fputcsv($file, [
                            $translation->product_id,
                            $translation->locale,
                            $translation->name,
                            $translation->content,
                            $translation->meta_title,
                            $translation->meta_description,
                            $translation->meta_keywords,
                            $translation->gunit_max_des,
                            $translation->gunit_midd_des,
                            $translation->gunit_min_des,
                            $translation->min_purchasing_unit_des,
                            $translation->product_name_zh_cn,
                            $translation->gunit_max,
                            $translation->gunit_midd,
                            $translation->gunit_min
                        ]);
                    } else {
                        // 没有该语言翻译，创建空行
                        $firstTranslation = $translations[0]; // 获取第一条记录作为参考
                        fputcsv($file, [
                            $productId,
                            $locale,
                            '', // name
                            '', // content
                            '', // meta_title
                            '', // meta_description
                            '', // meta_keywords
                            '', // gunit_max_des
                            '', // gunit_midd_des
                            '', // gunit_min_des
                            '', // min_purchasing_unit_des
                            $firstTranslation->product_name_zh_cn, // 保留中文名称作为参考
                            $firstTranslation->gunit_max, // 保留规格单位参考
                            $firstTranslation->gunit_midd, // 保留规格单位参考
                            $firstTranslation->gunit_min // 保留规格单位参考
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * 在翻译数组中查找指定语言的翻译
     */
    private function findTranslation($translations, $locale)
    {
        foreach ($translations as $translation) {
            if ($translation->locale === $locale) {
                return $translation;
            }
        }
        return null;
    }
    public function importTranslations(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        try {
            $file = $request->file('file');

            // 读取文件内容并确保UTF-8编码
            $content = file_get_contents($file->getPathname());

            // 检测并转换编码
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1251', 'CP1251', 'EUC-KR', 'GB2312'], true);

            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            // 处理BOM头
            $bom = pack('H*', 'EFBBBF');
            $content = preg_replace("/^$bom/", '', $content);

            // 保存为临时文件
            $tempFile = tempnam(sys_get_temp_dir(), 'import_');
            file_put_contents($tempFile, $content);

            $handle = fopen($tempFile, 'r');
            if (!$handle) {
                throw new \Exception('无法打开文件');
            }

            // 跳过表头
            fgetcsv($handle);

            $imported = 0;
            $updated = 0;
            $errors = [];
            $rowNumber = 1;

            while (($data = fgetcsv($handle)) !== FALSE) {
                $rowNumber++;

                if (count($data) < 11) {
                    $errors[] = "第 {$rowNumber} 行数据列数不足";
                    continue;
                }

                // 清理数据并确保UTF-8编码
                $data = array_map(function($value) {
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        // 尝试多种编码转换
                        $encodings = ['GB2312', 'GBK', 'Windows-1251', 'ISO-8859-1', 'CP1251'];
                        foreach ($encodings as $enc) {
                            $converted = @mb_convert_encoding($value, 'UTF-8', $enc);
                            if (mb_check_encoding($converted, 'UTF-8') && !preg_match('/[\\x80-\\xff]/', $converted)) {
                                return trim($converted);
                            }
                        }
                        // 最后尝试自动检测
                        return trim(mb_convert_encoding($value, 'UTF-8', 'auto'));
                    }
                    return trim($value);
                }, $data);

                $productId = $data[0];
                $locale = $data[1];

                // 验证数据
                if (empty($productId)) {
                    $errors[] = "第 {$rowNumber} 行产品ID不能为空";
                    continue;
                }

                if (!in_array($locale, ['zh_cn', 'en', 'mn', 'ru'])) {
                    $errors[] = "第 {$rowNumber} 行语言代码必须是 zh_cn, en, mn, ru 中的一个";
                    continue;
                }

                // 检查产品是否存在
                $productExists = DB::table('products')->where('id', $productId)->exists();
                if (!$productExists) {
                    $errors[] = "第 {$rowNumber} 行产品ID {$productId} 不存在";
                    continue;
                }

                try {
                    $result = $this->saveTranslationData([
                        'product_id' => $productId,
                        'locale' => $locale,
                        'name' => $data[2] ?? '',
                        'content' => $data[3] ?? '',
                        'meta_title' => $data[4] ?? '',
                        'meta_description' => $data[5] ?? '',
                        'meta_keywords' => $data[6] ?? '',
                        'gunit_max_des' => $data[7] ?? '',
                        'gunit_midd_des' => $data[8] ?? '',
                        'gunit_min_des' => $data[9] ?? '',
                        'min_purchasing_unit_des' => $data[10] ?? '',
                    ]);

                    if ($result === 'created') {
                        $imported++;
                    } else {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "第 {$rowNumber} 行保存失败: " . $e->getMessage();
                }
            }

            fclose($handle);
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            if (!empty($errors)) {
                $errorMessage = __('admin/product.import_completed_with_errors') . "\n" . implode("\n", array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $errorMessage .= "\n......（还有" . (count($errors) - 10) . "个错误）";
                }
                return response()->json(['message' => $errorMessage], 422);
            }

            return response()->json([
                'message' => __('admin/product.import_success_count', [
                    'imported' => $imported,
                    'updated' => $updated
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => __('admin/product.import_failed') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 保存翻译数据
     */
    private function saveTranslationData($data)
    {
        $existing = DB::table('product_descriptions')
            ->where('product_id', $data['product_id'])
            ->where('locale', $data['locale'])
            ->first();

        $recordData = [
            'product_id' => $data['product_id'],
            'locale' => $data['locale'],
            'name' => $data['name'],
            'content' => $data['content'],
            'meta_title' => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'meta_keywords' => $data['meta_keywords'],
            'gunit_max_des' => $data['gunit_max_des'],
            'gunit_midd_des' => $data['gunit_midd_des'],
            'gunit_min_des' => $data['gunit_min_des'],
            'min_purchasing_unit_des' => $data['min_purchasing_unit_des'],
            'is_trans' => 1,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('product_descriptions')
                ->where('product_id', $data['product_id'])
                ->where('locale', $data['locale'])
                ->update($recordData);
            return 'updated';
        } else {
            $recordData['created_at'] = now();
            DB::table('product_descriptions')->insert($recordData);
            return 'created';
        }
    }

    /**
     * 下载模板
     */
    public function downloadTemplate()
    {
        $fileName = 'product_translations_template.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            // 添加 BOM 头解决中文乱码
            fwrite($file, "\xEF\xBB\xBF");

            // 表头
            fputcsv($file, [
                'product_id',
                'locale',
                'name',
                'content',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'gunit_max_des',
                'gunit_midd_des',
                'gunit_min_des',
                'min_purchasing_unit_des',
                'product_name_zh_cn',
                'gunit_max',
                'gunit_midd',
                'gunit_min'
            ]);
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
    /**
     * 下载商品批量上传模板
     */
    public function batch_template_csv()
    {
        $fileName = 'product_batch_upload_template.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            // 不添加BOM头，避免导入时的问题
            // fwrite($file, "\xEF\xBB\xBF");

            // 根据新模板调整表头
            fputcsv($file, [
                'name_zh',
                'name_en',
                'name_mn',
                'name_ru',
                'brand_name',
                'sku',
                'price',
                'origin_price',
                'cost_price',
                'quantity',
                'images',
                'category_id',
                'goods_code',
                'min',
                'gunit_max_zh',
                'gunit_max_en',
                'gunit_max_mn',
                'gunit_max_ru',
                'gnum_midd',
                'gunit_midd_zh',
                'gunit_midd_en',
                'gunit_midd_mn',
                'gunit_midd_ru',
                'gnum_min',
                'gunit_min_zh',
                'gunit_min_en',
                'gunit_min_mn',
                'gunit_min_ru',
                'quality',
                'min_purchasing_unit_zh',
                'min_purchasing_unit_en',
                'min_purchasing_unit_mn',
                'min_purchasing_unit_ru',
                'min_purchasing_price'
            ]);

            // 添加示例数据
            fputcsv($file, [
                '测试商品中文名',
                'Test Product English Name',
                'Монгол нэр',
                'Русское название',
                '品牌名称',
                'SKU001',
                '99.99',
                '129.99',
                '80.00',
                '100',
                '["/images/product1.jpg"]',
                '700306',
                '6928804011047',
                '1',
                '箱',
                'Box',
                'Хайрцаг',
                'Коробка',
                '12',
                '袋',
                'Bag',
                'Уут',
                'Мешок',
                '1',
                '瓶',
                'Bottle',
                'Лонх',
                'Бутылка',
                '365',
                '瓶',
                'Bottle',
                'Лонх',
                'Бутылка',
                '85.00'
            ]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
    /**
     * 下载商品批量上传模板 (XLS格式 - Excel 97-2003)
     */
    public function batch_template()
    {
        $fileName = 'product_batch_upload_template.xls';

        // 创建新的 Spreadsheet 对象
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $headers = [
            'name_zh',
            'name_en',
            'name_mn',
            'name_ru',
            'brand_name',
            'sku',
            'price',
            'origin_price',
            'cost_price',
            'quantity',
            'images',
            'category_id',
            'goods_code',
            'min',
            'gunit_max_zh',
            'gunit_max_en',
            'gunit_max_mn',
            'gunit_max_ru',
            'gnum_midd',
            'gunit_midd_zh',
            'gunit_midd_en',
            'gunit_midd_mn',
            'gunit_midd_ru',
            'gnum_min',
            'gunit_min_zh',
            'gunit_min_en',
            'gunit_min_mn',
            'gunit_min_ru',
            'quality',
            'min_purchasing_unit_zh',
            'min_purchasing_unit_en',
            'min_purchasing_unit_mn',
            'min_purchasing_unit_ru',
            'min_purchasing_price'
        ];

        // 写入表头到第一行 - 使用正确的方法名
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // 设置示例数据
        $sampleData = [
            '测试商品中文名',
            'Test Product English Name',
            'Монгол нэр',
            'Русское название',
            '品牌名称',
            'SKU001',
            '99.99',
            '129.99',
            '80.00',
            '100',
            '["/images/product1.jpg"]',
            '700306',
            '6928804011047',
            '1',
            '箱',
            'Box',
            'Хайрцаг',
            'Коробка',
            '12',
            '袋',
            'Bag',
            'Уут',
            'Мешок',
            '1',
            '瓶',
            'Bottle',
            'Лонх',
            'Бутылка',
            '365',
            '瓶',
            'Bottle',
            'Лонх',
            'Бутылка',
            '85.00'
        ];

        // 写入示例数据到第二行 - 使用正确的方法名
        foreach ($sampleData as $col => $data) {
            $sheet->setCellValueByColumnAndRow($col + 1, 2, $data);
        }

        // 设置列宽
        foreach (range('A', 'Z') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(25);

        // 创建 Excel5 写入器（用于 .xls 格式）
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);

        // 设置响应头
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ];

        // 输出文件
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, $headers);
    }
    public function batch_store_back(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        try {
            $file = $request->file('csv_file');
            $skipErrors = $request->get('skip_errors', false);

            // 读取文件内容并确保UTF-8编码
            $content = file_get_contents($file->getPathname());

            // 检测并转换编码
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1251', 'CP1251', 'EUC-KR', 'GB2312'], true);

            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            // 处理BOM头 - 更彻底的方式
            $content = $this->removeBom($content);

            // 保存为临时文件
            $tempFile = tempnam(sys_get_temp_dir(), 'batch_upload_');
            file_put_contents($tempFile, $content);

            $handle = fopen($tempFile, 'r');
            if (!$handle) {
                throw new \Exception('无法打开文件');
            }

            // 读取表头并清理BOM
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                unlink($tempFile);
                throw new \Exception('文件为空或格式不正确');
            }

            // 清理表头中的BOM和特殊字符
            $headers = $this->cleanHeaders($headers);

            $success = 0;
            $failed = 0;
            $errors = [];
            $rowNumber = 1;

            while (($data = fgetcsv($handle)) !== FALSE) {
                $rowNumber++;

                if (count($data) != count($headers)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => '数据列数与表头不匹配，期望 ' . count($headers) . ' 列，实际 ' . count($data) . ' 列'
                    ];
                    $failed++;
                    continue;
                }

                // 将数据与表头关联
                $rowData = array_combine($headers, $data);

                // 清理数据并确保UTF-8编码
                $rowData = $this->cleanRowData($rowData);

                // 验证必填字段
                $requiredFields = [
                    'name_zh',
                    'name_en',
                    'name_mn',
                    'name_ru',
                    'price',
                    'quantity'
                ];
                $missingFields = [];
                foreach ($requiredFields as $field) {
                    if (empty($rowData[$field])) {
                        $missingFields[] = $field;
                    }
                }

                if (!empty($missingFields)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => "必填字段不能为空: " . implode(', ', $missingFields),
                        'data' => $rowData
                    ];
                    $failed++;
                    continue;
                }

                // 验证SKU唯一性
                $existingSku = \Beike\Models\ProductSku::where('sku', $rowData['sku'])->first();
                if ($existingSku) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => "SKU {$rowData['sku']} 已存在",
                        'data' => $rowData
                    ];
                    $failed++;
                    continue;
                }

                try {
                    DB::beginTransaction();

                    // 转换数据格式
                    $productData = $this->prepareProductData($rowData);

                    // 使用ProductService创建商品
                    $productService = new ProductService();
                    $product = $productService->create($productData);

                    // 触发钩子
                    $hookData = [
                        'request_data' => $productData,
                        'product' => $product,
                    ];
                    hook_action('admin.product.batch_store.after', $hookData);

                    DB::commit();
                    $success++;

                } catch (\Exception $e) {
                    DB::rollBack();

                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                        'data' => $rowData
                    ];
                    $failed++;

                    // 如果不跳过错误，直接抛出异常
                    if (!$skipErrors) {
                        fclose($handle);
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                        throw $e;
                    }
                }
            }

            fclose($handle);
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            $response = [
                'message' => __('admin/product.batch_upload_completed', [
                    'success' => $success,
                    'failed' => $failed
                ]),
                'success' => $success,
                'failed' => $failed
            ];

            if (!empty($errors)) {
                $response['errors'] = array_slice($errors, 0, 20); // 最多返回20个错误
                $response['total_errors'] = count($errors);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            // 确保清理临时文件
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

            return response()->json([
                'message' => __('admin/product.batch_upload_failed') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 移除BOM头
     */
    protected function removeBom($content)
    {
        $boms = [
            pack('H*', 'EFBBBF'),    // UTF-8 BOM
            pack('H*', 'FEFF'),      // UTF-16 BE BOM
            pack('H*', 'FFFE'),      // UTF-16 LE BOM
            pack('H*', '0000FEFF'),  // UTF-32 BE BOM
            pack('H*', 'FFFE0000'),  // UTF-32 LE BOM
        ];

        foreach ($boms as $bom) {
            if (substr($content, 0, strlen($bom)) === $bom) {
                $content = substr($content, strlen($bom));
                break;
            }
        }

        return $content;
    }

    /**
     * 清理表头
     */
    protected function cleanHeaders($headers)
    {
        $cleanedHeaders = [];
        foreach ($headers as $header) {
            // 移除BOM和不可见字符
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header); // UTF-8 BOM
            $header = preg_replace('/[^\x20-\x7E]/u', '', $header); // 移除非ASCII字符
            $header = trim($header);
            $cleanedHeaders[] = $header;
        }
        return $cleanedHeaders;
    }

    /**
     * 清理行数据
     */
    protected function cleanRowData($rowData)
    {
        $cleanedData = [];
        foreach ($rowData as $key => $value) {
            // 清理键名
            $cleanKey = preg_replace('/^\xEF\xBB\xBF/', '', $key); // 移除BOM
            $cleanKey = preg_replace('/[^\x20-\x7E]/u', '', $cleanKey); // 移除非ASCII字符
            $cleanKey = trim($cleanKey);

            // 清理值
            if (!mb_check_encoding($value, 'UTF-8')) {
                // 尝试多种编码转换
                $encodings = ['GB2312', 'GBK', 'Windows-1251', 'ISO-8859-1', 'CP1251'];
                foreach ($encodings as $enc) {
                    $converted = @mb_convert_encoding($value, 'UTF-8', $enc);
                    if (mb_check_encoding($converted, 'UTF-8')) {
                        $value = $converted;
                        break;
                    }
                }
                // 最后尝试自动检测
                $value = mb_convert_encoding($value, 'UTF-8', 'auto');
            }

            $cleanedData[$cleanKey] = trim($value);
        }
        return $cleanedData;
    }

    /**
     * 准备商品数据 - 根据新模板调整
     */
    protected function prepareProductData_bak($rowData)
    {
        // 处理品牌信息
        $brandId = $this->getOrCreateBrand($rowData['brand_name'] ?? '');
        $productData = [
            // 基础信息字段
            'brand_id' => $brandId,
            'goods_code' => $rowData['goods_code'] ?? null,
            'images' => $this->parseJsonField($rowData['images'] ?? ''),
            'price' => $rowData['price'] ?? 0,
            'video' => '',
            'position' => 0,
            'shipping' => 1,
            'active' => 1,
//            'variables' => [],
            'tax_class_id' => 0,
            'weight' => 0,
            'weight_class' => '',
            'min' => (int)($rowData['min'] ?? 1),
            'gunit_max' => $rowData['gunit_max_zh'] ?? null,
            'gnum_midd' => isset($rowData['gnum_midd']) ? (int)$rowData['gnum_midd'] : null,
            'gunit_midd' => $rowData['gunit_midd_zh'] ?? null,
            'gnum_min' => isset($rowData['gnum_min']) ? (int)$rowData['gnum_min'] : null,
            'gunit_min' => $rowData['gunit_min_zh'] ?? null,
            'quality' => isset($rowData['quality']) ? (int)$rowData['quality'] : null,
            'remark' => '',
            'cash_price_small' => $rowData['min_purchasing_price'] ?? null,
            'min_purchasing_unit' => $rowData['min_purchasing_unit_zh'] ?? null,
            'min_purchasing_price' => $rowData['min_purchasing_price'] ?? null,
        ];

        // 商品描述（多语言）
        $productData['descriptions'] = [
            'zh_cn' => [
                'name' => $rowData['name_zh'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_zh'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_zh'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_zh'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_zh'] ?? '',
            ],
            'en' => [
                'name' => $rowData['name_en'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_en'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_en'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_en'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_en'] ?? '',
            ],
            'mn' => [
                'name' => $rowData['name_mn'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_mn'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_mn'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_mn'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_mn'] ?? '',
            ],
            'ru' => [
                'name' => $rowData['name_ru'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_ru'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_ru'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_ru'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_ru'] ?? '',
            ]
        ];

        // SKU数据
        $productData['skus'] = [
            [
                'variants' => [],
//                'images' => $this->parseJsonField($rowData['images'] ?? ''),
                'model' => 'default',
                'sku' => $rowData['sku'] ?? '',
                'price' => (float)($rowData['price'] ?? 0),
                'origin_price' => (float)($rowData['origin_price'] ?? ($rowData['price'] ?? 0)),
                'cost_price' => (float)($rowData['cost_price'] ?? 0),
                'quantity' => (int)($rowData['quantity'] ?? 0),
                'is_default' => true,
            ]
        ];

        // 分类 - 根据category_id设置
        $categoryId = isset($rowData['category_id']) ? (int)$rowData['category_id'] : 0;
        if ($categoryId > 0) {
            $productData['categories'] = [$categoryId];
        } else {
            $productData['categories'] = [];
        }

        // 属性和关联商品设为空
        $productData['attributes'] = [];
        $productData['relations'] = [];

        return $productData;
    }
    /**
     * 解析JSON字段
     */
    protected function parseJsonField($value)
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    /**
     * 获取或创建品牌
     */
    protected function getOrCreateBrand($brandName)
    {
        if (empty($brandName)) {
            return 0;
        }

        try {
            // 首先尝试查找已存在的品牌
            $brand = Brand::where('name', $brandName)->first();

            if ($brand) {
                return $brand->id;
            }

            // 如果品牌不存在，创建新品牌
            $brand = Brand::create([
                'name' => $brandName,
                'first' => '', // 获取品牌首字母
                'logo' => null,
                'sort_order' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $brand->id;

        } catch (\Exception $e) {
            return 0;
        }
    }

    public function batch_store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:51200'
        ]);

        try {
            $file = $request->file('csv_file');
            $skipErrors = $request->get('skip_errors', false);

            Log::info('开始处理文件: ' . $file->getClientOriginalName());

            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['csv', 'txt', 'xlsx', 'xls'];

            if (!in_array($extension, $allowedExtensions)) {
                return response()->json([
                    'message' => '不支持的文件格式。请上传 CSV、TXT 或 Excel 文件。'
                ], 422);
            }

            if (in_array($extension, ['xlsx', 'xls'])) {
                $filePath = $file->getPathname();
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();

                Log::info('使用PhpSpreadsheet加载Excel文件成功');

                // 获取表头
                $headers = [];
                $headerRow = $worksheet->getRowIterator(1, 1)->current();
                $cellIterator = $headerRow->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $headers[] = $cell->getCalculatedValue();
                }

                $headers = $this->cleanHeaders($headers);
                Log::info('表头: ' . json_encode($headers));


                // 在批量上传方法中替换图片分配部分
                $imagesByRow = $this->extractAllImages($worksheet);
                Log::info('原始图片提取结果: ' . json_encode($imagesByRow));

                // 使用智能分配
                $imagesByRow = $this->smartImageAssignment($imagesByRow, $worksheet, $headers);
                Log::info('智能分配后的图片: ' . json_encode($imagesByRow));

                // 提前提取所有图片
//                $imagesByRow = $this->extractAllImages($worksheet);
//                Log::info('原始图片提取结果: ' . json_encode($imagesByRow));
//
//                // 修正图片分配
//                $imagesByRow = $this->assignImagesToRows($imagesByRow, $worksheet);
//                Log::info('修正后的图片分配: ' . json_encode($imagesByRow));

                // 处理数据行
                foreach ($worksheet->getRowIterator(2) as $row) {
                    $rowNumber = $row->getRowIndex();

                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    $colIndex = 0;
                    foreach ($cellIterator as $cell) {
                        if ($colIndex >= count($headers)) break;

                        $value = $cell->getCalculatedValue();
                        $rowData[$headers[$colIndex]] = $value;
                        $colIndex++;
                    }

                    $rowData = $this->cleanRowData($rowData);
                    Log::info("第{$rowNumber}行原始数据: " . json_encode($rowData));

                    // 为该行分配图片
                    if (isset($imagesByRow[$rowNumber]) && !empty($imagesByRow[$rowNumber])) {
                        $rowData['extracted_images'] = $imagesByRow[$rowNumber];
                        Log::info("第{$rowNumber}行分配图片: " . json_encode($imagesByRow[$rowNumber]));
                    } else {
                        // 处理images字段中的公式或JSON
                        $this->processImagesField($rowData, $rowNumber);
                    }

                    $this->processProductRow($rowData, $rowNumber, $success, $failed, $errors, $skipErrors);
                }

            } else {
                // CSV处理逻辑
                $content = file_get_contents($file->getPathname());

                // 检测并转换编码
                $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1251', 'CP1251', 'EUC-KR', 'GB2312'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                }

                // 处理BOM头
                $content = $this->removeBom($content);

                // 保存为临时文件
                $tempFile = tempnam(sys_get_temp_dir(), 'batch_upload_');
                file_put_contents($tempFile, $content);

                $handle = fopen($tempFile, 'r');
                if (!$handle) {
                    throw new \Exception('无法打开文件');
                }

                // 读取表头
                $headers = fgetcsv($handle);
                if (!$headers) {
                    fclose($handle);
                    unlink($tempFile);
                    throw new \Exception('文件为空或格式不正确');
                }

                // 清理表头
                $headers = $this->cleanHeaders($headers);

                $rowNumber = 1;

                while (($data = fgetcsv($handle)) !== FALSE) {
                    $rowNumber++;

                    if (count($data) != count($headers)) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'error' => '数据列数与表头不匹配，期望 ' . count($headers) . ' 列，实际 ' . count($data) . ' 列'
                        ];
                        $failed++;
                        continue;
                    }

                    // 将数据与表头关联
                    $rowData = array_combine($headers, $data);

                    // 清理数据
                    $rowData = $this->cleanRowData($rowData);

                    // 处理images字段
                    $this->processImagesField($rowData, $rowNumber);

                    $this->processProductRow($rowData, $rowNumber, $success, $failed, $errors, $skipErrors);
                }

                fclose($handle);
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }

            // 返回响应...
            $response = [
                'message' => __('admin/product.batch_upload_completed', [
                    'success' => $success,
                    'failed' => $failed
                ]),
                'success' => $success,
                'failed' => $failed
            ];

            if (!empty($errors)) {
                $response['errors'] = array_slice($errors, 0, 20);
                $response['total_errors'] = count($errors);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('批量上传失败: ' . $e->getMessage());
            Log::error('异常追踪: ' . $e->getTraceAsString());
            return response()->json([
                'message' => __('admin/product.batch_upload_failed') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 处理商品数据行
     */
    protected function processProductRow($rowData, $rowNumber, &$success, &$failed, &$errors, $skipErrors)
    {
        // 验证必填字段
        $requiredFields = [
            'name_zh',
            'name_en',
            'name_mn',
            'name_ru',
            'gunit_max_zh',
            'gunit_max_en',
            'gunit_max_mn',
            'gunit_max_ru',
            'gunit_min_zh',
            'gunit_min_en',
            'gunit_min_mn',
            'gunit_min_ru',
            'min_purchasing_unit_zh',
            'min_purchasing_unit_en',
            'min_purchasing_unit_mn',
            'min_purchasing_unit_ru',
            'price',
            'origin_price',
            'cost_price',
            'category_id',
            'goods_code',
            'min',
            'min_purchasing_price',
            'brand_name',
            'quantity'
        ];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($rowData[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $errors[] = [
                'row' => $rowNumber,
                'error' => __('admin/product.must_colum') . implode(', ', $missingFields),
                'data' => $rowData
            ];
            $failed++;
            return;
        }

        // // 验证SKU唯一性
        // $existingSku = \Beike\Models\ProductSku::where('sku', $rowData['sku'])->first();
        // if ($existingSku) {
        //     $errors[] = [
        //         'row' => $rowNumber,
        //         'error' => "SKU {$rowData['sku']} 已存在",
        //         'data' => $rowData
        //     ];
        //     $failed++;
        //     return;
        // }

        try {
            DB::beginTransaction();

            // 准备商品数据
            $productData = $this->prepareProductData($rowData);

            // 使用ProductService创建商品
            $productService = new ProductService();
            $product = $productService->create($productData);

            DB::commit();
            $success++;

        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = [
                'row' => $rowNumber,
                'error' => $e->getMessage(),
                'data' => $rowData
            ];
            $failed++;
            if (!$skipErrors) throw $e;
        }
    }
    /**
     * 根据MIME类型获取图片扩展名
     */
    protected function getImageExtension($mimeType)
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        return $mimeMap[$mimeType] ?? 'png';
    }
    protected function prepareProductData($rowData)
    {
        // 处理品牌信息
        $brandId = $this->getOrCreateBrand($rowData['brand_name'] ?? '');
        // 优先使用提取的图片，如果没有则使用原来的images字段
        $images = [];
        if (!empty($rowData['extracted_images'])) {
            $images = $rowData['extracted_images'];
        } else {
            $images = $this->parseJsonField($rowData['images'] ?? '');
        }
        $productData = [
            // 基础信息字段
            'brand_id' => $brandId,
            'goods_code' => $rowData['goods_code'] ?? null,
//            'images' => $this->parseJsonField($rowData['images'] ?? ''),
            'images' => $images, // 使用处理后的图片数组
            'price' => $rowData['price'] ?? 0,
            'video' => '',
            'position' => 0,
            'shipping' => 1,
            'active' => 1,
//            'variables' => [],
            'tax_class_id' => 0,
            'weight' => 0,
            'weight_class' => '',
            'min' => (int)($rowData['min'] ?? 1),
            'gunit_max' => $rowData['gunit_max_zh'] ?? null,
            'gnum_midd' => isset($rowData['gnum_midd']) ? (int)$rowData['gnum_midd'] : null,
            'gunit_midd' => $rowData['gunit_midd_zh'] ?? null,
            'gnum_min' => isset($rowData['gnum_min']) ? (int)$rowData['gnum_min'] : null,
            'gunit_min' => $rowData['gunit_min_zh'] ?? null,
            'quality' => isset($rowData['quality']) ? (int)$rowData['quality'] : null,
            'remark' => '',
            'cash_price_small' => $rowData['min_purchasing_price'] ?? null,
            'min_purchasing_unit' => $rowData['min_purchasing_unit_zh'] ?? null,
            'min_purchasing_price' => $rowData['min_purchasing_price'] ?? null,
        ];

        // 商品描述（多语言）
        $productData['descriptions'] = [
            'zh_cn' => [
                'name' => $rowData['name_zh'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_zh'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_zh'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_zh'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_zh'] ?? '',
            ],
            'en' => [
                'name' => $rowData['name_en'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_en'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_en'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_en'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_en'] ?? '',
            ],
            'mn' => [
                'name' => $rowData['name_mn'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_mn'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_mn'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_mn'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_mn'] ?? '',
            ],
            'ru' => [
                'name' => $rowData['name_ru'] ?? '',
                'is_trans'=>1,
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'gunit_max_des' => $rowData['gunit_max_ru'] ?? '',
                'gunit_midd_des' => $rowData['gunit_midd_ru'] ?? '',
                'gunit_min_des' => $rowData['gunit_min_ru'] ?? '',
                'min_purchasing_unit_des' => $rowData['min_purchasing_unit_ru'] ?? '',
            ]
        ];

        // SKU数据
        $productData['skus'] = [
            [
                'variants' => [],
//                'images' => $this->parseJsonField($rowData['images'] ?? ''),
                'model' => 'default',
//                'sku' => $rowData['sku'] ?? '',
                'sku' => $this->generateSku(),
                'price' => (float)($rowData['price'] ?? 0),
                'origin_price' => (float)($rowData['origin_price'] ?? ($rowData['price'] ?? 0)),
                'cost_price' => (float)($rowData['cost_price'] ?? 0),
                'quantity' => (int)($rowData['quantity'] ?? 0),
                'is_default' => true,
            ]
        ];

        // 分类 - 根据category_id设置
        $categoryId = isset($rowData['category_id']) ? (int)$rowData['category_id'] : 0;
        if ($categoryId > 0) {
            $productData['categories'] = [$categoryId];
        } else {
            $productData['categories'] = [];
        }

        // 属性和关联商品设为空
        $productData['attributes'] = [];
        $productData['relations'] = [];

        return $productData;
    }

    protected function extractAllImages($worksheet)
    {
        $imagesByRow = [];

        $drawingCollection = $worksheet->getDrawingCollection();

        if ($drawingCollection) {
            \Log::info("找到 " . count($drawingCollection) . " 个绘图对象");

            foreach ($drawingCollection as $index => $drawing) {
                $rowIndex = $this->getDrawingRowIndex($drawing);
                \Log::info("绘图对象 {$index} 在行 {$rowIndex}, 坐标: " . $drawing->getCoordinates());

                if ($rowIndex > 0) {
                    $imagePath = $this->saveDrawingToFile($drawing);
                    if ($imagePath) {
                        if (!isset($imagesByRow[$rowIndex])) {
                            $imagesByRow[$rowIndex] = [];
                        }
                        $imagesByRow[$rowIndex][] = $imagePath;
                        \Log::info("成功保存图片到行 {$rowIndex}: {$imagePath}");
                    }
                }
            }
        } else {
            \Log::warning("没有找到绘图对象");
        }

        return $imagesByRow;
    }

    /**
     * 获取绘图对象所在的行索引
     */
    protected function getDrawingRowIndex($drawing)
    {
        $cellCoordinate = $drawing->getCoordinates();

        // 解析单元格坐标，如 'K2'
        preg_match('/([A-Z]+)(\d+)/', $cellCoordinate, $matches);

        if (count($matches) === 3) {
            return (int)$matches[2]; // 返回行号
        }

        return 0;
    }

    /**
     * 保存绘图到文件
     */
    protected function saveDrawingToFile($drawing)
    {
        try {
            // 创建保存目录
            $saveDir = public_path('uploads/products/' . date('Y/m/d'));
            if (!is_dir($saveDir)) {
                mkdir($saveDir, 0755, true);
            }

            // 生成文件名
            $filename = uniqid() . '_' . time();

            if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                // 处理内存中的图片
                $extension = $this->getImageExtension($drawing->getMimeType());
                $filename .= '.' . $extension;
                $fullPath = $saveDir . '/' . $filename;

                // 保存图片
                ob_start();
                call_user_func(
                    $drawing->getRenderingFunction(),
                    $drawing->getImageResource()
                );
                $imageContents = ob_get_contents();
                ob_end_clean();

                file_put_contents($fullPath, $imageContents);

            } else {
                // 处理外部图片文件
                $extension = pathinfo($drawing->getPath(), PATHINFO_EXTENSION);
                $filename .= '.' . ($extension ?: 'png');
                $fullPath = $saveDir . '/' . $filename;

                copy($drawing->getPath(), $fullPath);
            }

            // 返回相对路径
            return '/uploads/products/' . date('Y/m/d') . '/' . $filename;

        } catch (\Exception $e) {
            Log::error('保存图片失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 处理images字段
     */
    protected function processImagesField(&$rowData, $rowNumber)
    {
        $imagesField = $rowData['images'] ?? '';

        // 如果images字段是#NAME?（公式无法解析），则清空
        if ($imagesField === '#NAME?') {
            $rowData['images'] = '';
            Log::warning("第{$rowNumber}行: images字段包含无法解析的公式，已清空");
            return;
        }

        // 如果images字段包含DISPIMG公式，尝试提取图片ID
        if (strpos($imagesField, 'DISPIMG') !== false) {
            $imageId = $this->extractImageIdFromFormula($imagesField);
            if ($imageId) {
                Log::info("第{$rowNumber}行: 找到图片ID: {$imageId}");
                // 这里可以记录图片ID，但无法直接获取图片内容
                $rowData['images'] = ''; // 清空无法处理的公式
            }
        }

        // 处理JSON格式的图片数组
        if (!empty($rowData['images'])) {
            $parsedImages = $this->parseJsonField($rowData['images']);
            if (!empty($parsedImages)) {
                $rowData['extracted_images'] = $parsedImages;
                Log::info("第{$rowNumber}行: 解析到图片URL: " . json_encode($parsedImages));
            }
        }
    }
    /**
     * 智能图片分配策略
     */
    protected function smartImageAssignment($imagesByRow, $worksheet, $headers)
    {
        $assignedImages = [];
        $imageQueue = [];

        // 将所有图片收集到队列中
        foreach ($imagesByRow as $rowImages) {
            foreach ($rowImages as $image) {
                $imageQueue[] = $image;
            }
        }

        Log::info("图片队列: " . json_encode($imageQueue));

        // 按行处理，为有数据的行分配图片
        $imageIndex = 0;
        foreach ($worksheet->getRowIterator(2) as $row) {
            $rowNumber = $row->getRowIndex();

            // 检查该行是否有有效数据（不是空行）
            $hasValidData = false;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $value = $cell->getCalculatedValue();
                if (!empty($value) && $value !== '#NAME?') {
                    $hasValidData = true;
                    break;
                }
            }

            if ($hasValidData && isset($imageQueue[$imageIndex])) {
                $assignedImages[$rowNumber] = [$imageQueue[$imageIndex]];
                Log::info("为第{$rowNumber}行分配图片: {$imageQueue[$imageIndex]}");
                $imageIndex++;
            }
        }

        return $assignedImages;
    }
    /**
     * 生成随机SKU
     */
    private function generateSku()
    {
        return 'SKU_' . time() . '_' . rand(1000, 9999);
    }
}
