<?php

use App\Models\Area;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('selected order items from multiple source orders are moved into one new target order', function () {
    $admin = adminUser();
    $customerA = User::factory()->create();
    $customerB = User::factory()->create();

    $area = Area::create([
        'code' => 'MVO-AREA-'.uniqid(),
        'name' => 'Move Order Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $tableA = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'MVO-A-'.uniqid(),
        'qr_code' => 'MVO-QRA-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $tableB = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'MVO-B-'.uniqid(),
        'qr_code' => 'MVO-QRB-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $bookingA = TableReservation::create([
        'booking_code' => random_int(1000, 9999),
        'table_id' => $tableA->id,
        'customer_id' => $customerA->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'checked_in',
    ]);

    $bookingB = TableReservation::create([
        'booking_code' => random_int(10000, 19999),
        'table_id' => $tableB->id,
        'customer_id' => $customerB->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '20:00',
        'status' => 'checked_in',
    ]);

    $sourceSession = TableSession::create([
        'table_reservation_id' => $bookingA->id,
        'table_id' => $tableA->id,
        'customer_id' => $customerA->id,
        'session_code' => 'MVO-SRC-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $targetSession = TableSession::create([
        'table_reservation_id' => $bookingB->id,
        'table_id' => $tableB->id,
        'customer_id' => $customerB->id,
        'session_code' => 'MVO-DST-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $order = Order::create([
        'table_session_id' => $sourceSession->id,
        'created_by' => $admin->id,
        'order_number' => 'MVO-ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 160000,
        'discount_amount' => 0,
        'total' => 160000,
        'ordered_at' => now(),
    ]);

    $orderSecond = Order::create([
        'table_session_id' => $sourceSession->id,
        'created_by' => $admin->id,
        'order_number' => 'MVO-ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    $inventoryItem = InventoryItem::create([
        'code' => 'MVO-INV-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Move Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 100000,
        'stock_quantity' => 100,
        'threshold' => 5,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $itemToMove = OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => 'Move Me',
        'item_code' => 'MOVE-1',
        'quantity' => 1,
        'price' => 100000,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'service_charge_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'pending',
    ]);

    $itemStay = OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => 'Stay Here',
        'item_code' => 'MOVE-2',
        'quantity' => 1,
        'price' => 60000,
        'subtotal' => 60000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'service_charge_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'pending',
    ]);

    $itemFromSecondOrder = OrderItem::create([
        'order_id' => $orderSecond->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => 'Move Me Too',
        'item_code' => 'MOVE-3',
        'quantity' => 1,
        'price' => 50000,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'service_charge_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'pending',
    ]);

    actingAs($admin)
        ->post(route('admin.bookings.moveOrder', $bookingA), [
            'order_item_ids' => [$itemToMove->id, $itemFromSecondOrder->id],
            'target_table_session_id' => $targetSession->id,
        ])
        ->assertRedirect();

    $movedItem = $itemToMove->fresh();
    $movedItemSecond = $itemFromSecondOrder->fresh();
    $remainingItem = $itemStay->fresh();

    $newOrder = Order::query()
        ->where('table_session_id', $targetSession->id)
        ->where('id', '!=', $order->id)
        ->latest('id')
        ->first();

    expect($newOrder)->not->toBeNull()
        ->and($movedItem->order_id)->toBe($newOrder->id)
        ->and($movedItemSecond->order_id)->toBe($newOrder->id)
        ->and($remainingItem->order_id)->toBe($order->id)
        ->and((float) $newOrder->fresh()->total)->toBe(150000.0)
        ->and((float) $order->fresh()->total)->toBe(60000.0)
        ->and($orderSecond->fresh()->status)->toBe('cancelled');
});

test('move order fails when target session equals source session', function () {
    $admin = adminUser();
    $customer = User::factory()->create();

    $area = Area::create([
        'code' => 'MVO-AREA-'.uniqid(),
        'name' => 'Move Order Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'MVO-T-'.uniqid(),
        'qr_code' => 'MVO-QRT-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $booking = TableReservation::create([
        'booking_code' => random_int(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'checked_in',
    ]);

    $session = TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'MVO-SRC-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'MVO-ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    $inventoryItem = InventoryItem::create([
        'code' => 'MVO-INV-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Move Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 50000,
        'stock_quantity' => 100,
        'threshold' => 5,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $item = OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => 'Move Me',
        'item_code' => 'MOVE-1',
        'quantity' => 1,
        'price' => 50000,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'service_charge_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'pending',
    ]);

    actingAs($admin)
        ->from(route('admin.bookings.index', ['tab' => 'active']))
        ->post(route('admin.bookings.moveOrder', $booking), [
            'order_item_ids' => [$item->id],
            'target_table_session_id' => $session->id,
        ])
        ->assertSessionHasErrors('target_table_session_id');

    expect($order->fresh()->table_session_id)->toBe($session->id);
});

test('move order fails when selected item is cancelled', function () {
    $admin = adminUser();
    $customerA = User::factory()->create();
    $customerB = User::factory()->create();

    $area = Area::create([
        'code' => 'MVO-AREA-'.uniqid(),
        'name' => 'Move Order Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $tableA = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'MVO-A-'.uniqid(),
        'qr_code' => 'MVO-QRA-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $tableB = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'MVO-B-'.uniqid(),
        'qr_code' => 'MVO-QRB-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $bookingA = TableReservation::create([
        'booking_code' => random_int(1000, 9999),
        'table_id' => $tableA->id,
        'customer_id' => $customerA->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'checked_in',
    ]);

    $bookingB = TableReservation::create([
        'booking_code' => random_int(10000, 19999),
        'table_id' => $tableB->id,
        'customer_id' => $customerB->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '20:00',
        'status' => 'checked_in',
    ]);

    $sourceSession = TableSession::create([
        'table_reservation_id' => $bookingA->id,
        'table_id' => $tableA->id,
        'customer_id' => $customerA->id,
        'session_code' => 'MVO-SRC-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $targetSession = TableSession::create([
        'table_reservation_id' => $bookingB->id,
        'table_id' => $tableB->id,
        'customer_id' => $customerB->id,
        'session_code' => 'MVO-DST-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $order = Order::create([
        'table_session_id' => $sourceSession->id,
        'created_by' => $admin->id,
        'order_number' => 'MVO-ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 100000,
        'discount_amount' => 0,
        'total' => 100000,
        'ordered_at' => now(),
    ]);

    $inventoryItem = InventoryItem::create([
        'code' => 'MVO-INV-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Move Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 100000,
        'stock_quantity' => 100,
        'threshold' => 5,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $cancelledItem = OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => 'Cancelled Item',
        'item_code' => 'MOVE-3',
        'quantity' => 1,
        'price' => 100000,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'service_charge_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'cancelled',
        'cancelled_at' => now(),
        'cancelled_by' => $admin->id,
    ]);

    actingAs($admin)
        ->from(route('admin.bookings.index', ['tab' => 'active']))
        ->post(route('admin.bookings.moveOrder', $bookingA), [
            'order_item_ids' => [$cancelledItem->id],
            'target_table_session_id' => $targetSession->id,
        ])
        ->assertSessionHasErrors('order_item_ids');

    expect($order->fresh()->table_session_id)->toBe($sourceSession->id);
});
