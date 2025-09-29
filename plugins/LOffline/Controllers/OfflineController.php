<?php

namespace Plugin\LOffline\Controllers;

use Beike\Admin\Http\Controllers\Controller;
use Beike\Repositories\OrderRepo;
use Beike\Services\StateMachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugin\LOffline\Models\OfflinePaymentOrder;

class OfflineController extends Controller
{
    public function imgUpload(Request $request)
    {
        // 判断上传的文件是否存在
        if ($request->hasFile('file')) {

            // 获取上传的文件
            $file = $request->file('file');
            // 判断文件是否上传成功
            if ($file->isValid()) {
                $upload_path = plugin_path('LOffline/Static/uploads/');
                // 获取文件扩展名
                $ext = $file->getClientOriginalExtension();
                // 生成新的文件名
                $newName = md5(time() . rand(0, 10000)) . "." . $ext;
                // 将文件移动到指定目录
                $file->move($upload_path, $newName);
                // 返回文件路径
                return [
                    'url'  => plugin_asset("LOffline", '/uploads/' . $newName),
                    'path' => '/uploads/' . $newName,
                ];
            }

        }

    }

    /**
     * 支付完后跳转页面
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function pay_result(Request $request)
    {

        $imgs = $request->imgs;

        if (empty($imgs)) {
            return response()->json([
                'code' => -1,
                'msg'  => trans("LOffline::common.certificate_empty")
            ]);
        }

        $customer = current_customer();
        $order    = OrderRepo::getOrderByNumber($request->order_no, $customer);
        if ($order && $order->status == StateMachineService::UNPAID) {

            OfflinePaymentOrder::query()->insert([
                'order_id' => $order->id,
                'imgs'     => json_encode($imgs, true)
            ]);

            //OrderPaymentRepo::createOrUpdatePayment($order->id, ['response' => $result]);
            StateMachineService::getInstance($order)->changeStatus(StateMachineService::PAID);
            //再修改为
            $order->status = 'l_offline';
            $order->update();

            if ($customer) {
                return response()->json([
                    'code'     => 0,
                    'msg'      => '',
                    "callback" => shop_route('account.order.show', $order->number)
                ]);
            }
            return response()->json([
                'code'     => 0,
                'msg'      => '',
                "callback" => shop_route('checkout.success', ['order_number' => $order->number])
            ]);
        }
        return response()->json([
            'code' => -1,
            'msg'  => "订单号错误"
        ]);
    }
}
