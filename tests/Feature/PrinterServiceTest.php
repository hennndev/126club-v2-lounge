<?php

use App\Models\BarOrder;
use App\Models\KitchenOrder;
use App\Models\Printer;
use App\Models\Tabel;
use App\Services\PrinterService;

beforeEach(function () {
    $logPath = storage_path('logs/printer.log');
    if (file_exists($logPath)) {
        unlink($logPath);
    }
});

it('prints kitchen ticket with the correct table_number', function () {
    $table = new Tabel;
    $table->table_number = 'T-42';

    $kitchenOrder = new KitchenOrder;
    $kitchenOrder->order_number = 'ORD-TEST-K001';
    $kitchenOrder->setRelation('table', $table);
    $kitchenOrder->setRelation('items', collect());

    $printer = Printer::make(['name' => 'Kitchen Test', 'connection_type' => 'log', 'width' => 42]);

    $result = (new PrinterService)->printKitchenTicket($kitchenOrder, $printer);

    $log = file_get_contents(storage_path('logs/printer.log'));

    expect($result)->toBeTrue()
        ->and($log)->toContain('T-42')
        ->and($log)->toContain('CHECKER')
        ->and($log)->toContain('ORD-TEST-K001');
});

it('prints bar ticket with the correct table_number', function () {
    $table = new Tabel;
    $table->table_number = 'B-07';

    $barOrder = new BarOrder;
    $barOrder->order_number = 'ORD-TEST-B001';
    $barOrder->setRelation('table', $table);
    $barOrder->setRelation('items', collect());

    $printer = Printer::make(['name' => 'Bar Test', 'connection_type' => 'log', 'width' => 42]);

    $result = (new PrinterService)->printBarTicket($barOrder, $printer);

    $log = file_get_contents(storage_path('logs/printer.log'));

    expect($result)->toBeTrue()
        ->and($log)->toContain('B-07')
        ->and($log)->toContain('CHECKER')
        ->and($log)->toContain('ORD-TEST-B001');
});

it('shows N/A in bar ticket when no table is assigned', function () {
    $barOrder = new BarOrder;
    $barOrder->order_number = 'ORD-TEST-B002';
    $barOrder->setRelation('table', null);
    $barOrder->setRelation('items', collect());

    $printer = Printer::make(['name' => 'Bar Test', 'connection_type' => 'log', 'width' => 42]);

    (new PrinterService)->printBarTicket($barOrder, $printer);

    $log = file_get_contents(storage_path('logs/printer.log'));

    expect($log)->toContain('N/A');
});

it('prints kitchen ticket with correct item names from inventoryItem relationship', function () {
    $table = new Tabel;
    $table->table_number = 'T-10';

    $inventoryItem = new \App\Models\InventoryItem;
    $inventoryItem->name = 'Test Food';

    $item = new \App\Models\KitchenOrderItem;
    $item->quantity = 2;
    $item->setRelation('inventoryItem', $inventoryItem);

    $kitchenOrder = new KitchenOrder;
    $kitchenOrder->order_number = 'ORD-TEST-K002';
    $kitchenOrder->setRelation('table', $table);
    $kitchenOrder->setRelation('items', collect([$item]));

    $printer = Printer::make(['name' => 'Kitchen Test', 'connection_type' => 'log', 'width' => 42]);

    (new PrinterService)->printKitchenTicket($kitchenOrder, $printer);

    $log = file_get_contents(storage_path('logs/printer.log'));

    expect($log)->toContain('2x Test Food');
});
