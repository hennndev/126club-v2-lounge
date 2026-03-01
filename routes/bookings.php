<?php

use App\Http\Controllers\TableReservationController;
use Illuminate\Support\Facades\Route;

Route::resource('bookings', TableReservationController::class)->except(['show', 'create', 'edit']);
Route::patch('bookings/{booking}/status', [TableReservationController::class, 'updateStatus'])->name('bookings.updateStatus');
Route::post('bookings/{booking}/close-billing', [TableReservationController::class, 'closeBilling'])->name('bookings.closeBilling');
Route::get('bookings/{booking}/receipt', [TableReservationController::class, 'receipt'])->name('bookings.receipt');
