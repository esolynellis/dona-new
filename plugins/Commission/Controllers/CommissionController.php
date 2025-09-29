<?php


namespace Plugin\Commission\Controllers;

use app\model\Licenses;
use Beike\Admin\Http\Controllers\Controller;
use Beike\Services\CurrencyService;
use Beike\Shop\View\Components\Breadcrumb;
use Beike\Models\Customer;
use Beike\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Mockery\Exception;
use Plugin\Commission\Models\CommissionWithdrawalGroup;
use Plugin\Commission\Models\CommissionAmountLog;
use Plugin\Commission\Models\CommissionCustomers;
use Plugin\Commission\Models\CommissionUser;
use Plugin\Commission\Resources\CommissionAmountLogsResource;

class CommissionController extends Controller
{
    public function users_index()
    {
        $plugin = app('plugin')->getPlugin('commission');
        $data = [
            'name' => '会员管理',
            'description' => $plugin->getLocaleDescription(),
        ];

        return view('Commission::admin.users', $data);
    }

    public function cashApplyNewAlert(Request $request)
    {
        //1.注意要把Static里面的mp3复制到，/public/Commission/sounds/notification.mp3里,2.把浏览器静默状态调整为可以在线播放声音
// 定义目标文件路径和源文件路径
        $targetDir  = public_path('plugin/Commission/sounds');
        $targetFile = $targetDir . '/notification.mp3';
        $sourceFile = base_path('plugins/Commission/Static/sounds/notification.mp3');

        // 检查目标目录是否存在，不存在则创建
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 检查目标文件是否存在，如果不存在且源文件存在，则复制文件
        if (!file_exists($targetFile) && file_exists($sourceFile)) {
            copy($sourceFile, $targetFile);
        }

        $logId = $request->log_id;
        if ($logId) {
            CommissionAmountLog::query()->where('id', $logId)->update(['is_notify' => 1]);
        }
        $log = CommissionAmountLog::query()->where('is_notify', 0)->whereNotNull('apply_data')->first();

        return response()->json([
            'code' => 0,
            'msg' => '有新的提现申请',
            'data' => ['need_alert' => $log ? 1 : 0, 'log_id' => $log ? $log->id : 0]
        ]);
    }


    public function checkAdd(Request $request)
    {
        $customer_ids = $request->customer_ids;
        $commissionUsers = CommissionUser::query()->whereIn('customer_id', $customer_ids)->get([
            'id',
            'customer_id',
            'status'
        ]);
        $normalCommissionIDs = [];
        $freezeCommissionIDs = [];
        $applyCommissionIDs = [];
        $allCommissionIDs = [];
        foreach ($commissionUsers as $commissionUser) {
            if ($commissionUser->status == 1) {
                $applyCommissionIDs[] = $commissionUser->customer_id;
            } else if ($commissionUser->status == 3) {
                $freezeCommissionIDs[] = $commissionUser->customer_id;
            } else {
                $normalCommissionIDs[] = $commissionUser->customer_id;
            }
            $allCommissionIDs[] = $commissionUser->customer_id;
        }
        return response()->json([
            'code' => 0,
            'msg' => '',
            'allCommissionIDs' => $allCommissionIDs,
            'normalCommissionIDs' => $normalCommissionIDs,
            'freezeCommissionIDs' => $freezeCommissionIDs,
            'applyCommissionIDs' => $applyCommissionIDs
        ]);
    }

