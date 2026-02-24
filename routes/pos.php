<?php

use App\Http\Controllers\PosController;
use illuminate\Support\Facades\Route;

Route::get('pos', [PosController::class, 'index'])->name('pos.index');
Route::post('pos/{productId}/add-to-cart', [PosController::class, 'addToCart'])->name('pos.add-to-cart');
Route::post('pos/{productId}/update-cart', [PosController::class, 'updateCartQuantity'])->name('pos.update-cart');
Route::delete('pos/{productId}/remove-from-cart', [PosController::class, 'removeFromCart'])->name('pos.remove-from-cart');
Route::post('pos/clear-cart', [PosController::class, 'clearCart'])->name('pos.clear-cart');
Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

// Printer integration
Route::post('pos/print-receipt/{order?}', [PosController::class, 'printReceipt'])->name('pos.print-receipt');
Route::post('pos/test-print', [PosController::class, 'testPrint'])->name('pos.test-print');
