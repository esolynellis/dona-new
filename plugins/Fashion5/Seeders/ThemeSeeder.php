<?php
/**
 * SettingsSeeder.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-03-16 11:42:42
 * @modified   2023-03-16 11:42:42
 */

namespace Plugin\Fashion5\Seeders;

use Beike\Repositories\SettingRepo;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $homeSetting = $this->getHomeSetting();
        SettingRepo::update('system', 'base', ['design_setting' => $homeSetting]);
    }

    /**
     * 设置首页装修数据
     *
     * @return mixed
     * @throws \Exception
     */
    private function getHomeSetting(): mixed
    {
        return [
            "modules" =>[
                [
                    "code" =>"img_text_slideshow",
                    "content" => [
                        "style" => [
                            "background_color" =>""
                        ],
                        "floor" => [
                            "en" =>"",
                            "zh_cn" =>""
                        ],
                        "module_size" =>"w-100",
                        "scroll_text" => [
                            "text" => [
                                "zh_cn" =>"",
                                "en" =>""
                            ],
                            "bg" =>"#ffffff",
                            "color" =>"#333333",
                            "font_size" =>"36",
                            "padding" =>"20"
                        ],
                        "images" =>[
                            [
                                "image" => "/catalog/fashion_5/banner/1.jpg",
                                "sub_title" => [
                                    "zh_cn" =>"魔声耳机，魔力音乐",
                                    "en" =>"Hear the soul of music."
                                ],
                                "title" => [
                                    "zh_cn" =>"科技与美学的完美融合",
                                    "en" =>"One device, endless possibilities"
                                ],
                                "description" => [
                                    "zh_cn" =>"限时优惠 促销",
                                    "en" =>"Limited time promotion"
                                ],
                                "text_position" =>"start",
                                "show" =>false,
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100003,
                                    "link" =>""
                                ]
                            ],
                            [
                                "image" => "/catalog/fashion_5/banner/2.jpg",
                                "show" =>true,
                                "sub_title" => [
                                    "zh_cn" =>"畅享科技，触手可及",
                                    "en" =>"flat 35% discount"
                                ],
                                "title" => [
                                    "zh_cn" =>"智能生活，从这里开始",
                                    "en" =>"Capture the world, in one click"
                                ],
                                "text_position" =>"end",
                                "description" => [
                                    "zh_cn" =>"限时优惠 促销",
                                    "en" =>"Limited time promotion"
                                ],
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100012,
                                    "link" =>""
                                ]
                            ]
                        ]
                    ],
                    "module_id" =>"JEnfMFS0PnZiSXES",
                    "name" =>"图文幻灯片",
                    "view_path" => version_compare(config('beike.version'), '1.6.0') < 0  ? "Fashion5::shop/design_img_text_slideshow" : ""
                ],
                [
                    "code" =>"icons",
                    "content" => [
                        "style" => [
                            "background_color" =>""
                        ],
                        "module_size" =>"container-fluid",
                        "title" => [
                            "zh_cn" =>"精选类别",
                            "en" =>"Shop by selected categories"
                        ],
                        "floor" => [
                            "en" =>"",
                            "zh_cn" =>""
                        ],
                        "images" =>[
                            [
                                "image" => "/catalog/fashion_5/icon/1.png",
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100012,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"手表",
                                    "en" =>"Watch"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>false
                            ],
                            [
                                "image" => "/catalog/fashion_5/icon/2.png",
                                "link" => [
                                    "type" =>"product",
                                    "value" =>2,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"耳机",
                                    "en" =>"Earphone"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>false
                            ],
                            [
                                "image" => "/catalog/fashion_5/icon/3.png",
                                "link" => [
                                    "type" =>"product",
                                    "value" =>5,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"内存卡",
                                    "en" =>"Memory Card"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>false
                            ],
                            [
                                "image" => "/catalog/fashion_5/icon/4.png",
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100018,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"鼠标",
                                    "en" =>"Mouse"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>true
                            ],
                            [
                                "image" => "/catalog/fashion_5/icon/5.png",
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100017,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"手机",
                                    "en" =>"Phone"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>false
                            ],
                            [
                                "image" => "/catalog/fashion_5/icon/6.png",
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100005,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"相机",
                                    "en" =>"Camera"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>false
                            ],
                            [
                                "image" => "/catalog/fashion_5/icon/7.png",
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100010,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"电脑",
                                    "en" =>"Computer"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>false
                            ],
                            [
                                "image" => "/catalog/fashion_5/icon/8.png",
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100010,
                                    "link" =>""
                                ],
                                "text" => [
                                    "zh_cn" =>"游戏手柄",
                                    "en" =>"Gamepad"
                                ],
                                "sub_text" => [
                                    "zh_cn" =>"",
                                    "en" =>""
                                ],
                                "show" =>false
                            ]
                        ]
                    ],
                    "module_id" =>"lnGC3GZpP2uYsCIg",
                    "name" =>"图标模块",
                    "view_path" =>""
                ],
                [
                    "code" =>"image200",
                    "content" => [
                        "style" => [
                            "background_color" =>""
                        ],
                        "floor" => [
                            "zh_cn" =>"",
                            "en" =>""
                        ],
                        "module_size" =>"container-fluid",
                        "images" =>[
                            [
                                "image" => [
                                    "zh_cn" =>"/catalog/fashion_5/banner/3-zh-cn.jpg",
                                    "en" =>"/catalog/fashion_5/banner/3.jpg"
                                ],
                                "show" =>false,
                                "link" => [
                                    "type" =>"product",
                                    "value" =>"",
                                    "link" =>""
                                ]
                            ],
                            [
                                "image" => [
                                    "zh_cn" =>"/catalog/fashion_5/banner/4-zh-cn.jpg",
                                    "en" =>"/catalog/fashion_5/banner/4.jpg"
                                ],
                                "show" =>true,
                                "link" => [
                                    "type" =>"product",
                                    "value" =>"",
                                    "link" =>""
                                ]
                            ]
                        ]
                    ],
                    "module_id" =>"GC4HgLJjLgu0OPCU",
                    "name" =>"一行两图",
                    "view_path" =>""
                ],
                [
                    "code" =>"tab_product",
                    "content" => [
                        "style" => [
                            "background_color" =>""
                        ],
                        "floor" => [
                            "zh_cn" =>"",
                            "en" =>""
                        ],
                        "editableTabsValue" =>"2",
                        "tabs" =>[
                            [
                                "title" => [
                                    "zh_cn" =>"时尚单品",
                                    "en" =>"Fashion sheet"
                                ],
                                "products" =>[
                                    5,
                                    9,
                                    10,
                                    11,
                                    12,
                                    13,
                                    14,
                                    15,
                                    8,
                                    2
                                ]
                            ],
                            [
                                "title" => [
                                    "zh_cn" =>"潮流穿搭",
                                    "en" =>"Trendy outfits"
                                ],
                                "products" =>[
                                    39,
                                    15,
                                    1,
                                    4,
                                    13,
                                    7,
                                    8,
                                    3,
                                    9,
                                    10
                                ]
                            ],
                            [
                                "title" => [
                                    "zh_cn" =>"最新促销",
                                    "en" =>"Latest promotions"
                                ],
                                "products" =>[
                                    1,
                                    2,
                                    3,
                                    4,
                                    5,
                                    7,
                                    8,
                                    11,
                                    9,
                                    10
                                ]
                            ]
                        ],
                        "title" => [
                            "zh_cn" =>"推荐商品",
                            "en" =>"Fashion items"
                        ]
                    ],
                    "module_id" =>"s6e7e3vucriazzbi",
                    "name" =>"选项卡商品"
                ],
                [
                    "code" =>"image100",
                    "content" => [
                        "style" => [
                            "background_color" =>""
                        ],
                        "floor" => [
                            "en" =>"",
                            "zh_cn" =>""
                        ],
                        "module_size" =>"container-fluid",
                        "images" =>[
                            [
                                "image" => [
                                    "en" =>"/catalog/fashion_5/banner/5.png",
                                    "zh_cn" =>"/catalog/fashion_5/banner/5-zh-cn.png"
                                ],
                                "show" =>true,
                                "link" => [
                                    "type" =>"category",
                                    "value" =>100018,
                                    "link" =>""
                                ]
                            ]
                        ]
                    ],
                    "module_id" =>"T5VWnNOJPf72Lzao",
                    "name" =>"横幅模块",
                    "view_path" =>""
                ],
                [
                    "code" =>"product",
                    "content" => [
                        "style" => [
                            "background_color" =>""
                        ],
                        "floor" => [
                            "en" =>"",
                            "zh_cn" =>""
                        ],
                        "module_size" =>"container-fluid",
                        "products" =>[
                            1,
                            2,
                            3,
                            6,
                            5
                        ],
                        "title" => [
                            "zh_cn" =>"精密加工",
                            "en" =>"Process Technology"
                        ]
                    ],
                    "module_id" =>"XT6QIdw2wfgWF9Ua",
                    "name" =>"商品模块",
                    "view_path" =>""
                ],
                [
                    "code" =>"page",
                    "content" => [
                        "style" => [
                            "background_color" =>""
                        ],
                        "floor" => [
                            "zh_cn" =>"",
                            "en" =>""
                        ],
                        "items" =>[
                            22,
                            23,
                            24,
                            25
                        ],
                        "title" => [
                            "zh_cn" =>"新闻博客",
                            "en" =>"News Blog"
                        ]
                    ],
                    "module_id" =>"24P9p4bRwk1nbtXE",
                    "name" =>"文章模块",
                    "view_path" =>""
                ]
            ]
        ];
    }
}
