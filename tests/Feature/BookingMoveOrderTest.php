<?php

use App\Models\Area;
use App\Models\Order;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('order can be moved to another active table session from active bookings flow', function () {
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

    actingAs($admin)
        ->post(route('admin.bookings.moveOrder', $bookingA), [
            'order_id' => $order->id,
            'target_table_session_id' => $targetSession->id,
        ])
        ->assertRedirect();

    expect($order->fresh()->table_session_id)->toBe($targetSession->id);
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

    actingAs($admin)
        ->from(route('admin.bookings.index', ['tab' => 'active']))
        ->post(route('admin.bookings.moveOrder', $booking), [
            'order_id' => $order->id,
            'target_table_session_id' => $session->id,
        ])
        ->assertSessionHasErrors('target_table_session_id');

    expect($order->fresh()->table_session_id)->toBe($session->id);
});

test('move order fails when order status is cancelled', function () {
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
        'status' => 'cancelled',
        'items_total' => 100000,
        'discount_amount' => 0,
        'total' => 100000,
        'ordered_at' => now(),
        'cancelled_at' => now(),
        'cancelled_by' => $admin->id,
    ]);

    actingAs($admin)
        ->from(route('admin.bookings.index', ['tab' => 'active']))
        ->post(route('admin.bookings.moveOrder', $bookingA), [
            'order_id' => $order->id,
            'target_table_session_id' => $targetSession->id,
        ])
        ->assertSessionHasErrors('order_id');

    expect($order->fresh()->table_session_id)->toBe($sourceSession->id);
});
