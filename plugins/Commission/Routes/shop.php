<?php
/**
 * shop.php
 *
 * @author     村长+ <178277164@qq.com>
 */
use Illuminate\Support\Facades\Route;
use Plugin\Commission\Controllers\CommissionController;

Route::get('/commission/shop/orders_index', [
    CommissionController::class,
    'shop_orders_index'
])->name('shop_order_index');

Route::get('/commission/shop/orders', [
    CommissionController::class,
    'shop_orders'
])->name('shop_orders');

Route::post('/commission/shop/cash_apply', [
    CommissionController::class,
    'shop_cash_apply'
])->name('cash_apply');

Route::post('/commission/shop/pay_order', [
    CommissionController::class,
    'pay_order'
])->name('pay_order');

Route::get('/commission/shop/users_index', [
    CommissionController::class,
    'shop_users_index'
])->name('shop_users_index');


Route::get('/commission/shop/users', [
    CommissionController::class,
    'shop_users'
])->name('shop_users');

Route::get('/commission/task/{pwd}', [
    CommissionController::class,
    'task'
])->name('commission_task');