    public function users(Request $request)
    {
        $users = CommissionUser::query()->with(['customer'])->orderByDesc('id');
        if ($request->q) {
            $q = urldecode($request->q);
            $customers = Customer::query()->orWhere('name', 'like', '%' . $q . '%')->orWhere('email', 'like', '%' . $q . '%')->get(['id']);

            $curstomerIds = [];
            $curstomerIds[] = -1;
            if ($customers->count() > 0) {
                foreach ($customers as $customer) {
                    $curstomerIds[] = $customer->id;
                }
            }
            $users = $users->orWhereIn('customer_id', $curstomerIds);
            $users = $users->orWhere('code', 'like', '%' . $q . '%');
        }
        $parent = null;
        if ($request->parent_customer_id) {
            $curstomerIds = [];
            $curstomerIds[] = -1;
            $parentCommissionUser = CommissionUser::query()->where('customer_id', $request->parent_customer_id)->first();//上级
            if ($parentCommissionUser) {
                $parent = $parentCommissionUser->customer;
                $childCommissionCustomers = CommissionCustomers::query()->where('commission_user_id', $parentCommissionUser->id)->get();//下级人员关系
                if ($childCommissionCustomers->count() > 0) {
                    //只能查这些人了
                    foreach ($childCommissionCustomers as $childCommissionCustomer) {
                        $curstomerIds[] = $childCommissionCustomer->customer_id;
                    }
                }
            }
            $users = $users->orWhereIn('customer_id', $curstomerIds);
        }

        if ($request->start_at) {
            $users = $users->where('created_at', '>=', $request->start_at);
        }
        if ($request->end_at) {
            $users = $users->where('created_at', '<=', $request->end_at);
        }

        $users = $users->paginate(perPage());

        $items = $users->items();

        //$groups  = CustomerGroupRepo::list();
        //$options = [];
        //foreach ($groups as $group) {
        //$options[$group->id] = $group;
        // }
        $plugin = plugin_setting('commission');

        $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;

        $customerIDs = [];
        foreach ($items as $item) {
            $item->parent_user_id = 0;
            $item->parent_user_name = "无";
            $item->account = empty($item->customer) ? '用户被删除' : $item->customer->email;
            $item->rate1 = $item->rate_1 == -1 ? $plugin['rate1'] : $item->rate_1;
            $item->rate2 = $item->rate_2 == -1 ? $plugin['rate2'] : $item->rate_2;
            $item->rate3 = $item->rate_3 == -1 ? $plugin['rate3'] : $item->rate_3;
            $customerIDs[] = $item->customer_id;
        }


        if ($add_balance_type != 0) {
            $walletBalances = $this->getWalletUserBalance($customerIDs);
            foreach ($items as $item) {
                if (is_string($walletBalances)) {
                    $item->balance = $walletBalances;
                } else {
                    $item->balance = isset($walletBalances[$item->customer_id]) ? $walletBalances[$item->customer_id]['balance'] : 0;
                }
            }
        }

        $commissionCustomers = CommissionCustomers::query()->whereIn('customer_id', $customerIDs)->get();//找到这些人的上级
        if (!empty($commissionCustomers)) {
            $tmpItem2CommissionUserID = [];//这些人对应的的上级关系
            $ccIDs = [];
            foreach ($commissionCustomers as $commissionCustomer) {
                $ccIDs[] = $commissionCustomer->commission_user_id;
                $tmpItem2CommissionUserID[$commissionCustomer->customer_id] = $commissionCustomer->commission_user_id;
            }
            $commissionUsers = CommissionUser::query()->whereIn('id', $ccIDs)->get();//再找上级人员的consumerID
            $parentUserIDs = [];
            $tmpCommissionUser2CustomerID = [];//上线对应的的customer关系
            foreach ($commissionUsers as $commissionUser) {
                $parentUserIDs[] = $commissionUser->customer_id;
                $tmpCommissionUser2CustomerID[$commissionUser->id] = $commissionUser->customer_id;
            }
            $tmpParentCustomers = [];
            $parentCustomers = Customer::query()->whereIn("id", $parentUserIDs)->get([
                "email",
                "id"
            ]);//对应的上级人员
            foreach ($parentCustomers as $parentCustomer) {
                $tmpParentCustomers[$parentCustomer->id] = $parentCustomer;
            }

            foreach ($items as $item) {
                if (!isset($tmpItem2CommissionUserID[$item->customer_id])) {
                    continue;
                }
                $commission_user_id = $tmpItem2CommissionUserID[$item->customer_id];//找到上级的会员ID
                if (!isset($tmpCommissionUser2CustomerID[$commission_user_id])) {
                    continue;
                }
                $customer_id = $tmpCommissionUser2CustomerID[$commission_user_id];//上级的id
                if (!isset($tmpParentCustomers[$customer_id])) {
                    continue;
                }

                $item->parent_user_id = $tmpParentCustomers[$customer_id]->id;
                $item->parent_user_name = $tmpParentCustomers[$customer_id]->email;
            }
        }


        return response()->json([
            'users' => $users,
            'systemRate' => $plugin,
            'parent' => $parent,
        ]);
    }

    public function users_delete(Request $request)
    {
        $customer_id = $request->customer_id;
        $commissionUser = CommissionUser::query()->where('customer_id', $customer_id)->first();
        if (empty($commissionUser)) {
            return response()->json([
                'code' => -1,
                'msg' => '会员不存在'
            ]);
        }
        $commissionCustomer = CommissionCustomers::query()->where('customer_id', $customer_id)->first();
        if ($commissionCustomer) {
            return response()->json([
                'code' => -2,
                'msg' => '会员有分销客户，不能删除，只能冻结'
            ]);
        }
        $commissionAmountLog = CommissionAmountLog::query()->where('customer_id', $customer_id)->first();
        if ($commissionAmountLog) {
            return response()->json([
                'code' => -2,
                'msg' => '会员有分销订单，不能删除，只能冻结'
            ]);
        }
        $commissionUser->delete();
        return response()->json([
            'code' => 0,
            'msg' => '删除成功'
        ]);

    }


