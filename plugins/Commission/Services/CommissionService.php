<?php

namespace Plugin\Commission\Services;


use Plugin\Commission\Models\CommissionAmountLog;

class CommissionService
{


    public static function parseActionFormat($data)
    {
        if ($data['action_format'] == $data['action']) {
            $order         = $data['order'];
            $action_format = "";
            $action        = str_replace("commission_", "", $data['action']);
            switch ($action) {
                case CommissionAmountLog::ACTION_FISSION;
                    if ((isset($data['level']) && $data['level'] == 1) || (isset($data['note']) && $data['note'] == 'commission level 1')) {
                        $action_format = trans("Commission::orders.fission_1");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                    } else if ((isset($data['level']) && $data['level'] == 2) || (isset($data['note']) && $data['note'] == 'commission level 2')) {
                        $action_format = trans("Commission::orders.fission_2");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                    } else if ((isset($data['level']) && $data['level'] == 3) || (isset($data['note']) && $data['note'] == 'commission level 3')) {
                        $action_format = trans("Commission::orders.fission_3");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                    }
                    break;
                case CommissionAmountLog::ACTION_ORDER;
                    if ((isset($data['level']) && $data['level'] == 1) || (isset($data['note']) && $data['note'] == 'commission level 1')) {
                        $action_format = trans("Commission::orders.buy_product");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_1");

                    } else if ((isset($data['level']) && $data['level'] == 2) || (isset($data['note']) && $data['note'] == 'commission level 2')) {
                        $action_format = trans("Commission::orders.buy_product");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_2");
                    } else if ((isset($data['level']) && $data['level'] == 3) || (isset($data['note']) && $data['note'] == 'commission level 3')) {
                        $action_format = trans("Commission::orders.buy_product");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_3");
                    }
                    if ($order != null) {
                        if (!empty($order->customer)) {
                            $action_format = "(" . $order->customer->name . ")" . $action_format;
                        } else {
                            $action_format = "(" . $order->shipping_customer_name . ")" . $action_format;
                        }
                    }
                    break;
                case CommissionAmountLog::ACTION_REFUND;

                    $action_format = trans("Commission::orders.refund");
                    if ($order != null) {
                        if (!empty($order->customer)) {
                            $action_format = "(" . $order->customer->name . ")" . $action_format;
                        } else {
                            $action_format = "(" . $order->shipping_customer_name . ")" . $action_format;
                        }
                        $action_format = $action_format . "(" . $order->number . '),';
                    }
                    $action_format = $action_format . trans('Commission::orders.cancel_commission_amount');
                    break;
                case CommissionAmountLog::ACTION_CLOSE;
                    if ((isset($data['apply_data']) && !empty($data['apply_data'])) || isset($data['note']) && $data['note'] == 'Commission withdrawal of cash') {
                        $action_format = trans("Commission::orders.apply_success_close");
                        $note          = null;
                        if (isset($data['audit_note'])) {
                            $note = empty($data['audit_note']) ? null : $data['audit_note'];
                        } else if (isset($data['note'])) {
                            $note = empty($data['note']) ? null : $data['note'];
                        }
                        if ($note) {
                            $action_format = $action_format . "({$note})";
                        }
                    } else {
                        $action_format = trans("Commission::orders.close");
                    }

                    break;
                case CommissionAmountLog::ACTION_APPLY_CLOSE;
                    $action_format = trans("Commission::orders.apply_close");

                    break;
                case CommissionAmountLog::ACTION_REFUSE_CLOSE;
                    $action_format = trans("Commission::orders.refuse_close");

                    $note = null;
                    if (isset($data['audit_note'])) {
                        $note = empty($data['audit_note']) ? null : $data['audit_note'];
                    } else if (isset($data['note'])) {
                        $note = empty($data['note']) ? null : $data['note'];
                    }
                    if ($note) {
                        $action_format = $action_format . "({$note})";
                    }
                    break;
                case CommissionAmountLog::ACTION_PAY;
                    $action_format = trans("Commission::orders.pay");
                    if ($order != null) {
                        $action_format = $action_format . "(" . $order->number . '),';
                    }
                    break;
                case CommissionAmountLog::ACTION_INIT;
                    $action_format = trans("Commission::orders.init");
                    break;
                case CommissionAmountLog::ACTION_SYS_ADD;
                    $action_format = trans("Commission::orders.sys_add");
                    $note          = null;
                    if (isset($data['audit_note'])) {
                        $note = empty($data['audit_note']) ? null : $data['audit_note'];
                    } else if (isset($data['note'])) {
                        $note = empty($data['note']) ? null : $data['note'];
                    }
                    if ($note) {
                        $action_format = $action_format . "({$note})";
                    }
                    break;
                case CommissionAmountLog::ACTION_SYS_SUB;
                    $action_format = trans("Commission::orders.sys_sub");
                    $note          = null;
                    if (isset($data['audit_note'])) {
                        $note = empty($data['audit_note']) ? null : $data['audit_note'];
                    } else if (isset($data['note'])) {
                        $note = empty($data['note']) ? null : $data['note'];
                    }
                    if ($note) {
                        $action_format = $action_format . "({$note})";
                    }
                    break;
                case CommissionAmountLog::ACTION_REG_VIP;
                    if ($data['customer']) {
                        $action_format = "(" . $data['customer']->name . ")" . trans("Commission::orders.reg_vip");
                    } else {
                        $action_format = trans("Commission::orders.reg_vip");
                    }
                    break;
                case CommissionAmountLog::ACTION_ORDER_VIP;
                    if ((isset($data['level']) && $data['level'] == 1) || (isset($data['note']) && $data['note'] == 'commission level 1')) {
                        $action_format = trans("Commission::orders.order_vip");
                        if ($order != null) {
                            if (!empty($order->customer)) {
                                $action_format = "(" . $order->customer->name . ")" . $action_format;
                            } else {
                                $action_format = "(" . $order->shipping_customer_name . ")" . $action_format;
                            }

                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_1");
                    } else if ((isset($data['level']) && $data['level'] == 2) || (isset($data['note']) && $data['note'] == 'commission level 2')) {
                        $action_format = trans("Commission::orders.order_vip");
                        if ($order != null) {
                            if (!empty($order->customer)) {
                                $action_format = "(" . $order->customer->name . ")" . $action_format;
                            } else {
                                $action_format = "(" . $order->shipping_customer_name . ")" . $action_format;
                            }

                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_2");
                    } else if ((isset($data['level']) && $data['level'] == 3) || (isset($data['note']) && $data['note'] == 'commission level 3')) {
                        $action_format = trans("Commission::orders.order_vip");
                        if ($order != null) {
                            if (!empty($order->customer)) {
                                $action_format = "(" . $order->customer->name . ")" . $action_format;
                            } else {
                                $action_format = "(" . $order->shipping_customer_name . ")" . $action_format;
                            }

                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_3");
                    }
                    break;
                case CommissionAmountLog::ACTION_SCHEDULE_CLOSE;
                    if ((isset($data['level']) && $data['level'] == 1) || (isset($data['note']) && $data['note'] == 'commission level 1')) {
                        $action_format = trans("Commission::orders.buy_product");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_1");

                    } else if ((isset($data['level']) && $data['level'] == 2) || (isset($data['note']) && $data['note'] == 'commission level 2')) {
                        $action_format = trans("Commission::orders.buy_product");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("ACommission::orders.cash_back_2");
                    } else if ((isset($data['level']) && $data['level'] == 3) || (isset($data['note']) && $data['note'] == 'commission level 3')) {
                        $action_format = trans("Commission::orders.buy_product");
                        if ($order != null) {
                            $action_format = $action_format . "(" . $order->number . '),';
                        }
                        $action_format = $action_format . trans("Commission::orders.cash_back_3");
                    }
                    if ($order != null) {
                        if (!empty($order->customer)) {
                            $action_format = "(" . $order->customer->name . ")" . $action_format;
                        } else {
                            $action_format = "(" . $order->shipping_customer_name . ")" . $action_format;
                        }
                    }
                    break;
            }
            if (!empty($action_format)) {
                $data['action_format'] = $action_format;
            }
        }
        return $data;
    }

}
