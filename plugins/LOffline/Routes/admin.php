<?php
use Illuminate\Support\Facades\Route;


Route::middleware('can:l_offline_payment_config_update')->post('/l_offline/config', [
    \Plugin\LOffline\Controllers\AdminOfflineController::class,
    'save_config'
])->name('l_offline.save_config');