    public function balance_update(Request $request)
    {

        $customer_id = $request->customer_id;
        $commissionUser = CommissionUser::query()->where('customer_id', $customer_id)->first();
        if (empty($commissionUser)) {
            return response()->json([
                'code' => -1,
                'msg' => '会员不存在'
            ]);
        }

        $amount = $request->amount;
        if ($amount == 0) {
            return response()->json([
                'code' => -2,
                'msg' => '金额不能为0',
            ]);
        }
        $amount = bcmul(round($amount, 2), 100);

        $plugin = plugin_setting('commission');
        $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;
        if ($add_balance_type === 1) {
            if ($amount < 0 && $commissionUser->balance < -$amount) {
                return response()->json([
                    'code' => -2,
                    'msg' => '余额不足',
                ]);
            }
        }
        DB::beginTransaction();
        try {
            $amount1Log = [
                'commission_user_id' => $commissionUser->id,
                'customer_id' => $commissionUser->customer_id,
                'order_id' => 0,
                'action' => $amount > 0 ? CommissionAmountLog::ACTION_SYS_ADD : CommissionAmountLog::ACTION_SYS_SUB,
                'rate' => 0,
                'level' => 0,
                'amount' => $amount,
                'audit_note' => $request->note,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
                'status' => 1,
            ];
            DB::table('commission_amount_logs')->insertGetId($amount1Log);

            if ($add_balance_type == 0) {
                CommissionUser::query()->where('customer_id', $customer_id)->update(['balance' => bcadd($commissionUser->balance, $amount)]);
            } else {
                //结算到钱包
                $rs = $this->updateWalletBalance($customer_id, bcdiv($amount, 100, 2), $request->note, "commission_" . ($amount > 0 ? CommissionAmountLog::ACTION_SYS_ADD : CommissionAmountLog::ACTION_SYS_SUB));
                if ($rs != 'success') {
                    DB::rollBack();
                    return response()->json([
                        'code' => -2,
                        'msg' => $rs,
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'code' => -2,
                'msg' => '操作异常，结算失败',
                'exception' => json_encode($exception, true),
            ]);
        }


        return response()->json([
            'code' => 0,
            'msg' => '操作成功'
        ]);

    }


    private function updateWalletBalance($customer_id, $amount, $note, $action, $order_id = 0)
    {
        $plugin = plugin_setting("wallet_pay");
        if (empty($plugin) || $plugin['status'] == 0) {
            return "请开启钱包插件";
        } else {
            return \Plugin\WalletPay\Services\WalletService::updateBalance($customer_id, $amount, $note, false, $action, $order_id);
        }
    }

    private function getWalletUserBalance($customer_ids)
    {
        $plugin = plugin_setting("wallet_pay");
        if (empty($plugin) || $plugin['status'] == 0) {
            return "请开启钱包插件";
        } else {
            $walletBalances = \Plugin\WalletPay\Services\WalletService::getBalance($customer_ids);
            $tmpWalletBalances = [];
            foreach ($walletBalances as $walletBalance) {
                unset($walletBalance->balance_format);
                $walletBalance->balance = bcmul($walletBalance->balance, 100);
                $tmpWalletBalances[$walletBalance->customer_id] = $walletBalance;
            }
            return $tmpWalletBalances;
        }
    }

    public function close_balance(Request $request)
    {

        $customer_id = $request->customer_id;
        $commissionUser = CommissionUser::query()->where('customer_id', $customer_id)->first();
        if (empty($commissionUser)) {
            return response()->json([
                'code' => -1,
                'msg' => '会员不存在'
            ]);
        }

        $plugin = plugin_setting('commission');
        $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;
        if ($add_balance_type != 0) {//可能是钱包
            $walletBalances = $this->getWalletUserBalance([$commissionUser->customer_id]);
            if (is_string($walletBalances)) {
                DB::rollBack();
                return response()->json([
                    'code' => -2,
                    'msg' => $walletBalances,
                ]);
            }
            if (isset($walletBalances[$commissionUser->customer_id])) {
                $balance = $walletBalances[$commissionUser->customer_id]->balance;
            } else {
                $balance = 0;
            }
        } else {
            $balance = $commissionUser->balance;
        }
        $amount = $balance;
        if ($request->amount) {
            $amount = $request->amount;
        }
        if ($amount > $balance) {
            $amount = $balance;
        }

        if ($amount <= 0) {
            return response()->json([
                'code' => -2,
                'msg' => trans("Commission::orders.insufficient_balance"),
            ]);
        }

        DB::beginTransaction();
        try {
            $amount1Log = [
                'commission_user_id' => $commissionUser->id,
                'customer_id' => $commissionUser->customer_id,
                'order_id' => 0,
                'action' => CommissionAmountLog::ACTION_CLOSE,
                'rate' => 0,
                'level' => 0,
                'amount' => 0 - $amount,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
                'status' => 3,
            ];
            DB::table('commission_amount_logs')->insertGetId($amount1Log);


            if ($add_balance_type == 0) {
                CommissionUser::query()->where('customer_id', $customer_id)->update(['balance' => bcsub($commissionUser->balance, $amount)]);//申请就减扣余额
            } else {
                $walletBalances = $this->getWalletUserBalance([$commissionUser->customer_id]);
                if (is_string($walletBalances)) {
                    DB::rollBack();
                    return response()->json([
                        'code' => -2,
                        'msg' => $walletBalances,
                    ]);
                }
                //结算到钱包
                $rs = $this->updateWalletBalance($commissionUser->customer_id, -bcdiv($amount, 100, 2), "", "commission_" . CommissionAmountLog::ACTION_CLOSE);
                if ($rs != 'success') {
                    DB::rollBack();
                    return response()->json([
                        'code' => -2,
                        'msg' => $rs,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'code' => -2,
                'msg' => '结算异常，结算失败',
                'exception' => json_encode($exception, true),
            ]);
        }


        return response()->json([
            'code' => 0,
            'msg' => '结算成功'
        ]);

    }

    public function customer_user(Request $request)
    {
        $customer_id = $request->customer_id;
        $customer = Customer::query()->find($customer_id);
        if (empty($customer)) {
            return response()->json([
                'code' => -1,
                'msg' => '客户不存在'
            ]);
        }
        $status = $request->status;
        $commissionUser = CommissionUser::query()->where('customer_id', $customer_id)->first();
        if (empty($commissionUser)) {


            $commissionUser = [
                'customer_id' => $customer_id,
                'code' => $this->getCode($customer_id),
                'status' => $status,
                'rate_1' => $request->rate1 ?? -1,
                'rate_2' => $request->rate2 ?? -1,
                'rate_3' => $request->rate3 ?? -1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            DB::table('commission_users')->insertGetId($commissionUser);
            return response()->json([
                'code' => 0,
                'msg' => '加入成功'
            ]);
        } else {
            $commissionUser->update(['status' => $status]);
            return response()->json([
                'code' => 0,
                'msg' => '修改成功'
            ]);
        }

    }

    public function customer_user_rate(Request $request)
    {
        $customer_id = $request->customer_id;
        $customer = Customer::query()->find($customer_id);
        if (empty($customer)) {
            return response()->json([
                'code' => -1,
                'msg' => '客户不存在'
            ]);
        }
        $commissionUser = CommissionUser::query()->where('customer_id', $customer_id)->first();
        if (empty($commissionUser)) {
            return response()->json([
                'code' => -1,
                'msg' => '会员不存在'
            ]);
        } else {
            $commissionUser->update([
                'rate_1' => $request->rate1,
                'rate_2' => $request->rate2,
                'rate_3' => $request->rate3
            ]);
            return response()->json([
                'code' => 0,
                'msg' => '修改成功'
            ]);
        }
    }

    private function getCode($customer_id)
    {
        $code = md5($customer_id . '-' . time() . '-' . rand(100, 1000) . '-' . time());
        $code = substr($code, 8, 8);;
        //有重复的去掉
        $oldComUsers = CommissionUser::query()->where('code', $code)->first();
        if ($oldComUsers) {
            $code = $this->getCCode($customer_id);
        }
        return $code;
    }


    public function orders_index()
    {
        $plugin = app('plugin')->getPlugin('commission');
        $data = [
            'name' => '佣金变更记录',
            'description' => $plugin->getLocaleDescription(),
        ];

        return view('Commission::admin.orders', $data);
    }

    public function cash_apply_logs_index()
    {
        $plugin = app('plugin')->getPlugin('commission');
        $data = [
            'name' => '提现记录',
            'description' => $plugin->getLocaleDescription(),
        ];

        return view('Commission::admin.cash_apply_orders', $data);
    }

    public function orders(Request $request)
    {
        $logs = CommissionAmountLog::query()->with([
            'order',
            'commissionUser',
            'customer'
        ]);

        $isCashApply = $request->is_cash_apply ? $request->is_cash_apply : 0;
        if ($isCashApply) {
            $logs = $logs->where(function ($query) {
                $query->whereIn('action', [
                    CommissionAmountLog::ACTION_APPLY_CLOSE,
                    CommissionAmountLog::ACTION_REFUSE_CLOSE
                ])->orWhereNotNull('apply_data');
            });

        } else {
            $logs = $logs->whereNotIn('action', [
                CommissionAmountLog::ACTION_APPLY_CLOSE,
                CommissionAmountLog::ACTION_REFUSE_CLOSE
            ])->whereNull('apply_data');


        }
        if ($request->q) {
            $q = urldecode($request->q);

            $customers = Customer::query()->orWhere('name', 'like', '%' . $q . '%')->orWhere('email', 'like', '%' . $q . '%')->get(['id']);

            $curstomerIds = [];
            $curstomerIds[] = -1;
            if ($customers->count() > 0) {
                foreach ($customers as $customer) {
                    $curstomerIds[] = $customer->id;
                }
            }
            $logs = $logs->orWhereIn('customer_id', $curstomerIds);

            $orders = Order::query()->where('number', 'like', '%' . $q . '%')->get(['id']);
            $orderIds = [];
            $orderIds[] = -1;
            if ($orders->count() > 0) {
                foreach ($orders as $order) {
                    $orderIds[] = $order->id;
                }
            }
            $logs = $logs->orWhereIn('order_id', $orderIds);

        }

        if ($request->start_at) {
            $logs = $logs->where('created_at', '>=', $request->start_at);
        }
        if ($request->end_at) {
            $logs = $logs->where('created_at', '<=', $request->end_at);
        }

        $logs = $logs->orderByDesc('id')->paginate(perPage());
        CommissionAmountLogsResource::collection($logs);
        return response()->json($logs);
    }


    public function refund_order(Request $request)
    {
        $log_id = $request->log_id;
        $commissionAmountLog = CommissionAmountLog::query()->where('id', $log_id)->whereIn('status', [
            1,
            5
        ])->first();
        if (empty($commissionAmountLog)) {
            return response()->json([
                'code' => -1,
                'msg' => '这条记录无法退款'
            ]);
        }
        $commissionUser = CommissionUser::query()->where('id', $commissionAmountLog->commission_user_id)->first();//分销会员
        $balance = $commissionUser->balance;

        $plugin = plugin_setting('commission');
        $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;
        if ($add_balance_type != 0) {//可能是钱包
            $walletBalances = $this->getWalletUserBalance([$commissionUser->customer_id]);
            if (is_string($walletBalances)) {
                DB::rollBack();
                return response()->json([
                    'code' => -2,
                    'msg' => $walletBalances,
                ]);
            }
            if (isset($walletBalances[$commissionUser->customer_id])) {
                $balance = $walletBalances[$commissionUser->customer_id]->balance;
            } else {
                $balance = 0;
            }
        }

        $amount = $commissionAmountLog->amount;
        if ($balance < $amount) {
            return response()->json([
                'code' => -2,
                'msg' => trans("Commission::orders.insufficient_balance"),
            ]);
        }

        DB::beginTransaction();
        try {
            //$after_amount = bcsub($balance, $amount, 2);
            $amount1Log = [
                'commission_user_id' => $commissionAmountLog->commission_user_id,
                'customer_id' => $commissionAmountLog->customer_id,
                'order_id' => $commissionAmountLog->order_id,
                'action' => CommissionAmountLog::ACTION_REFUND,
                'rate' => 0,
                'level' => 0,
                'amount' => -$amount,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'status' => 2,
            ];
            DB::table('commission_amount_logs')->insertGetId($amount1Log);
            //$commissionUser->update(['balance' => $after_amount]);
            CommissionAmountLog::query()->where('id', $log_id)->update([
                'status' => 2,
                'updated_at' => Carbon::now(),
            ]);

            //更新到钱包
            if ($add_balance_type == 0) {
                CommissionUser::query()->where('customer_id', $commissionAmountLog->customer_id)->update(['balance' => bcsub($commissionUser->balance, $amount)]);//申请就减扣余额
            } else {
                $walletBalances = $this->getWalletUserBalance([$commissionUser->customer_id]);
                if (is_string($walletBalances)) {
                    DB::rollBack();
                    return response()->json([
                        'code' => -2,
                        'msg' => $walletBalances,
                    ]);
                }
                //$order = Order::query()->where('id', $commissionAmountLog->order_id)->first();
                //结算到钱包
                $rs = $this->updateWalletBalance($commissionUser->customer_id, -bcdiv($amount, 100, 2), "", "commission_" . CommissionAmountLog::ACTION_REFUND, $commissionAmountLog->order_id);
                if ($rs != 'success') {
                    DB::rollBack();
                    return response()->json([
                        'code' => -2,
                        'msg' => $rs,
                    ]);
                }
            }


            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'code' => -2,
                'msg' => '退款异常，退款失败'
            ]);
        }


        return response()->json([
            'code' => 0,
            'msg' => '退款成功'
        ]);

    }


    public function shop_orders_index(Request $request)
    {
        $customer = current_customer();
        if (!$customer) {
            $success_url = shop_route('login.index');
            return redirect($success_url);
        }
        $plugin = app('plugin')->getPlugin('commission');


        $pluginSetting = plugin_setting('commission');
        $withdrawal_btn_display = isset($pluginSetting['withdrawal_btn_display']) ? $pluginSetting['withdrawal_btn_display'] : 1;

        $tmpGroups = [];
        if ($withdrawal_btn_display == 1) {
            $groups = CommissionWithdrawalGroup::query()->with(['items'])->get();
            foreach ($groups as $group) {
                if ($group->items->count() > 0) {
                    foreach ($group->items as $item) {
                        $item->value = "";
                    }
                    $tmpGroups[] = $group;
                }
            }
            if (empty($tmpGroups)) {//没有可用的提现方式
                $withdrawal_btn_display = 0;
            }
        }
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'title' => trans('shop/common.home'),
            'url' => shop_route('home.index'),
        ];
        $breadcrumbs[] = [
            'title' => trans('shop/account.index'),
            'url' => shop_route('account.index'),
        ];
        $breadcrumbs[] = [
            'title' => trans('Commission::orders.commission_amount_logs'),
            'url' => shop_route('shop_order_index'),
        ];
        $data = [
            'withdrawal_btn_display' => $withdrawal_btn_display,
            'breadcrumbs' => $breadcrumbs,
            'name' => '佣金变更记录',
            'withdrawal_groups' => $tmpGroups,
            'description' => $plugin->getLocaleDescription(),
        ];

        return view('Commission::shop.orders', $data);
    }

    public function shop_orders(Request $request)
    {
        $customer = current_customer();
        if (!$customer) {
            die();
        }
        $logs = CommissionAmountLog::query()->where('customer_id', current_customer()->id)->with([
            'order',
            'commissionUser',
            'customer'
        ]);
        if ($request->q) {
            $q = urldecode($request->q);

            $orders = Order::query()->where('number', 'like', '%' . $q . '%')->get(['id']);
            $orderIds = [];
            $orderIds[] = -1;
            if ($orders->count() > 0) {
                foreach ($orders as $order) {
                    $orderIds[] = $order->id;
                }
            }
            $logs = $logs->whereIn('order_id', $orderIds);

        }

        if ($request->start_at) {
            $logs = $logs->where('created_at', '>=', $request->start_at);
        }
        if ($request->end_at) {
            $logs = $logs->where('created_at', '<=', $request->end_at);
        }

        $logs = $logs->orderByDesc('id')->paginate(perPage());


        $comUser = CommissionUser::query()->where('customer_id', current_customer()->id)->first();

        //$shareUrl = env('APP_URL') . '?cid=' . $comUser->code;

        $plugin = plugin_setting('commission');
        $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;
        if ($add_balance_type != 0) {
            $walletBalances = $this->getWalletUserBalance([$comUser->customer_id]);
            if (is_string($walletBalances)) {
                $comUser->balance = $walletBalances;
            } else {
                $comUser->balance = isset($walletBalances[$comUser->customer_id]) ? $walletBalances[$comUser->customer_id]['balance'] : 0;
            }
        }

        $comUser->balance = CurrencyService::getInstance()->convert($comUser->balance, system_setting('base.currency'), current_currency_code());//转化为显示币
        $comUser->total_amount = CurrencyService::getInstance()->convert($comUser->total_amount, system_setting('base.currency'), current_currency_code());//转化为显示币
        $comUser->balance_progress = CurrencyService::getInstance()->convert($comUser->balance_progress, system_setting('base.currency'), current_currency_code());//转化为显示币
        //print_r($comUser->balance);exit;
        $customer = current_customer();


        CommissionAmountLogsResource::collection($logs);
        return response()->json([
            'logs' => $logs,
            'commission_user' => $comUser,
            'customer' => $customer
        ]);
    }


    public function shop_cash_apply(Request $request)
    {
        $customer = current_customer();
        if (!$customer) {
            die();
        }

        if (!$request->amount || !is_numeric($request->amount) || $request->amount <= 0) {
            return response()->json([
                'code' => '-1',
                'msg' => '参数错误'
            ]);
        }
        $plugin = plugin_setting('commission');

        if (isset($plugin['cash_limit']) && $request->amount < $plugin['cash_limit']) {
            return response()->json([
                'code' => '-1',
                'msg' => trans('Commission::orders.cash_limit_alert') . $plugin['cash_limit']
            ]);
        }

        $items = $request->withdrawal_group_items;
        $tmpItems = [];
        foreach ($items as $item) {
            $tmpItems[] = [
                'name' => $item['description']['content'],
                'value' => $item['value']
            ];
        }
        $group = CommissionWithdrawalGroup::query()->where('id', $request->withdrawal_id)->first();
        $apply_data = [
            'amount' => $request->amount,
            'withdrawal_group_name' => $group->name,
            'withdrawal_items' => $tmpItems,
        ];
        $commissionUser = CommissionUser::query()->where('customer_id', $customer->id)->first();
        if (empty($commissionUser)) {
            return response()->json([
                'code' => -1,
                'msg' => '会员不存在'
            ]);
        }
        if ($commissionUser->is_staff) {
            return response()->json([
                'code' => -1,
                'msg' => '员工提现请联系管理员'
            ]);
        }

        $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;
        if ($add_balance_type != 0) {
            $walletBalances = $this->getWalletUserBalance([$commissionUser->customer_id]);
            if (is_string($walletBalances)) {
                $commissionUser->balance = 0;
            } else {
                $commissionUser->balance = isset($walletBalances[$commissionUser->customer_id]) ? $walletBalances[$commissionUser->customer_id]['balance'] : 0;
            }
        }

        $amount = $commissionUser->balance;
        if ($request->amount) {
            $amount = $request->amount * 100;
        }
        if ($request->amount == 0 || $amount > $commissionUser->balance) {
            return response()->json([
                'code' => -1,
                'msg' => trans('Commission::orders.insufficient_balance')
            ]);

        }
        $amount1Log = [
            'commission_user_id' => $commissionUser->id,
            'customer_id' => $commissionUser->customer_id,
            'order_id' => 0,
            'action' => CommissionAmountLog::ACTION_APPLY_CLOSE,
            'rate' => 0,
            'level' => 0,
            'status' => 5,
            'apply_data' => json_encode($apply_data),
            'is_notify'=>0,
            'amount' => 0 - $amount,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ];

        DB::table('commission_amount_logs')->insertGetId($amount1Log);

        if ($add_balance_type == 0) {
            $commissionUser->update(['balance' => bcsub($commissionUser->balance, $amount)]);//申请就减扣余额
        } else {
            //结算到钱包
            $rs = $this->updateWalletBalance($commissionUser->customer_id, -bcdiv($amount, 100, 2), "Commission withdrawal of cash", 'commission_' . CommissionAmountLog::ACTION_APPLY_CLOSE);
            if ($rs != 'success') {
                DB::rollBack();
                return response()->json([
                    'code' => -2,
                    'msg' => $rs,
                ]);
            }
        }


        DB::commit();
        return response()->json([
            'code' => 0,
            'msg' => '提现申请成功'
        ]);

    }

    public function audit_cash_apply(Request $request)
    {

        $log_id = $request->id;

        $commissionAmountLog = CommissionAmountLog::query()->where('id', $log_id)->where('action', CommissionAmountLog::ACTION_APPLY_CLOSE)->first();

        if (empty($commissionAmountLog)) {
            return response()->json([
                'code' => -1,
                'msg' => '记录不存在'
            ]);
        }
        $commissionUser = CommissionUser::query()->where('customer_id', $commissionAmountLog->customer_id)->first();
        if (empty($commissionUser)) {
            return response()->json([
                'code' => -1,
                'msg' => '会员不存在'
            ]);
        }


        DB::beginTransaction();
        try {
            //变更这条记录
            if ($request->action == 'refuse') {
                $amount = $commissionAmountLog->amount;
                $commissionAmountLog->update([
                    'action' => CommissionAmountLog::ACTION_REFUSE_CLOSE,
                    'audit_note' => $request->audit_note ? $request->audit_note : '',
                    'status' => 4,
                ]);

                $plugin = plugin_setting('commission');
                $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;

                if ($add_balance_type == 0) {
                    CommissionUser::query()->where('customer_id', $commissionUser->customer_id)->update(['balance' => bcsub($commissionUser->balance, $amount)]);//加回去
                } else {
                    //结算到钱包(把钱加回去)
                    $note = $request->audit_note ? $request->audit_note : '';
                    $rs = $this->updateWalletBalance($commissionUser->customer_id, -bcdiv($amount, 100, 2), $note, 'commission_' . CommissionAmountLog::ACTION_REFUSE_CLOSE);
                    if ($rs != 'success') {
                        DB::rollBack();
                        return response()->json([
                            'code' => -2,
                            'msg' => $rs,
                        ]);
                    }
                }
            } else {//同意
                $commissionAmountLog->update([
                    'action' => CommissionAmountLog::ACTION_CLOSE,
                    'audit_note' => $request->audit_note ? $request->audit_note : '',
                    'status' => 1,
                ]);

                $amount = $commissionAmountLog->amount;
                CommissionUser::query()->where('customer_id', $commissionUser->customer_id)->update(['withdrawal_balance' => bcadd($commissionUser->withdrawal_balance, -$amount)]);
            }
            DB::commit();
            return response()->json([
                'code' => 0,
                'msg' => '提现成功'
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'code' => -2,
                'msg' => '结算异常，结算失败',
                'exception' => json_encode($exception, true),
            ]);
        }
    }


    public function shop_users_index(Request $request)
    {

        $customer = current_customer();
        if (!$customer) {
            $success_url = shop_route('login.index');
            return redirect($success_url);
        }

        $plugin = app('plugin')->getPlugin('commission');

        $breadcrumbs = [];
        $breadcrumbs[] = [
            'title' => trans('shop/common.home'),
            'url' => shop_route('home.index'),
        ];
        $breadcrumbs[] = [
            'title' => trans('shop/account.index'),
            'url' => shop_route('account.index'),
        ];
        $breadcrumbs[] = [
            'title' => trans('Commission::orders.membership'),
            'url' => shop_route('shop_users_index'),
        ];

        $data = [
            'breadcrumbs' => $breadcrumbs,
            'name' => '会员上下级',
            'description' => $plugin->getLocaleDescription(),
        ];

        return view('Commission::shop.users', $data);
    }

    public function shop_users(Request $request)
    {
        // $plugin   = plugin_setting('commission');
        $customer = current_customer();
        if (!$customer) {
            die();
        }

        $comUser = CommissionUser::query()->where('customer_id', $customer->id)->first();
        if (!$comUser) {
            die();
        }
        $shareUrl = env('APP_URL') . '?cid=' . $comUser->code;

        $l_promotion = plugin_setting('l_promotion');
        if ($l_promotion && $l_promotion['status'] == 1) {
            $parentPromoteUser = \Plugin\LPromotion\Models\PromotionUser::query()->where('customer_id', $customer->id)->first();
            if ($parentPromoteUser) {
                $shareUrl = $shareUrl . '&pid=' . $parentPromoteUser->code;
            }
        }

        $myParentCustomer = null;
        //我的上级
        $commissionCustomer = CommissionCustomers::query()->where('customer_id', $customer->id)->first();
        if ($commissionCustomer) {
            $commissionUser = CommissionUser::query()->where('id', $commissionCustomer->commission_user_id)->where('status', 2)->first();//分销会员
            if ($commissionUser) {
                $myParentCustomer = Customer::query()->where('id', $commissionUser->customer_id)->first();
                unset($myParentCustomer->password);
            }
        }

        //我的下级
        $users = CommissionUser::query()->with(['customer'])->orderByDesc('id');
        $curstomerIds = [];
        $curstomerIds[] = -1;
        $parentCommissionUser = CommissionUser::query()->where('customer_id', $customer->id)->first();//我是上级
        if ($parentCommissionUser) {
            $childCommissionCustomers = CommissionCustomers::query()->where('commission_user_id', $parentCommissionUser->id)->get();//下级人员关系
            if ($childCommissionCustomers->count() > 0) {
                //只能查这些人了
                foreach ($childCommissionCustomers as $childCommissionCustomer) {
                    $curstomerIds[] = $childCommissionCustomer->customer_id;
                }
            }
        } else {
            return response()->json([
                'myParentCustomer' => $myParentCustomer,
                'users' => [],
                'shareUrl' => $shareUrl
            ]);
        }


        if ($request->q) {
            $q = urldecode($request->q);
            $customers = Customer::query()->orWhere('name', 'like', '%' . $q . '%')->orWhere('email', 'like', '%' . $q . '%')->get(['id']);
            $curstomerIds2 = [];
            $curstomerIds2[] = -1;
            if ($customers->count() > 0) {
                foreach ($customers as $customer) {
                    if (in_array($customer->id, $curstomerIds)) {
                        $curstomerIds2[] = $customer->id;
                    }
                }
            }
            $curstomerIds = $curstomerIds2;
            $users = $users->orWhere('code', 'like', '%' . $q . '%');
        }

        $users = $users->orWhereIn('customer_id', $curstomerIds);

        $users = $users->paginate(perPage());

        $items = $users->items();

        foreach ($items as $item) {
            $item->account = empty($item->customer) ? '用户被删除' : $item->customer->email;
        }

        return response()->json([
            'myParentCustomer' => $myParentCustomer,
            'users' => $users,
            'shareUrl' => $shareUrl
        ]);
    }

    public function task()
    {
        $plugin = plugin_setting('commission');
        $time = null;
        if (isset($plugin['settlement_period']) && $plugin['settlement_period'] > 0) {//有设定结算周期
            $time = date("Y-m-d H:i:s", strtotime(" -{$plugin['settlement_period']} day"));
        }

        if ($time == null) {//立即计算
            $cals = CommissionAmountLog::query()->where('status', 5)->where('action', CommissionAmountLog::ACTION_ORDER)->get();
        } else {
            $cals = CommissionAmountLog::query()->where('created_at', '<=', $time)->where('status', 5)->where('action', CommissionAmountLog::ACTION_ORDER)->get();
        }
        if ($cals->count() > 0) {
            $cUserIds = [];
            $cUserBalances = [];
            DB::beginTransaction();
            try {


                $plugin = plugin_setting('commission');
                $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;

                foreach ($cals as $cal) {
                    $uid = $cal->commission_user_id;
                    $cUserIds[] = $uid;
                    $amount = $cal->amount;
                    $cUserBalances[$uid] = isset($cUserBalances[$uid]) ? bcadd($cUserBalances[$uid], $amount) : $amount;
                    $cal->update([
                        'status' => 1
                    ]);

                    if ($add_balance_type != 0) {
                        //结算到钱包
                        $rs = $this->updateWalletBalance($cal->customer_id, bcdiv($amount, 100, 2), 'commission level ' . $cal->level, 'commission_' . CommissionAmountLog::ACTION_SCHEDULE_CLOSE, $cal->order_id);
                        if ($rs != 'success') {
                            DB::rollBack();
                            break;
                        }
                    }
                }


                $cusers = CommissionUser::query()->whereIn('id', $cUserIds)->get();
                foreach ($cusers as $cuser) {
                    $uid = $cuser->id;
                    $after_amount = bcadd($cuser->balance, $cUserBalances[$uid]);

                    if ($add_balance_type == 0) {
                        $cuser->update([
                            'balance' => $after_amount,
                            'balance_progress' => bcsub($cuser->balance_progress, $cUserBalances[$uid]),
                            'total_amount' => bcadd($cuser->total_amount, $cUserBalances[$uid])
                        ]);
                    } else {
                        //已结算到了钱包，这里就不再进行计算 $balance了
                        $cuser->update([
                            'balance_progress' => bcsub($cuser->balance_progress, $cUserBalances[$uid]),
                            'total_amount' => bcadd($cuser->total_amount, $cUserBalances[$uid])
                        ]);
                    }
                }
                DB::commit();
            } catch (Exception $exception) {
                DB::rollBack();
            }


            /**
             *
             * $after_amount = bcadd($commissionUser->balance, $amount);
             * $commissionUser->update([
             * 'balance'      => $after_amount,
             * 'total_amount' => bcadd($commissionUser->total_amount, $amount_1)
             * ]);
             */
        }
        return 'success';

    }
}
