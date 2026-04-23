<?php

/**
 * bootstrap.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-07-20 15:35:59
 * @modified   2022-07-20 15:35:59
 */

namespace Plugin\Fashion5;

class Bootstrap
{
    public function boot()
    {
        // 兼容 1.6
        add_hook_blade('home.modules.after', function ($callback, $output, $data) {
            if (version_compare(config('beike.version'), '1.6.0') < 0) {
                return '<style>.module-image-banner .container-fluid {padding-right: 0;padding-left: 0;}</style>';
            }
        });

        add_hook_blade('product.detail.footer', function ($callback, $output, $data) {
            if (version_compare(config('beike.version'), '1.6.0') < 0) {
                return '<style>.page-product .product-image .left #swiper {max-height: 500px}</style>';
            }
        });

        /**
         * Add module for admin design.
         */
        if (version_compare(config('beike.version'), '1.6.0') < 0) {
            add_hook_filter('admin.design.index.data', function ($data) {
                $data['editors'][] = 'editor-img_text_slideshow';

                return $data;
            });

            add_hook_filter('service.design.module.content', function ($data) {
                $module = $data['module_code'] ?? '';

                if ($module == 'img_text_slideshow') {
                    $images = $data['images'];
                    if (empty($images)) {
                        return $data;
                    }

                    $data['images'] = self::handleImages($images);
                }

                return $data;
            });
        }
    }

    /**
     * 处理图片以及链接
     * @throws \Exception
     */
    private static function handleImages($images): array
    {
        if (empty($images)) {
            return [];
        }

        foreach ($images as $index => $image) {
            $imagePath                        = $image['image']         ?? '';
            $images[$index]['text_position']  = $image['text_position'] ?? 'start';
            $images[$index]['image']          = image_origin($imagePath);
            $images[$index]['sub_title']      = $image['sub_title'][locale()] ?? '';
            $images[$index]['title']          = $image['title'][locale()]     ?? '';
            $images[$index]['description']    = nl2br($image['description'][locale()] ?? '');

            $link = $image['link'];
            if (empty($link)) {
                continue;
            }

            $type                           = $link['type'] ?? '';
            $value                          = $link['type'] == 'custom' ? $link['value'] : ((int) $link['value'] ?? 0);
            $images[$index]['link']['link'] = self::handleLink($type, $value);
        }

        return $images;
    }

    /**
     * 处理链接
     *
     * @param $type
     * @param $value
     * @return string
     * @throws \Exception
     */
    private static function handleLink($type, $value): string
    {
        return type_route($type, $value);
    }
}
