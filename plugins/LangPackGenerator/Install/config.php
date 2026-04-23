<?php

return [
    'install_files' => [
        [
            'line' => [
                '1.4.0' => [
                    10
                ],
                '1.5.5' =>[
                    10
                ],
                '1.5.0' => [
                    10
                ],
                '1.6.0' => [
                    10
                ]
            ],
            'version' => ['1.4.0', '1.5.0','1.5.5','1.6.0'],
            'hook' => 'admin.menu.before',
            'name' => '文本i18n组件',
            'path' => 'resources/beike/admin/views/pages/design/builder/component/text_i18n.blade.php',
            'code' => 0,
        ],
        [
            'line' => [
                '1.4.0' => [
                    44
                ],
                '1.5.5' =>[
                    44
                ],
                '1.5.0' => [
                    44
                ],
                '1.6.0' => [
                    44
                ]
            ],
            'version' => ['1.4.0', '1.5.0','1.5.5','1.6.0'],
            'hook' => 'admin.page_category.info.before',
            'name' => '文章分页',
            'path' => 'resources/beike/admin/views/pages/page_categories/form.blade.php',
            'code' => 0,
        ],
        [
            'line' => [
                '1.4.0' => [
                    42
                ],
                '1.5.5' =>[
                    42
                ],
                '1.5.0' => [
                    42
                ],
                '1.6.0' => [
                    42
                ]

            ],
            'version' => ['1.4.0', '1.5.0','1.5.5','1.6.0'],
            'hook' => 'admin.page.info.before',
            'name' => '文章表单',
            'path' => 'resources/beike/admin/views/pages/pages/form.blade.php',
            'code' => 1,
         ],
        [
            'line' => [
                '1.4.0' => [
                    53
                ],
                '1.5.5' =>[
                    53
                ],
                '1.5.0' => [
                    53
                ],
                '1.6.0' => [
                    53
                ]
            ],
            'version' => ['1.4.0', '1.5.0','1.5.5','1.6.0'],
            'hook' => 'admin.product.sku.edit.item.after',
            'name' => '商品属性',
            'path' => 'resources/beike/admin/views/pages/attributes/index.blade.php',
            'code' => 0,
        ],
        [
            'line' => [
                '1.4.0' => [
                    48
                ],
                '1.5.5' =>[
                    48
                ],
                '1.5.0' => [
                    48
                ],
                '1.6.0' => [
                    48
                ]
            ],
            'version' => ['1.4.0', '1.5.0','1.5.5','1.6.0'],
            'hook' => 'admin.product.sku.edit.item.after',
            'name' => '商品属性组',
            'path' => 'resources/beike/admin/views/pages/attribute_group/index.blade.php',
            'code' => 0,
        ],
        [
            'line' => [
                '1.4.0' => [
                    19,84
                ],
                '1.5.5' =>[
                    19,84
                ],
                '1.5.0' => [
                    19,84
                ],
                '1.6.0' => [
                    19,84
                ]
            ],
            'version' => ['1.4.0', '1.5.0','1.5.5','1.6.0'],
            'hook' => 'admin.product.sku.edit.item.after',
            'name' => '属性表单',
            'path' => 'resources/beike/admin/views/pages/attributes/form.blade.php',
            'code' => 0,
        ],
        [
            'line' => [
                '1.4.0' => [
                    508
                ],
                '1.5.5' =>[
                    508
                ],
                '1.5.0' => [
                    508
                ],
                '1.6.0' => [
                    508
                ]
            ],
            'version' => ['1.4.0', '1.5.0','1.5.5','1.6.0'],
            'hook' => 'admin.product.sku.edit.item.after',
            'name' => '商品表单',
            'path' => 'resources/beike/admin/views/pages/products/form/form.blade.php',
            'code' => 0,
        ],

    ]
];
//<x-admin::form.row title="{{ __('common.language') }}">
//    @hook('admin.page.info.before')
//                  </x-admin::form.row>
