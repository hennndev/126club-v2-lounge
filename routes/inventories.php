<?php

use App\Http\Controllers\InventoryController;
use illuminate\Support\Facades\Route;

Route::resource('inventory', InventoryController::class)->only(['index', 'store']);
Route::post('inventory/update-threshold', [InventoryController::class, 'updateThreshold'])->name('inventory.updateThreshold');
Route::get('inventory/{inventory}/detail', [InventoryController::class, 'fetchDetail'])->name('inventory.fetchDetail');
Route::patch('inventory/{inventory}/toggle-active', [InventoryController::class, 'toggleActive'])->name('inventory.toggle-active');

use App\Http\Controllers\StockOpnameController;

Route::prefix('stock-opname')->name('stock-opname.')->group(function () {
    Route::get('/history', [StockOpnameController::class, 'history'])->name('history');
    Route::get('/', [StockOpnameController::class, 'index'])->name('index');
    Route::post('/', [StockOpnameController::class, 'store'])->name('store');
    Route::put('{stockOpname}', [StockOpnameController::class, 'update'])->name('update');
    Route::post('{stockOpname}/complete', [StockOpnameController::class, 'complete'])->name('complete');
    Route::get('{stockOpname}', [StockOpnameController::class, 'show'])->name('show');
});
