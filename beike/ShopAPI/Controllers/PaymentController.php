<?php

namespace Beike\ShopAPI\Controllers;

use Beike\Models\Order;
use Beike\Repositories\OrderRepo;
use Beike\Services\QPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController
{
    protected $qpay;

    public function __construct(QPayService $qpay)
    {
        $this->qpay = $qpay;
    }

    /**
     * 创建支付订单
     */
    public function createPayment(Request $request)
    {
        $orderId = uniqid(); // 生成唯一订单ID
        $orderNumber = $request->order_sn;
        $customer    = current_customer();
        $order       = OrderRepo::getOrderByNumber($orderNumber, $customer);
        $invoiceData = [
//            'invoice_code' => 'DONA_INVOICE', // 从QPay获取的发票代码
            'invoice_code' => 'DONA_TRADE_INVOICE', // 从QPay获取的发票代码
            'sender_invoice_no' => $orderId,
            'invoice_receiver_code' => 'terminal', // 或特定客户代码
            'invoice_description' => '订单支付 - ' . $orderId,
            'sender_branch_code' => 'SALBAR1', // 可选，分支机构代码
            'amount' => round($order->total*$order->currency_value, 2),
            'callback_url' => route('api.payment.callback', ['payment_id' => $orderId]), // 支付回调URL，包含payment_id参数
        ];
        $result = $this->qpay->createSimpleInvoice($invoiceData);
        if (!$result) {
            return json_fail('创建支付订单失败');
        }
        //更新订单表qpay_id
        $order->qpay_id = $result['invoice_id'];
        $order->payment_id = $orderId;
        $order->update();
        return json_encode($result,true);
    }

    /**
     * 支付回调处理
     */
    public function paymentCallback(Request $request)
    {
        // 记录完整的请求参数
        Log::channel('qpay')->info('QPay回调请求参数:', [
            'headers' => $request->headers->all(),
            'input' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        $paymentId = $request->input('payment_id');

        // 验证支付状态
        $paymentInfo = $this->qpay->getPayment($paymentId);

        if ($paymentInfo && $paymentInfo['payment_status'] === 'PAID') {
            // 支付成功，更新订单状态
            // 这里可以添加更新订单状态的逻辑
            $order = Order::where('qpay_id', $paymentId)->first();
            if(!$order){
                $order = Order::where('payment_id', $paymentId)->first();
            }
            if ($order) {
                $order->update(['status' => 'paid']);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }

    /**
     * 检查支付状态
     */
    public function checkPaymentStatus(Request $request)
    {
        $invoiceId = $request->input('invoice_id');
        $result = $this->qpay->checkPayment('INVOICE', $invoiceId);

        if ($result && $result['count'] > 0) {
            // 根据invoice_id查询订单
            $order = Order::where('qpay_id', $invoiceId)->first();
            if ($order) {
                // 检查订单是否已经处理过
                if ($order->status !== 'paid') {
                    // 更新订单状态
                    $order->update([
                        'status' => 'paid',
                    ]);
                }
            }
            return response()->json([
                'paid' => true,
                'payment' => $result['rows'][0]
            ]);
        }

        return response()->json(['paid' => false]);
    }

    /**
     * 取消支付
     */
    public function cancelPayment($paymentId)
    {
        $success = $this->qpay->cancelPayment($paymentId, '用户取消支付');

        if ($success) {
            // 更新订单状态为已取消
            $order = Order::where('qpay_id', $paymentId)->first();
            if(!$order){
                $order = Order::where('payment_id', $paymentId)->first();
            }
            if ($order) {
                $order->update([
                    'status' => 'cancelled',
                ]);
            }

            return back()->with('success', '支付已取消');
        }

        return back()->with('error', '取消支付失败');
    }
}
