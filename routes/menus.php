<?php

use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
Route::post('menus', [MenuController::class, 'store'])->name('menus.store');
Route::get('menus/{inventory}/detail', [MenuController::class, 'fetchDetail'])->name('menus.fetch-detail');
