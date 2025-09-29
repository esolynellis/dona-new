<?php
/**
 * shop.php
 *
 * @author     村长+ <178277164@qq.com>
 */

use Illuminate\Support\Facades\Route;
use Plugin\RegisterCaptcha\Controllers\CaptchaController;

Route::post('/register/captcha', [
    CaptchaController::class,
    'checkCaptcha'
])->name('regCheckCaptcha');

