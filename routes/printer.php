<?php

use App\Http\Controllers\PrinterController;
use Illuminate\Support\Facades\Route;

// Printer Management
Route::resource('printers', PrinterController::class)->except(['show', 'create', 'edit']);
Route::post('printers/{printer}/set-default', [PrinterController::class, 'setDefault'])->name('printers.set-default');
Route::post('printers/{printer}/test', [PrinterController::class, 'testPrint'])->name('printers.test');
Route::post('printers/{printer}/ping', [PrinterController::class, 'ping'])->name('printers.ping');

// Print receipt (for POS integration)
Route::post('printer/receipt/{order}', [PrinterController::class, 'printReceipt'])->name('printer.receipt');
Route::post('printer/receipt/{order}/{printer}', [PrinterController::class, 'printReceipt'])->name('printer.receipt.specific');
