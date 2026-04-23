<?php
/**
 * admin.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2023-02-24 10:33:13
 * @modified   2023-02-24 10:33:13
 */

use Illuminate\Support\Facades\Route;
use Plugin\ProductImport\Controllers\ProductImportController;

Route::middleware('can:products_import_index')->get('import/import', [ProductImportController::class, 'index'])->name('import.index');
Route::middleware('can:products_import_import')->post('import/upload', [ProductImportController::class, 'upload'])->name('import.upload');
Route::middleware('can:products_import_export')->post('import/export', [ProductImportController::class, 'export'])->name('import.export');
Route::middleware('can:products_import_import')->post('import/import', [ProductImportController::class, 'import'])->name('import.import');
