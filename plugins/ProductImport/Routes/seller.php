<?php
/**
 * shop.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2023-05-11 17:33:13
 * @modified   2023-05-11 17:33:13
 */

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Plugin\ProductImport\Controllers\ProductImportController;

if (function_exists('is_seller') && is_seller()) {
    // Seller端的路由定义
    $appDomain = parse_url(config('app.url'), PHP_URL_HOST);
    Route::prefix('seller')
        ->domain($appDomain)
        ->name('seller.')
        ->middleware([EncryptCookies::class, StartSession::class, ShareErrorsFromSession::class, \Beike\Seller\Middleware\SetLocaleSeller::class, VerifyCsrfToken::class])
        ->group(function () {
            Route::middleware(\Beike\Seller\Middleware\SellerAuthenticate::class .':' . \Beike\Seller\Models\SellerUser::AUTH_GUARD)
                ->group(function () {
                    Route::middleware('can:products_create')->get('import/import', [ProductImportController::class, 'index'])->name('import.index');
                    Route::middleware('can:products_create')->post('import/upload', [ProductImportController::class, 'upload'])->name('import.upload');
                    Route::middleware('can:products_create')->post('import/export', [ProductImportController::class, 'export'])->name('import.export');
                    Route::middleware('can:products_create')->post('import/import', [ProductImportController::class, 'import'])->name('import.import');
                });
        });
}
