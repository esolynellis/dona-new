<?php
/**
 * ProductController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2023-02-24 18:57:56
 * @modified   2023-02-24 18:57:56
 */

namespace Plugin\ProductImport\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugin\ProductImport\Requests\ProductImportRequest;
use Plugin\ProductImport\Services\ProductImportService;

class ProductImportController
{
    public function index(): View
    {
        $data = [];

        // 判断是否定义了函数is_seller
        if (function_exists('is_seller') && is_seller()) {
            return view('ProductImport::seller.product_import', $data);
        }
        return view('ProductImport::admin.product_import', $data);
    }

    public function upload(ProductImportRequest $request)
    {
        ini_set('memory_limit', '-1');

        $json                = [];
        $post_max_size       = ProductImportService::getBytes(ini_get('post_max_size'));
        $upload_max_filesize = ProductImportService::getBytes(ini_get('upload_max_filesize'));

        if ($request->file()['import_file']->getSize() > $post_max_size) {
            $json['error'] = sprintf(trans('ProductImport::common.error_post_max_size'), ini_get('post_max_size'));
        } elseif ($request->file()['import_file']->getSize() > $upload_max_filesize) {
            $json['error'] = sprintf(trans('ProductImport::common.error_upload_max_filesize'), ini_get('upload_max_filesize'));
        }

        if (! $json) {
            $filename = 'gd_' . time();
            $request->file()['import_file']->storeAs('/upload/', $filename);
            $file = storage_path('app/') . '/upload/' . $filename;

            try {
                $json['count'] = ProductImportService::getInstance()->upload($file);
            } catch (\Exception $e) {
                $json['error'] = $e->getMessage();
            }
        }

        return json_success(trans(''), $json);
    }

    public function import(Request $request)
    {
        $json = [];

        ini_set('memory_limit', '-1');

        try {
            ProductImportService::getInstance()->import($request->get('index') + 1);
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        return json_success(trans('common.success'), $json);
    }

    public function export(Request $request)
    {
        ini_set('memory_limit', '-1');

        try {
            return ProductImportService::getInstance()->download($request->all());
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
