<?php
/**
 * admin.php
 *
 * @copyright  2022 HL-MALL.com - All Rights Reserved
 * @link       https://HL-MALL.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-08-04 16:17:53
 * @modified   2022-08-04 16:17:53
 */

use Illuminate\Support\Facades\Route;
use Plugin\LangPackGenerator\Controllers\LangPackGeneratorController;
Route::middleware('can:lang_pack_generator_sync_content')->get('/lang_pack_generator_sync_content', [LangPackGeneratorController::class, 'syncContent'])->name('LangPackGenerator.sync_content');
Route::middleware('can:lang_pack_generator_index')->post('/lang_pack_generator_replace', [LangPackGeneratorController::class, 'replace'])->name('lang_pack_generator_replace_save');
Route::middleware('can:lang_pack_generator_index')->post('/lang_pack_generator_batch_replace', [LangPackGeneratorController::class, 'batch_replace'])->name('lang_pack_generator_replace_batch');
Route::middleware('can:lang_pack_generator_index')->get('/lang_pack_generator_replace', [LangPackGeneratorController::class, 'replace'])->name('lang_pack_generator_replace');
Route::middleware('can:lang_pack_generator_index')->get('/lang_pack_generator_index', [LangPackGeneratorController::class, 'index'])->name('LangPackGenerator.index');
Route::middleware('can:lang_pack_generator_stop')->post('/lang_pack_generator_stop', [LangPackGeneratorController::class, 'stop'])->name('LangPackGenerator.stop');
Route::middleware('can:lang_pack_generator_lists')->get('/lang_pack_generator_lists', [LangPackGeneratorController::class, 'lists'])->name('LangPackGenerator.lists');
Route::middleware('can:lang_pack_generator_import')->post('/lang_pack_generator_import', [LangPackGeneratorController::class, 'import'])->name('LangPackGenerator.import');
Route::middleware('can:lang_pack_generator_del')->post('/lang_pack_generator_del', [LangPackGeneratorController::class, 'delete'])->name('LangPackGenerator.delete');
Route::middleware('can:lang_pack_generator_index')->post('/lang_pack_generator_add', [LangPackGeneratorController::class, 'add'])->name('LangPackGenerator.add');
Route::middleware('can:lang_pack_generator_edit')->post('/lang_pack_generator_edit', [LangPackGeneratorController::class, 'edit'])->name('LangPackGenerator.edit');
Route::middleware('can:lang_pack_generator_run')->post('/lang_pack_generator_run', [LangPackGeneratorController::class, 'run'])->name('LangPackGenerator.run');
Route::middleware('can:lang_pack_generator_logs')->get('/lang_pack_generator_logs', [LangPackGeneratorController::class, 'logs'])->name('LangPackGenerator.logs');
Route::middleware('can:lang_pack_generator_progress')->get('/lang_pack_generator_progress', [LangPackGeneratorController::class, 'progress'])->name('LangPackGenerator.progress');
Route::middleware('can:lang_pack_generator_config')->post('/lang_pack_generator_config', [LangPackGeneratorController::class, 'config'])->name('LangPackGenerator.config');
Route::middleware('can:lang_pack_generator_backups_log')->get('/lang_pack_generator_backups_log', [LangPackGeneratorController::class, 'logBackups'])->name('LangPackGenerator.backups_log');
Route::middleware('can:lang_pack_generator_submit_restore_backups')->post('/lang_pack_generator_submit_restore_backups', [LangPackGeneratorController::class, 'restoreBackups'])->name('LangPackGenerator.restore_backups');

Route::middleware('can:lang_pack_generator_iframe')->get('/lang_pack_generator_component/{component}', [LangPackGeneratorController::class, 'component'])->name('LangPackGenerator.component');
Route::middleware('can:lang_pack_generator_install_pro')->post('/lang_pack_generator_install_pro', [LangPackGeneratorController::class, 'installPro'])->name('LangPackGenerator.lang_pack_generator_install_pro');
Route::middleware('can:lang_pack_generator_uninstall_pro')->post('/lang_pack_generator_uninstall_pro', [LangPackGeneratorController::class, 'unInstallPro'])->name('LangPackGenerator.lang_pack_generator_uninstall_pro');


Route::middleware('can:lang_pack_generator_decode_unescapeHtml')->post('/lang_pack_generator_decode_unescapeHtml', [LangPackGeneratorController::class, 'decodeUnescapeHtml'])->name('LangPackGenerator.lang_pack_generator_decode_unescapeHtml');

