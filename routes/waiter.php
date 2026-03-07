<?php

use App\Http\Controllers\TableController;
use App\Http\Controllers\TableReservationController;
use App\Http\Controllers\Waiter\WaiterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WaiterController::class, 'index'])->name('index');
Route::get('/pos', [WaiterController::class, 'pos'])->name('pos');
Route::get('/active-tables', [WaiterController::class, 'activeTables'])->name('active-tables');
Route::get('/scanner', [WaiterController::class, 'scanner'])->name('scanner');
Route::post('/table-scanner/scan', [TableController::class, 'scanQR'])->name('table-scanner.scan');
Route::post('/table-scanner/generate-checkin-qr', [TableController::class, 'generateCheckInQR'])->name('table-scanner.generate-checkin-qr');
Route::post('/table-scanner/process-checkin', [TableController::class, 'processCheckIn'])->name('table-scanner.process-checkin');
Route::get('/bookings', [TableReservationController::class, 'index'])->name('bookings.index');
Route::get('/notifications', [WaiterController::class, 'notifications'])->name('notifications');
Route::get('/settings', [WaiterController::class, 'settings'])->name('settings');
