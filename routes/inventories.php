<?php

use App\Http\Controllers\InventoryController;
use illuminate\Support\Facades\Route;

Route::resource('inventory', InventoryController::class)->except(['show', 'create', 'edit']);
Route::post('inventory/update-threshold', [InventoryController::class, 'updateThreshold'])->name('inventory.updateThreshold');

use App\Http\Controllers\StockOpnameController;

Route::prefix('stock-opname')->name('stock-opname.')->group(function () {
    Route::get('/history', [StockOpnameController::class, 'history'])->name('history');
    Route::get('/', [StockOpnameController::class, 'index'])->name('index');
    Route::post('/', [StockOpnameController::class, 'store'])->name('store');
    Route::put('{stockOpname}', [StockOpnameController::class, 'update'])->name('update');
    Route::post('{stockOpname}/complete', [StockOpnameController::class, 'complete'])->name('complete');
    Route::get('{stockOpname}', [StockOpnameController::class, 'show'])->name('show');
});
