<?php

use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Services\PrinterService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

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

test('transaction history print allows bar print even when order has no bar items', function () {
    $admin = adminUser();

    makeTransactionHistoryPrinter('cashier', true);
    makeTransactionHistoryPrinter('bar');

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['kitchen']);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'bar',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->bar_print_count)->toBe(1);
});

test('transaction history print allows kitchen print regardless assigned printer types', function () {
    $admin = adminUser();

    makeTransactionHistoryPrinter('cashier', true);
    makeTransactionHistoryPrinter('checker');

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['cashier', 'checker']);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'kitchen',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->kitchen_print_count)->toBe(1);
});

test('transaction history resmi print always uses cashier type printer', function () {
    $admin = adminUser();

    $defaultKitchenPrinter = makeTransactionHistoryPrinter('kitchen', true);
    $cashierPrinter = makeTransactionHistoryPrinter('cashier', false);

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['kitchen']);

    mock(PrinterService::class, function (MockInterface $mock) use ($order, $cashierPrinter): void {
        $mock->shouldReceive('printReceipt')
            ->once()
            ->withArgs(function ($printedOrder, $printer) use ($order, $cashierPrinter): bool {
                return $printedOrder instanceof Order
                    && $printedOrder->id === $order->id
                    && $printer instanceof Printer
                    && $printer->id === $cashierPrinter->id;
            })
            ->andReturn(true);

        $mock->shouldReceive('printKitchenTicket')->never();
        $mock->shouldReceive('printBarTicket')->never();
    });

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'resmi',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->receipt_print_count)->toBe(1)
        ->and($defaultKitchenPrinter->id)->not()->toBe($cashierPrinter->id);
});

test('transaction history resmi print fails when cashier printer is unavailable', function () {
    $admin = adminUser();

    makeTransactionHistoryPrinter('kitchen', true);
    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['kitchen']);

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'resmi',
        ])
        ->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tidak ada printer aktif untuk lokasi Kasir.');
});

test('transaction history print uses selected active printer when printer_id is provided', function () {
    $admin = adminUser();

    $cashierPrinter = makeTransactionHistoryPrinter('cashier', true);
    $kitchenPrinter = makeTransactionHistoryPrinter('kitchen', false);

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['kitchen']);

    mock(PrinterService::class, function (MockInterface $mock) use ($order, $kitchenPrinter): void {
        $mock->shouldReceive('printReceipt')
            ->once()
            ->withArgs(function ($printedOrder, $printer) use ($order, $kitchenPrinter): bool {
                return $printedOrder instanceof Order
                    && $printedOrder->id === $order->id
                    && $printer instanceof Printer
                    && $printer->id === $kitchenPrinter->id;
            })
            ->andReturn(true);

        $mock->shouldReceive('printKitchenTicket')->never();
        $mock->shouldReceive('printBarTicket')->never();
    });

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'resmi',
            'printer_id' => $kitchenPrinter->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->receipt_print_count)->toBe(1)
        ->and($cashierPrinter->id)->not()->toBe($kitchenPrinter->id);
});

test('transaction history supports checker print type', function () {
    $admin = adminUser();

    $checkerPrinter = makeTransactionHistoryPrinter('checker', false);
    makeTransactionHistoryPrinter('cashier', true);

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['checker']);
    $order->update([
        'receipt_print_count' => 1,
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($order, $checkerPrinter): void {
        $mock->shouldReceive('printReceipt')
            ->once()
            ->withArgs(function ($printedOrder, $printer) use ($order, $checkerPrinter): bool {
                return $printedOrder instanceof Order
                    && $printedOrder->id === $order->id
                    && $printer instanceof Printer
                    && $printer->id === $checkerPrinter->id;
            })
            ->andReturn(true);

        $mock->shouldReceive('printKitchenTicket')->never();
        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printCheckerTicket')->never();
    });

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'checker',
            'printer_id' => $checkerPrinter->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->receipt_print_count)->toBe(1)
        ->and($order->fresh()->checker_print_count)->toBe(1);
});

test('transaction history bar print uses checker layout when bar order is missing but kitchen order exists', function () {
    $admin = adminUser();

    $barPrinter = makeTransactionHistoryPrinter('bar', false);
    makeTransactionHistoryPrinter('cashier', true);

    $order = makeTransactionHistoryOrder($admin->id, 'kitchen', ['bar']);

    $kitchenOrder = KitchenOrder::create([
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'customer_user_id' => null,
        'table_id' => null,
        'total_amount' => 50000,
        'status' => 'selesai',
        'progress' => 100,
    ]);

    KitchenOrderItem::create([
        'kitchen_order_id' => $kitchenOrder->id,
        'inventory_item_id' => null,
        'quantity' => 2,
        'price' => 25000,
        'is_completed' => true,
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($kitchenOrder, $barPrinter): void {
        $mock->shouldReceive('printBarTicket')
            ->once()
            ->withArgs(function ($printedOrder, $printer) use ($kitchenOrder, $barPrinter): bool {
                return $printedOrder instanceof KitchenOrder
                    && $printedOrder->id === $kitchenOrder->id
                    && $printer instanceof Printer
                    && $printer->id === $barPrinter->id;
            })
            ->andReturn(true);

        $mock->shouldReceive('printReceipt')->never();
        $mock->shouldReceive('printKitchenTicket')->never();
        $mock->shouldReceive('printCheckerTicket')->never();
    });

    actingAs($admin)
        ->postJson(route('admin.transaction-history.print', $order), [
            'type' => 'bar',
            'printer_id' => $barPrinter->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh()->bar_print_count)->toBe(1);
});
