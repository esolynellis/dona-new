<?php
/**
 * OrderController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-08-14 17:54:20
 * @modified   2023-08-14 17:54:20
 */

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Models\Order;
use Beike\Repositories\OrderRepo;
use Beike\Services\StateMachineService;
use Beike\Shop\Http\Resources\Account\OrderDetailList;
use Beike\Shop\Http\Resources\Account\OrderDetailResource;
use Beike\Shop\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugin\LOffline\Models\OfflinePaymentOrder;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $customer = current_customer();
        if (empty($customer)) {
            return json_success(trans('common.get_success'));
        }

        try {
            $filters = [
                'customer' => $customer,
                'status'   => $request->get('status'),
            ];
            $orders = OrderRepo::filterOrders($filters);

            return OrderDetailList::collection($orders);
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function show(Order $order): JsonResponse
    {
        try {
            $customer = current_customer();
            if ($customer && $order->customer_id != $customer->id) {
                throw new \Exception('Order dose not belong to customer');
            }

            $orderData = new OrderDetailResource($order);

            return json_success(trans('common.get_success'), $orderData);

        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function pay(Request $request, Order $order)
    {
        try {
            $customer = current_customer();
            if ($customer && $order->customer_id != $customer->id) {
                throw new \Exception('Order dose not belong to customer');
            }

            return (new PaymentService($order))->mobilePay();
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function offlinepay(Request $request, Order $order)
    {
        try {
            $customer = current_customer();
            if ($customer && $order->customer_id != $customer->id) {
                throw new \Exception('Order dose not belong to customer');
            }

            return (new PaymentService($order))->offlinePay();
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        try {
            $customer = current_customer();
            if (empty($customer)) {
                throw new \Exception('Empty customer');
            }
            if ($order->customer_id != $customer->id) {
                throw new \Exception('Order dose not belong to customer');
            }

            StateMachineService::getInstance($order)->changeStatus(StateMachineService::CANCELLED);

            return json_success(trans('common.edit_success'));

        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function confirm(Request $request, Order $order): JsonResponse
    {
        try {
            $customer = current_customer();
            if (empty($customer)) {
                throw new \Exception('Empty customer');
            }
            if ($order->customer_id != $customer->id) {
                throw new \Exception('Order dose not belong to customer');
            }

            StateMachineService::getInstance($order)->changeStatus(StateMachineService::COMPLETED);

            return json_success(trans('common.edit_success'));

        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function uploadProof(Request $request)
    {

        // 判断上传的文件是否存在
        if ($request->hasFile('file')) {
            // 获取上传的文件
            $file = $request->file('file');
            $imagePaths = [];
            // 判断文件是否上传成功
            if ($file->isValid()) {
                $upload_path = plugin_path('LOffline/Static/uploads/');
                // 获取文件扩展名
                $ext = $file->getClientOriginalExtension();
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                if (!in_array(strtolower($ext), $allowedExtensions)) {
                    return response()->json([
                        'code'     => -1,
                        'msg'      => '文件格式错误',
                    ]);
                }
                // 生成新的文件名
                $newName = md5(time() . rand(0, 10000)) . "." . $ext;
                // 将文件移动到指定目录
                $file->move($upload_path, $newName);
                $imagePaths[] = '/uploads/' . $newName;
                // 返回文件路径
//                return [
//                    'url'  => plugin_asset("LOffline", '/uploads/' . $newName),
//                    'path' => '/uploads/' . $newName,
//                ];
            }else{
                return response()->json([
                    'code'     => -1,
                    'msg'      => '上传失败',
                ]);
            }

        }else{
            return response()->json([
                'code'     => -1,
                'msg'      => '请选择文件',
            ]);
        }

        // 将图片路径数组转为JSON格式并存入数据库
        $images = json_encode($imagePaths,true);
        $customer = current_customer();
        $order    = OrderRepo::getOrderByNumber($request->order_no, $customer);
        if ($order && $order->status == StateMachineService::UNPAID) {
            OfflinePaymentOrder::query()->insert([
                'order_id' => $order->id,
                'imgs'     => $images
            ]);

            //OrderPaymentRepo::createOrUpdatePayment($order->id, ['response' => $result]);
            StateMachineService::getInstance($order)->changeStatus(StateMachineService::PAID);
            //再修改为
            $order->status = 'l_offline';
            $order->update();
            return response()->json([
                'code'     => 0,
                'msg'      => '成功',
            ]);
        }
        return response()->json([
            'code' => -1,
            'msg'  => "订单号错误"
        ]);
    }
}
