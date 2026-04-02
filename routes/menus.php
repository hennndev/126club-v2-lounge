<?php

use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
Route::post('menus', [MenuController::class, 'store'])->name('menus.store');
Route::get('menus/{inventory}/detail', [MenuController::class, 'fetchDetail'])->name('menus.fetch-detail');
Route::patch('menus/{inventory}/tax-flags', [MenuController::class, 'updateTaxFlags'])->name('menus.update-tax-flags');
Route::patch('menus/{inventory}/printer-targets', [MenuController::class, 'updatePrinterTargets'])->name('menus.update-printer-targets');
Route::patch('menus/{inventory}/pos-name', [MenuController::class, 'updatePosName'])->name('menus.update-pos-name');
Route::patch('menus/{inventory}/category-main', [MenuController::class, 'updateCategoryMain'])->name('menus.update-category-main');
