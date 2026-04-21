<?php

use App\Models\BarOrder;
use App\Models\Billing;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\OrderItem;
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

it('prints walk-in billing simulation with discount row after subtotal', function () {
    $orderItem = new OrderItem;
    $orderItem->item_name = 'Test Food Lounge';
    $orderItem->quantity = 1;
    $orderItem->price = 5000000;
    $orderItem->subtotal = 5000000;

    $order = new Order;
    $order->order_number = 'WALKIN-TEST-01';
    $order->ordered_at = now();
    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('customer', null);
    $order->setRelation('createdBy', null);

    $billing = new Billing;
    $billing->transaction_code = 'WALKIN-000001';
    $billing->updated_at = now();
    $billing->minimum_charge = 0;
    $billing->subtotal = 5000000;
    $billing->tax = 550000;
    $billing->tax_percentage = 11;
    $billing->service_charge = 277500;
    $billing->service_charge_percentage = 5;
    $billing->discount_amount = 582750;
    $billing->grand_total = 5244750;
    $billing->payment_mode = 'normal';
    $billing->payment_method = 'cash';

    $printer = Printer::make([
        'name' => 'Walkin Test',
        'connection_type' => 'log',
        'width' => 42,
    ]);

    $result = (new PrinterService)->printWalkInBillingReceipt($order, $billing, $printer);

    $log = (string) file_get_contents(storage_path('logs/printer.log'));

    $subTotalPos = strpos($log, 'Sub Total');
    $discountPos = strpos($log, 'Diskon');
    $remainingPos = strpos($log, 'Sisa Bayar');

    expect($result)->toBeTrue()
        ->and($log)->toContain('WALK-IN RECEIPT')
        ->and($log)->toContain('- Rp 582.750')
        ->and($subTotalPos)->not->toBeFalse()
        ->and($discountPos)->not->toBeFalse()
        ->and($remainingPos)->not->toBeFalse()
        ->and($discountPos > $subTotalPos)->toBeTrue()
        ->and($remainingPos > $discountPos)->toBeTrue();
});

it('prints end day recap with LD quantity row', function () {
    $printer = Printer::make([
        'name' => 'End Day Test',
        'location' => 'cashier',
        'connection_type' => 'log',
        'width' => 42,
    ]);

    $recapData = [
        'selectedStartDatetime' => '16/04/2026 09:00',
        'selectedEndDatetime' => '17/04/2026 09:00',
        'cashierCount' => 2,
        'cashierRevenue' => 150000,
        'totalTax' => 15000,
        'totalServiceCharge' => 10000,
        'totalDiscount' => 0,
        'totalDownPayment' => 0,
        'paymentMethodTotals' => [
            'cash' => 150000,
            'transfer' => 0,
            'debit' => 0,
            'kredit' => 0,
            'qris' => 0,
        ],
        'dashboardPreview' => [
            'total_kitchen_items' => 3,
            'total_bar_items' => 4,
            'total_staff_meal' => 25000,
            'total_ld_quantity' => 7,
        ],
        'kitchenQtyTotal' => 3,
        'barQtyTotal' => 4,
        'rokokItems' => [],
        'cashierTransactions' => [],
    ];

    $result = (new PrinterService)->printEndDayRecap($recapData, $printer);

    $log = file_get_contents(storage_path('logs/printer.log'));

    expect($result)->toBeTrue()
        ->and($log)->toContain('Total Staff Meal')
        ->and($log)->toContain('25.000')
        ->and($log)->toContain('Total LD Qty')
        ->and($log)->toContain('7');
});
