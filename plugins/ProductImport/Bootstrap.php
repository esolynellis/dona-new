<?php
/**
 * bootstrap.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2023-02-24 15:35:59
 * @modified   2023-02-24 15:35:59
 */

namespace Plugin\ProductImport;

class Bootstrap
{
    public function boot()
    {
        add_hook_filter('admin.sidebar.product_routes', function ($data) {
            $data[] = [
                'route'    => 'import.index',
                'title'    => trans('ProductImport::common.title'),
                'prefixes' => ['import'],
            ];

            return $data;
        }, 0);
        add_hook_filter('admin.sidebar.product.prefix', function ($data) {
            $data[] = 'import';

            return $data;
        }, 0);

        add_hook_filter('role.permissions.plugin', function ($data) {
            $data[] = [
                'title'       => trans('ProductImport::common.title'),
                'permissions' => [
                    [
                        'code' => 'products_import_index',
                        'name' => trans('ProductImport::common.index'),
                    ],
                    [
                        'code' => 'products_import_export',
                        'name' => trans('ProductImport::common.export'),
                    ],
                    [
                        'code' => 'products_import_import',
                        'name' => trans('ProductImport::common.import'),
                    ],
                ],
            ];

            return $data;
        });
        add_hook_filter('seller.sidebar.menus', function ($data) {
            $data = array_map(function ($item) {
                if ($item['code'] == 'products') {
                    $item['children'][] = [
                        'name' => trans('ProductImport::common.title'),
                        'route' => 'import.index',
                        'blank' => false,
                    ];
                }
                return $item;
            }, $data);

            return $data;
        }, 0);

    }
}
