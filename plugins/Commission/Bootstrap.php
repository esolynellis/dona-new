<?php

namespace Plugin\Commission;


use Beike\Models\Customer;
use Beike\Models\Order;
use Beike\Models\OrderProduct;
use Beike\Models\OrderTotal;
use Beike\Services\StateMachineService;
use Carbon\Carbon;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Plugin\Commission\Services\CommissionService;
use Plugin\Commission\Models\CommissionAmountLog;
use Plugin\Commission\Models\CommissionCustomers;
use Plugin\Commission\Models\CommissionOrder;
use Plugin\Commission\Models\CommissionUser;
use Beike\Seller\Repositories\OrderRepo;

class Bootstrap
{

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

    public function boot()
    {
        //加入接口权限
        add_hook_filter('role.permissions.plugin', function ($data) {
            $data[] = [
                'title'       => __('Commission::bootstrap.module_title'),
                'permissions' => [
                    [
                        'code' => 'commission_user_list',
                        'name' => __('Commission::bootstrap.permission_user_list'),
                    ],
                    [
                        'code' => 'commission_amount_list',
                        'name' => __('Commission::bootstrap.permission_amount_list'),
                    ],
                    [
                        'code' => 'commission_user_status_update',
                        'name' => __('Commission::bootstrap.permission_user_status'),
                    ],
                    [
                        'code' => 'commission_user_balance_update',
                        'name' => __('Commission::bootstrap.permission_user_balance'),
                    ],
                    [
                        'code' => 'commission_amount_refund',
                        'name' => __('Commission::bootstrap.permission_amount_refund'),
                    ],
                    [
                        'code' => 'commission_user_rate_update',
                        'name' => __('Commission::bootstrap.permission_user_rate'),
                    ],
                    [
                        'code' => 'withdrawal_group',
                        'name' => __('Commission::bootstrap.permission_withdrawal'),
                    ],
                    [
                        'code' => 'withdrawal_group',
                        'name' => __('Commission::bootstrap.permission_withdrawal_config'),
                    ],
                ],
            ];
            return $data;
        });

        //加入后台管理菜单
        add_hook_filter('admin.sidebar.customer.prefix', function ($data) {
            $data[] = 'users_index';
            $data[] = 'orders_index';
            $data[] = 'cash_apply_logs_index';
            $data[] = 'withdrawal_group';
            return $data;
        });

        add_hook_filter('admin.sidebar.customer_routes', function ($data) {
            $data[] = [
                'route'    => 'users_index',
                'prefixes' => ['users_index'],
                'title'    => __('Commission::bootstrap.menu_commission_users'),
            ];
            $data[] = [
                'route'    => 'orders_index',
                'prefixes' => ['orders_index'],
                'title'    => __('Commission::bootstrap.menu_commission_orders'),
            ];

            $data[] = [
                'route'    => 'cash_apply_logs_index',
                'prefixes' => ['cash_apply_logs_index'],
                'title'    => __('Commission::bootstrap.menu_withdrawal_manage'),
            ];
            $data[] = [
                'route'    => 'withdrawal_group.index',
                'prefixes' => ['withdrawal_group'],
                'title'    => __('Commission::bootstrap.menu_withdrawal_config'),
            ];
            return $data;
        }, 20240814);



        add_hook_blade('admin.header.user', function ($callback, $output, $data) {
            $view       = view('Commission::admin.cash_apply_orders_alert', $data)->render();
            return $view. $output;
        });

        //加入用户管理列表操作和状态列
        add_hook_blade('admin.customer.list.column', function ($callback, $output, $data) {
            return '<th>' . __('Commission::bootstrap.customer_list_title') . '</th>' . $output;
        });

        //加入公共事件
        add_hook_blade('admin.header.user', function ($callback, $output, $data) {
            $commission = plugin_setting('commission');
            $view       = view('Commission::admin.customer_button', $commission)->render();
            return $view . $output;
        });

        add_hook_blade('admin.customer.list.column_value', function ($callback, $output, $data) {
            return '<td commission_status></td>' . $output;


            return $output;
        });

        add_hook_blade('admin.customer.list.action', function ($callback, $output, $data) {
            $view = '<button type="button" class="btn btn-outline-success btn-sm ml-1" commission_add style="display: none" onclick="addCommission(this)" data-status="2">' . __('Commission::bootstrap.customer_btn_add') . '</button>';
            return $view . $output;
            return $output;
        });

        //前端请求检测是否是否为返佣路径
        add_hook_filter('footer.content', function ($data) {
            $commission = plugin_setting('commission');
            if (request()->cid) {//存在cid请求,缓存到cookie
                $cid             = app(Encrypter::class)->encrypt(request()->cid);
                $day             = isset($commission['cid_days']) ? $commission['cid_days'] : 365;
                $expireTimestamp = time() + (60 * 60 * 24) * $day;
                Cookie::queue('cid', $cid, $expireTimestamp);
            }
            return $data;

        });

        // 注册后检测是否有cid
        add_hook_action('service.account.register.after', function ($customer) {
            $commission = plugin_setting('commission');
            $cid        = Cookie::get('cid');
            //$cid = getCookies()['cid'];

            if ($cid) {
                $cid = app(Encrypter::class)->decrypt($cid);
                //Log::debug("========cid2=======" . $cid);
                if ($cid) {
                    //加入为分销者的子用户
                    $commissionUser = CommissionUser::query()->where('code', $cid)->first();
                    if ($commissionUser) {
                        $commissionCustomer = CommissionCustomers::query()->where('customer_id', $customer->id)->where('commission_user_id', $commissionUser->id)->first();
                        if (!$commissionCustomer) {
                            $commissionCustomer = [
                                'customer_id'        => $customer->id,
                                'commission_user_id' => $commissionUser->id,
                                'created_at'         => Carbon::now(),
                                'updated_at'         => Carbon::now()
                            ];
                            DB::table('commission_customers')->insertGetId($commissionCustomer);
                        }
                    }
                }
            }
            if (isset($commission['reg_join']) && $commission['reg_join'] == 1) {//注册后自动加入分销员
                $status         = 2;
                $commissionUser = CommissionUser::query()->where('customer_id', $customer->id)->first();
                if (empty($commissionUser)) {
                    $commissionUser = [
                        'customer_id' => $customer->id,
                        'code'        => $this->getCode($customer->id),
                        'status'      => $status,
                        'rate_1'      => -1,
                        'rate_2'      => -1,
                        'rate_3'      => -1,
                        'created_at'  => Carbon::now(),
                        'updated_at'  => Carbon::now()
                    ];
                    $cuID           = DB::table('commission_users')->insertGetId($commissionUser);


                    //赠送初始化金额
                    if (isset($commission['init_balance']) && $commission['init_balance'] > 0) {
                        $after_amount = bcmul($commission['init_balance'], 100);
                        $amount1Log   = [
                            'commission_user_id' => $cuID,
                            'customer_id'        => $customer->id,
                            'order_id'           => 0,
                            'action'             => CommissionAmountLog::ACTION_INIT,
                            'rate'               => 0,
                            'level'              => 0,
                            'amount'             => $after_amount,
                            'base_amount'        => 0,
                            'created_at'         => Carbon::now(),
                            'updated_at'         => Carbon::now()
                        ];
                        DB::table('commission_amount_logs')->insertGetId($amount1Log);

                        $plugin           = plugin_setting('commission');
                        $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;

                        $commissionUser = CommissionUser::query()->where('id', $cuID)->first();
                        if ($add_balance_type === 0) {
                            $commissionUser->update([
                                'balance'      => $after_amount,
                                'total_amount' => bcadd($commissionUser->total_amount, $after_amount, 2)
                            ]);
                        } else {
                            $commissionUser->update([
                                'total_amount' => bcadd($commissionUser->total_amount, $after_amount, 2)
                            ]);
                            //结算到钱包
                            $this->updateWalletBalance($customer->id, bcdiv($after_amount, 100, 2), '');

                        }

                    }
                }
            }


        }, 0);


        //创建订单后要执行,创建订单时记录订单数据，要不然后台变更订单状态可能会有问题
        add_hook_filter('checkout.confirm.data', function ($order) {
            $customer_id        = 0;
            $commission_user_id = 0;
            $commissionUser     = null;
            $commissionUser     = null;
            //获取订单商品信息
            $orderproduct = OrderProduct::query()->where('order_id',$order->id)->first();
            if ($order->customer_id) {//下单人员
                //需要变更下单人员会员等级
                $customer = Customer::query()->where('id', $order->customer_id)->first();
                if($customer){
                    if($orderproduct['product_id']==1&&$customer->customer_group_id==1){
                        $customer->customer_group_id = 2;
                    }elseif ($orderproduct['product_id']==2){
                        $customer->customer_group_id = 3;
                    }
                    $customer->save();
                }

                //检测是否有分销
                $commissionCustomer = CommissionCustomers::query()->where('customer_id', $order->customer_id)->first();
                if ($commissionCustomer) {
                    $commission_user_id = $commissionCustomer->commission_user_id;
                    $commissionUser     = CommissionUser::query()->where('id', $commissionCustomer->commission_user_id)->where('status', 2)->first();//分销会员
                    $customer_id        = $commissionUser->customer_id;
                }
            } else {
                $cid = Cookie::get('cid');
                if ($cid) {
                    try {
                        $cid = app(Encrypter::class)->decrypt($cid);

                        if ($cid) {
                            $commissionUser     = CommissionUser::query()->where('code', $cid)->where('status', 2)->first();
                            $commission_user_id = $commissionUser->id;
                            $customer_id        = $commissionUser->customer_id;
                        }
                    } catch (\Exception $exception) {

                    }
                }
            }


            if ($commissionUser) {
                //判断商品id为1和2的订单才返佣

                if($orderproduct){
                    if($orderproduct['product_id']==1||$orderproduct['product_id']==2){
                        CommissionOrder::query()->insert([
                            'customer_id'        => $customer_id,
                            'commission_user_id' => $commission_user_id,
                            'order_id'           => $order->id
                        ]);
                    }
                }
            }
            return $order;

        });


        add_hook_filter('service.state_machine.all_statuses', function ($data) {
            $pluginSeller = plugin_setting("multi_seller");
            if (!empty($pluginSeller) && $pluginSeller['status'] == 0) {
                $data[] = [
                    'status' => 'split',
                    'name'   => '',
                ];
            }

            return $data;
        });

        //支付后要执行addCommissionAmountLog
        add_hook_filter('service.state_machine.machines', function ($data) {
            $pluginSeller = plugin_setting("multi_seller");
            $fromStatus   = StateMachineService::UNPAID;
            $toStatus     = StateMachineService::PAID;

            if (!empty($pluginSeller) && $pluginSeller['status'] == 1) {
                $order = $data['order'];

                $hasSubOrder = Order::query()->where('parent_id', $order->id)->count();
                if ($order->parent_id > 0) {
                    return $data;
                } else if (!$hasSubOrder) {
                    $fromStatus = StateMachineService::UNPAID;
                    $toStatus   = StateMachineService::PAID;
                } else if ($order->parent_id == 0 && OrderRepo::hasSubOrder($order->id)) {
                    $fromStatus = StateMachineService::PAID;
                    $toStatus   = "split";
                }
            }
            $data['machines'][$fromStatus][$toStatus][] = function () use ($data) {
                $order = $data['order'];


                $commissionOrder = CommissionOrder::query()->where("order_id", $order->id)->first();
                if (!$commissionOrder || $commissionOrder->status == 2) {
                    return;
                }
                //Log::debug("=======3=========");

                $commissionUser = CommissionUser::query()->where('customer_id', $commissionOrder->customer_id)->where('status', 2)->first();//分销会员


                CommissionOrder::query()->where("order_id", $order->id)->update(['status' => 2]);

                $plugin           = plugin_setting('commission');
                $add_balance_type = isset($plugin['add_balance_type']) ? $plugin['add_balance_type'] : 0;
                if ($commissionUser) {//分销员存在，则进行返佣金
                    //Log::debug("=======4=========");
                    //$order_amount = bcmul($order->total, 100);
                    //除去运费的
                    $orderTotals = OrderTotal::query()->whereIn('code', [
                        'sub_total',
                        'customer_discount'
                    ])->where('order_id', $order->id)->get();
                    $subTotal    = 0;
                    $discount    = 0;
                    foreach ($orderTotals as $orderTotal) {
                        if ($orderTotal->code == 'sub_total') {
                            $subTotal = bcadd($orderTotal->value, $subTotal, 2);
                        } else {
                            $discount = bcadd($orderTotal->value, $discount, 2);
                        }
                    }
                    $order_amount = bcadd($subTotal, $discount, 2);
                    $order_amount = bcmul(round($order_amount, 2), 100);
                    //$order_amount = CurrencyService::getInstance()->convert($order_amount,$order->currency_code , system_setting('base.currency'));//转化为本币,系统中取的sub_total等已经是默认币种了

                    //第一级返佣
                    $rate1 = $commissionUser->rate_1;
                    //TODO 对应产品的分销比例
                    if ($rate1 == -1) {
                        $rate1 = $plugin['rate1'];
                    }

                    $status = 1;
                    if (isset($plugin['settlement_period']) && $plugin['settlement_period'] > 0) {//有设定结算周期
                        $status = 5;
                    }

                    if ($rate1 > 0) {
                        $rate11   = bcdiv($rate1, 100, 2);
                        $amount_1 = bcmul($order_amount, $rate11);
                        if ($amount_1 > 0) {
                            $amount1Log = [
                                'commission_user_id' => $commissionUser->id,
                                'customer_id'        => $commissionUser->customer_id,
                                'order_id'           => $order->id,
                                'action'             => CommissionAmountLog::ACTION_ORDER,
                                'rate'               => $rate1,
                                'level'              => 1,
                                'amount'             => $amount_1,
                                'base_amount'        => $order_amount,
                                'created_at'         => Carbon::now(),
                                'updated_at'         => Carbon::now(),
                                'status'             => $status,
                            ];
                            DB::table('commission_amount_logs')->insertGetId($amount1Log);

                            if ($status == 1) {//结算到余额
                                if ($add_balance_type === 0) {
                                    $after_amount = bcadd($commissionUser->balance, $amount_1);
                                    $commissionUser->update([
                                        'balance'      => $after_amount,
                                        'total_amount' => bcadd($commissionUser->total_amount, $amount_1)
                                    ]);
                                } else {
                                    $commissionUser->update([
                                        'total_amount' => bcadd($commissionUser->total_amount, $amount_1)
                                    ]);
                                    //结算到钱包
                                    $this->updateWalletBalance($commissionUser->customer_id, bcdiv($amount_1, 100, 2), __('Commission::bootstrap.wallet_order_commission_1', ['number' => $order->number]));

                                }

                            } else {
                                $after_amount = bcadd($commissionUser->balance_progress, $amount_1);
                                $commissionUser->update([
                                    'balance_progress' => $after_amount,
                                ]);
                            }
                            $order_amount = bcsub($order_amount, $amount_1, 2);
                        }
                    }


                    //第二级返佣
                    $commissionCustomer2 = CommissionCustomers::query()->where('customer_id', $commissionUser->customer_id)->first();//$commissionUser的上级
                    if ($commissionCustomer2) {
                        $commissionUser2 = CommissionUser::query()->where('id', $commissionCustomer2->commission_user_id)->where('status', 2)->first();//分销会员
                        if ($commissionUser2) {
                            $rate2 = $commissionUser2->rate_2;
                            //TODO 对应产品的分销比例
                            if ($rate2 == -1) {
                                $rate2 = $plugin['rate2'];
                            }

                            if ($rate2 > 0) {
                                $rate22   = bcdiv($rate2, 100, 2);
                                $amount_2 = bcmul($order_amount, $rate22);

                                if ($amount_2 > 0) {
                                    $amount2Log = [
                                        'commission_user_id' => $commissionUser2->id,
                                        'customer_id'        => $commissionUser2->customer_id,
                                        'order_id'           => $order->id,
                                        'action'             => CommissionAmountLog::ACTION_ORDER,
                                        'rate'               => $rate2,
                                        'level'              => 2,
                                        'amount'             => $amount_2,
                                        'base_amount'        => $order_amount,
                                        'created_at'         => Carbon::now(),
                                        'updated_at'         => Carbon::now(),
                                        'status'             => $status,
                                    ];
                                    DB::table('commission_amount_logs')->insertGetId($amount2Log);

                                    if ($status == 1) {//结算到余额
                                        if ($add_balance_type === 0) {
                                            $after_amount2 = bcadd($commissionUser2->balance, $amount_2);
                                            $commissionUser2->update([
                                                'balance'      => $after_amount2,
                                                'total_amount' => bcadd($commissionUser2->total_amount, $amount_2)
                                            ]);
                                        } else {
                                            $commissionUser2->update([
                                                'total_amount' => bcadd($commissionUser2->total_amount, $amount_2)
                                            ]);
                                            //结算到钱包
                                            $this->updateWalletBalance($commissionUser2->customer_id, bcdiv($amount_2, 100, 2), __('Commission::bootstrap.wallet_order_commission_2', ['number' => $order->number]));

                                        }
                                    } else {
                                        $after_amount2 = bcadd($commissionUser2->balance_progress, $amount_2);
                                        $commissionUser2->update([
                                            'balance_progress' => $after_amount2,
                                        ]);
                                    }
                                    $order_amount = bcsub($order_amount, $amount_2, 2);
                                }
                            }

                            //第三级返佣
                            $commissionCustomer3 = CommissionCustomers::query()->where('customer_id', $commissionUser2->customer_id)->first();//$commissionUser2的上级
                            if ($commissionCustomer3) {
                                $commissionUser3 = CommissionUser::query()->where('id', $commissionCustomer3->commission_user_id)->where('status', 2)->first();//分销会员
                                if ($commissionUser3) {
                                    $rate3 = $commissionUser3->rate_3;
                                    //TODO 对应产品的分销比例
                                    if ($rate3 == -1) {
                                        $rate3 = $plugin['rate3'];
                                    }

                                    if ($rate3 > 0) {
                                        $rate33   = bcdiv($rate3, 100, 2);
                                        $amount_3 = bcmul($order_amount, $rate33);
                                        if ($amount_3) {
                                            $amount3Log = [
                                                'commission_user_id' => $commissionUser3->id,
                                                'customer_id'        => $commissionUser3->customer_id,
                                                'order_id'           => $order->id,
                                                'action'             => CommissionAmountLog::ACTION_ORDER,
                                                'rate'               => $rate3,
                                                'level'              => 3,
                                                'amount'             => $amount_3,
                                                'base_amount'        => $order_amount,
                                                'created_at'         => Carbon::now(),
                                                'updated_at'         => Carbon::now(),
                                                'status'             => $status,
                                            ];
                                            DB::table('commission_amount_logs')->insertGetId($amount3Log);
                                            if ($status == 1) {//结算到余额
                                                if ($add_balance_type === 0) {
                                                    $after_amount3 = bcadd($commissionUser3->balance, $amount_3);
                                                    $commissionUser3->update([
                                                        'balance'      => $after_amount3,
                                                        'total_amount' => bcadd($commissionUser3->total_amount, $amount_3)
                                                    ]);
                                                } else {
                                                    $commissionUser3->update([
                                                        'total_amount' => bcadd($commissionUser3->total_amount, $amount_3)
                                                    ]);
                                                    //结算到钱包
                                                    $this->updateWalletBalance($commissionUser3->customer_id, bcdiv($amount_3, 100, 2), __('Commission::bootstrap.wallet_order_commission_3', ['number' => $order->number]));
                                                }
                                            } else {
                                                $after_amount3 = bcadd($commissionUser3->balance_progress, $amount_3);
                                                $commissionUser3->update([
                                                    'balance_progress' => $after_amount3,
                                                ]);
                                            }
                                        }
                                    }

                                }
                            }

                        }
                    }


                }
            };
            return $data;
        }, 20240714);


        // 添加前台用户中心界面
        add_hook_blade('account.sidebar.before.logout', function ($callback, $output, $data) {

            $comUser = CommissionUser::query()->where('customer_id', current_customer()->id)->first();
            if ($comUser) {
                $view = view('Commission::shop.account_sidebar', $data)->render();
                return $view . $output;
            }
            return $output;
        }, 202501100006);


        /**
         * 商品详情中的分享功能互动
         */
        add_hook_filter('product.detail.after.share.url', function ($data) {
            $customer = current_customer();
            if (!$customer) {
                return $data;
            }
            if (!isset($data['product']['url'])) {
                return $data;
            }
            $comUser = CommissionUser::query()->where('customer_id', $customer->id)->first(['code']);
            if (!$comUser) {
                return $data;
            }
            $url = $data['product']['url'];
            if (strpos($url, "?") !== false) {
                $url = $url . "&cid=" . $comUser->code;
            } else {
                $url = $url . "?cid=" . $comUser->code;
            }
            $data['product']['url'] = $url;
            return $data;
        }, 997);


        //创建订单后要执行,创建订单时记录订单数据，要不然后台变更订单状态可能会有问题
        add_hook_filter('wallet_pay.amount.logs.resource', function ($data) {
            $data = CommissionService::parseActionFormat($data);
            return $data;
        });
    }

    private function updateWalletBalance($customer_id, $amount, $note)
    {
        $plugin = plugin_setting("wallet_pay");
        if (empty($plugin) || $plugin['status'] == 0) {
            return "请开启钱包插件";
        } else {
            return \Plugin\WalletPay\Services\WalletService::updateBalance($customer_id, $amount, $note, false);
        }
    }
}
