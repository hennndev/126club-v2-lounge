<?php

use App\Http\Controllers\AccurateSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('accurate/sync')->name('accurate.sync.')->group(function () {
    Route::get('/', [AccurateSyncController::class, 'index'])->name('index');
    Route::post('/items', [AccurateSyncController::class, 'syncItems'])->name('items');
    Route::post('/bom', [AccurateSyncController::class, 'syncBom'])->name('bom');
    Route::get('/status', [AccurateSyncController::class, 'status'])->name('status');
    Route::post('/interval', [AccurateSyncController::class, 'updateInterval'])->name('interval');
    Route::post('/toggle', [AccurateSyncController::class, 'toggleAutoSync'])->name('toggle');
});
