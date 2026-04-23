<?php
/**
 * Render.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-07-08 17:09:15
 * @modified   2022-07-08 17:09:15
 */

namespace Plugin\Fashion5\Admin\View\DesignBuilders;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ImgTextSlideshow extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View
     */
    public function render(): View
    {
        if (version_compare(config('beike.version'), '1.6.0') < 0) {
            $view_path = 'Fashion5::shop/design_img_text_slideshow';
        } else {
            $view_path = 'admin::pages.design.module.img_text_slideshow';
        }

        $data['register'] = [
            'code'      => 'img_text_slideshow',
            'sort'      => 0,
            'name'      => trans('Fashion5::common.img_text_slideshow'),
            'icon'      => plugin_asset('Fashion5', 'image/img_text_slideshow.png'),
            'view_path' => $view_path,
        ];

        if (version_compare(config('beike.version'), '1.6.0') < 0) {
            return view('Fashion5::admin/img_text_slideshow', $data);
        }

        return view('admin::pages.design.module.img_text_slideshow', $data);
    }
}
