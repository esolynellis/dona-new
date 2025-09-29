<?php

namespace Plugin\LOffline;


use Beike\Repositories\LanguageRepo;
use Beike\Services\StateMachineService;
use Plugin\LOffline\Models\OfflinePaymentConfigDescriptions;
use Plugin\LOffline\Models\OfflinePaymentOrder;

class Bootstrap
{

    public function boot()
    {

        //加入接口权限
        add_hook_filter('role.permissions.plugin', function ($data) {
            $data[] = [
                'title'       => '线下支付',
                'permissions' => [
                    [
                        'code' => 'l_offline_payment_config_update',
                        'name' => '修改支付提示信息',
                    ],
                ],
            ];

            return $data;
        });

        add_hook_filter("order.status_format", function ($status_format) {
            if ($status_format == 'order.cash_on_delivery') {
                $status_format = trans("LOffline::common.payment_name");
            }
            return $status_format;
        });


        add_hook_filter("service.state_machine.machines", function ($data) {
            $order = $data['order'];
            if ($order->status == 'l_offline') {
                $data['machines']['l_offline'] = $data['machines']['paid'];
            }
            return $data;
        });

        add_hook_filter("service.state_machine.all_statuses", function ($data) {
            $data[] = [
                'status' => 'l_offline',
                'name'   => trans("LOffline::common.payment_name"),
            ];
            return $data;
        });

        add_hook_filter('service.payment.pay.data', function ($data) {
            $order               = $data['order'];
            $payment_method_code = $order['payment_method_code'];
            if ($payment_method_code != 'l_offline') {
                return $data;
            }

            $offlinePD = OfflinePaymentConfigDescriptions::query()->where("locale", locale())->first();
            if ($offlinePD) {
                $data['offline_des'] = $offlinePD->content;
            } else {
                $data['offline_des'] = "";
            }
            return $data;
        });


        //后台支付图片记录展示
        add_hook_blade('admin.order.form.payments', function ($callback, $output, $data) {
            $order = $data['order'];
            if ($order['payment_method_code'] != 'l_offline') {
                return $output;
            }
            $offlineP = OfflinePaymentOrder::query()->where("order_id", $order->id)->first();
            if (empty($offlineP)) {
                return $output;
            }

            $offline_imgs = json_decode($offlineP['imgs'], true);
            foreach ($offline_imgs as $key => $offline_img) {
                $offline_imgs[$key] = plugin_asset('LOffline', $offline_img);
            }

            $data['offline_imgs'] = $offline_imgs;
            $view                 = view('LOffline::admin.certificate', $data)->render();
            return $view . $output;
        });

        add_hook_blade('admin.plugin.form', function ($callback, $output, $data) {
            $code = $data['plugin']->code;
            if ($code == 'l_offline') {
                $offlinePDs = OfflinePaymentConfigDescriptions::query()->get();
                if ($offlinePDs->count() > 0) {
                    $data['offline_payment_descriptions'] = $offlinePDs;
                } else {
                    $data['offline_payment_descriptions'] = [];
                }
                $data['languages2'] = LanguageRepo::all();

                return view('LOffline::admin.config_form', $data)->render();
            }
            return $output;
        }, 10096);
    }
}
