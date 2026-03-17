<?php

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;

use function Pest\Laravel\actingAs;

function makeTransactionHistoryInventoryItem(array $attributes = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'code' => 'TH-ITEM-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'TH Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 25000,
        'stock_quantity' => 10,
        'threshold' => 1,
        'unit' => 'unit',
        'is_active' => true,
    ], $attributes));
}

function makeTransactionHistoryOrder(int $createdById, string $preparationLocation, array $assignedPrinterLocations = []): Order
{
    $inventoryItem = makeTransactionHistoryInventoryItem();

    $order = Order::create([
        'table_session_id' => null,
        'customer_user_id' => null,
        'created_by' => $createdById,
        'order_number' => 'TH-ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => $inventoryItem->name,
        'item_code' => $inventoryItem->code,
        'quantity' => 2,
        'price' => 25000,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'preparation_location' => $preparationLocation,
        'status' => 'pending',
    ]);

    if ($assignedPrinterLocations !== []) {
        $printerIds = Printer::query()
            ->whereIn('location', $assignedPrinterLocations)
            ->pluck('id')
            ->all();

        $inventoryItem->printers()->sync($printerIds);
    }

    return $order;
}

function makeTransactionHistoryPrinter(string $location, bool $isDefault = false): Printer
{
    return Printer::create([
        'name' => strtoupper($location).' Printer',
        'location' => $location,
        'connection_type' => 'log',
        'ip' => null,
        'port' => 9100,
        'path' => null,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'show_qr_code' => false,
        'width' => 42,
        'is_default' => $isDefault,
        'is_active' => true,
    ]);
}

test('transaction history print requires reprint flag after first print', function () {
    $admin = adminUser();

    makeTransactionHistoryPrinter('cashier', true);
    makeTransactionHistoryPrinter('kitchen');

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['kitchen']);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'kitchen',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->kitchen_print_count)->toBe(1);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'kitchen',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'kitchen',
            'is_reprint' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->kitchen_print_count)->toBe(2);
});

test('transaction history print rejects bar print for order without bar items', function () {
    $admin = adminUser();

    makeTransactionHistoryPrinter('cashier', true);
    makeTransactionHistoryPrinter('bar');

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['kitchen']);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'bar',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('transaction history print ignores preparation_location and follows assigned printer type', function () {
    $admin = adminUser();

    makeTransactionHistoryPrinter('cashier', true);
    makeTransactionHistoryPrinter('checker');

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['cashier', 'checker']);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'kitchen',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});
