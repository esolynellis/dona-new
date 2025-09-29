<?php


use Illuminate\Support\Facades\Route;
use Plugin\Commission\Controllers\CommissionController;
use Plugin\Commission\Controllers\WithdrawalGroupController;



Route::middleware('can:commission_user_list')->get('/commission/users_index', [
    CommissionController::class,
    'users_index'
])->name('users_index');

Route::middleware('can:commission_user_list')->get('/commission/users', [
    CommissionController::class,
    'users'
])->name('users');

Route::middleware('can:commission_user_list')->delete('/commission/users', [
    CommissionController::class,
    'users_delete'
])->name('users_delete');

Route::middleware('can:commission_user_status_update')->post('/commission/users', [
    CommissionController::class,
    'customer_user'
])->name('customer_user');

Route::middleware('can:commission_user_rate_update')->post('commission/users/rate', [
    CommissionController::class,
    'customer_user_rate'
])->name('customer_user_rate');


Route::middleware('can:commission_amount_list')->get('/commission/orders_index', [
    CommissionController::class,
    'orders_index'
])->name('orders_index');


Route::middleware('can:commission_user_balance_update')->put('/commission/user/balance', [
    CommissionController::class,
    'close_balance'
])->name('customer_user_balance_close');


Route::middleware('can:commission_user_list')->post('/commission/checkAdd', [
    CommissionController::class,
    'checkAdd'
])->name('commission_users_check_add');




Route::middleware('can:commission_amount_list')->get('/commission/orders', [
    CommissionController::class,
    'orders'
])->name('orders');

Route::middleware('can:commission_amount_refund')->put('/commission/orders/refund', [
    CommissionController::class,
    'refund_order'
])->name('refund_order');


Route::middleware('can:commission_user_balance_update')->put('/commission/user/audit_cash_apply', [
    CommissionController::class,
    'audit_cash_apply'
])->name('audit_cash_apply');


Route::middleware('can:commission_user_balance_update')->put('/commission/user/balance/update', [
    CommissionController::class,
    'balance_update'
])->name('admin_commission_user_balance_update');


Route::middleware('can:commission_amount_list')->get('/commission/cash_apply_logs_index', [
    CommissionController::class,
    'cash_apply_logs_index'
])->name('cash_apply_logs_index');

Route::middleware('can:commission_amount_list')->get('/commission/cash_apply_new_alert', [
    CommissionController::class,
    'cashApplyNewAlert'
])->name('cash_apply_new_alert');


Route::middleware('can:withdrawal_group')->get('/withdrawal_group/index', [
    WithdrawalGroupController::class,
    'index'
])->name('withdrawal_group.index');

Route::middleware('can:withdrawal_group')->post('/withdrawal_group/store', [
    WithdrawalGroupController::class,
    'store'
])->name('withdrawal_group.store');


Route::middleware('can:withdrawal_group')->delete('/withdrawal_group/del', [
    WithdrawalGroupController::class,
    'destory'
])->name('withdrawal_group.destory');

Route::middleware('can:withdrawal_group')->post('/withdrawal_group/item/store', [
    WithdrawalGroupController::class,
    'item_store'
])->name('withdrawal_group.item.store');


Route::middleware('can:withdrawal_group')->delete('/withdrawal_group/item/del', [
    WithdrawalGroupController::class,
    'item_destory'
])->name('withdrawal_group.item.destory');



