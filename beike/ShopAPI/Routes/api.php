<?php
/**
 * api.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-04-11 17:36:05
 * @modified   2023-04-11 17:36:05
 */

use Beike\ShopAPI\Controllers as ShopController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(['api'])->name('api.')->group(function () {
    Route::post('login', [ShopController\AuthController::class, 'login']);

    Route::get('miniapp', [ShopController\MiniappController::class, 'index']);

    Route::get('carts', [ShopController\CartController::class, 'index']);
    Route::post('carts', [ShopController\CartController::class, 'store']);
    Route::put('carts/select', [ShopController\CartController::class, 'select']);
    Route::put('carts/unselect', [ShopController\CartController::class, 'unselect']);
    Route::put('carts/{cart}', [ShopController\CartController::class, 'update']);
    Route::delete('carts/{cart}', [ShopController\CartController::class, 'destroy']);
    Route::post('register', [ShopController\AuthController::class, 'register']);
    Route::post('logout', [ShopController\AuthController::class, 'logout']);
    Route::post('refresh', [ShopController\AuthController::class, 'refresh']);
    Route::get('me', [ShopController\AuthController::class, 'me']);
    Route::put('me', [ShopController\AccountController::class, 'update']);
    Route::delete('me', [ShopController\AccountController::class, 'destroy']);
    Route::post('captcha', [ShopController\AccountController::class, 'captcha']);

    Route::post('files', [ShopController\FileController::class, 'store']);
    Route::get('articles', [ShopController\ArticleController::class, 'index']);

    Route::get('home', [ShopController\HomeController::class, 'index']);
    Route::get('products', [ShopController\ProductController::class, 'index']);
    Route::get('fenxiaogoods', [ShopController\ProductController::class, 'fenxiaogoods']);
    Route::get('products/{product}', [ShopController\ProductController::class, 'show']);

    Route::get('categories', [ShopController\CategoryController::class, 'index']);

    Route::get('checkout', [ShopController\CheckoutController::class, 'index']);
    Route::put('checkout', [ShopController\CheckoutController::class, 'update']);
    Route::post('checkout/confirm', [ShopController\CheckoutController::class, 'confirm']);

    Route::get('countries', [ShopController\CountryController::class, 'index']);
    Route::get('countries/{country}/zones', [ShopController\CountryController::class, 'zones']);
    Route::get('zones/{zone}/cities', [ShopController\CountryController::class, 'cities']);
    Route::get('cities/{city}/counties', [ShopController\CountryController::class, 'counties']);

    Route::get('addresses', [ShopController\AddressController::class, 'index']);
    Route::get('addresses/{address}', [ShopController\AddressController::class, 'show']);
    Route::post('addresses', [ShopController\AddressController::class, 'store']);
    Route::put('addresses/{address}', [ShopController\AddressController::class, 'update']);
    Route::delete('addresses/{address}', [ShopController\AddressController::class, 'destroy']);

    Route::get('orders', [ShopController\OrderController::class, 'index']);
    Route::post('orders', [ShopController\OrderController::class, 'uploadProof']);
    Route::get('orders/{order}', [ShopController\OrderController::class, 'show']);
    Route::post('orders/{order}/pay', [ShopController\OrderController::class, 'pay']);
    Route::post('orders/{order}/cancel', [ShopController\OrderController::class, 'cancel']);
    Route::post('orders/{order}/confirm', [ShopController\OrderController::class, 'confirm']);
    Route::post('paypal/capture', [Plugin\Paypal\Controllers\PaypalController::class, 'capture']);
    Route::post('stripe/capture', [Plugin\Stripe\Controllers\StripeController::class, 'capture']);

    Route::get('rmas', [ShopController\RmaController::class, 'index']);
    Route::post('rmas', [ShopController\RmaController::class, 'store']);
    Route::get('rmas/{rma}', [ShopController\RmaController::class, 'show']);

    Route::get('settings', [ShopController\SettingController::class, 'index']);

    Route::get('seller/{id}', [ShopController\SellerController::class, 'index']);
    Route::get('seller/{sid}/categories', [ShopController\SellerController::class, 'categories']);
    Route::get('seller/{sid}/categories/{id}', [ShopController\SellerController::class, 'category']);

    Route::get('wishlists', [ShopController\WishlistController::class, 'index']);
    Route::post('wishlists', [ShopController\WishlistController::class, 'store']);
    Route::delete('wishlists/{wishlist}', [ShopController\WishlistController::class, 'destroy']);

    Route::get('customergroup', [ShopController\CustomerController::class, 'groupList']);
    Route::post('createcusorder', [ShopController\CustomerController::class, 'createCusorder']);
    Route::post('cusorders', [ShopController\CustomerController::class, 'uploadProof']);
    Route::post('tuiguang', [ShopController\CustomerController::class, 'tuiguang']);
    Route::post('qpay', [ShopController\PaymentController::class, 'createPayment']);
    Route::post('qpay/callbak', [ShopController\PaymentController::class, 'paymentCallback'])->name('payment.callback');
    Route::post('qpay/check', [ShopController\PaymentController::class, 'checkPaymentStatus']);
});
