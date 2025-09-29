<?php


namespace Plugin\Commission\Controllers;

use Beike\Models\Order;
use Beike\Shop\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugin\Wallet\Models\OrderRecycle;

class RecycleController extends Controller
{
    public function recycle_orders_index()
    {
        $plugin = app('plugin')->getPlugin('commission');
        $data   = [
            'name'        => '回收管理',
            'description' => $plugin->getLocaleDescription(),
        ];

        return view('Commission::admin.recycle_orders', $data);
    }

    public function recycle_orders(Request $request)
    {
        $logs = OrderRecycle::query()->with([
            'customer',
            'order',
            'order_product'
        ]);
        if ($request->q) {
            $logs = $logs->where('status', $request->q);
        }
        if(!empty(current_user()->code)){
            $logs = $logs->where('admin_user_id',current_user()->id);
        }


        $logs = $logs->orderByDesc('id')->paginate(perPage());
        return response()->json($logs);
    }

    public function recycle(Request $request)
    {
        $logs = OrderRecycle::query()->with([
            'customer',
            'order',
            'order_product'
        ]);
        $log  = $logs->where('id', $request->id)->first();
        if ($log->status == 1) {
            return response()->json(['code' => 0]);
        }
        $log->status       = 1;
        $log->amount       = $request->amount;
        $log->total_amount = bcmul($request->amount, $request->qua, 2);
        $log->update();

        //加回金额
        $log->customer->balance = bcadd($log->customer->balance, $log->total_amount, 2);
        $log->customer->update();

        return response()->json(['code' => 0]);
    }


    public function index(Request $request)
    {
        $logs   = Order::query()->where('customer_id', current_customer()->id)->with(['orderProducts']);
        $logs   = $logs->orderByDesc('id')->paginate(perPage());
        $orders = $logs->items();

        $orderIDs = [];
        foreach ($orders as $log) {
            $orderIDs[] = $log['id'];
        }

        $orRs = OrderRecycle::query()->whereIn('order_id', $orderIDs)->get();

        $tmpOrRs = [];
        foreach ($orRs as $orR) {
            $tmpOrRs[$orR['order_id']] = $orR;
        }


        foreach ($orders as $log) {
            $log->update_at = time_format($log->updated_at);
            $log->recycle   = isset($tmpOrRs[$log->id]) ? $tmpOrRs[$log->id] : null;
        }


        $data = [
            'orders' => $orders,
        ];

        return view('Commission::shop.recycle', $data);
    }

    public function shop_orders(Request $request)
    {
        $logs = OrderRecycle::query()->where('customer_id', current_customer()->id)->with([
            'order',
            'order_product'
        ]);
        $logs = $logs->orderByDesc('id')->paginate(perPage());

        foreach ($logs->items() as $log) {
            $log->update_at = time_format($log->updated_at);
        }
        return response()->json([
            'orders' => $logs,
        ]);
    }
}
