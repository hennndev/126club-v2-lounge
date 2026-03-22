<?php

use App\Models\Area;
use App\Models\DailyAuthCode;
use App\Models\Order;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('pending order can be cancelled with valid daily auth code', function () {
    $admin = adminUser();
    $customer = User::factory()->create();

    $area = Area::create([
        'code' => 'BCO-AREA-'.uniqid(),
        'name' => 'Cancel Order Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'BCO-T-'.uniqid(),
        'qr_code' => 'BCO-QR-'.uniqid(),
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
        'session_code' => 'BCO-SES-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'BCO-ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    DailyAuthCode::updateOrCreate(
        ['date' => now()->format('Y-m-d')],
        ['code' => '1234', 'override_code' => null, 'generated_at' => now()]
    );

    actingAs($admin)
        ->post(route('admin.bookings.cancelOrder', $booking), [
            'order_id' => $order->id,
            'cancel_auth_code' => '1234',
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->cancelled_by)->toBe($admin->id);
});

test('pending order cannot be cancelled with invalid daily auth code', function () {
    $admin = adminUser();
    $customer = User::factory()->create();

    $area = Area::create([
        'code' => 'BCO-AREA-'.uniqid(),
        'name' => 'Cancel Order Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'BCO-T-'.uniqid(),
        'qr_code' => 'BCO-QR-'.uniqid(),
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
        'session_code' => 'BCO-SES-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'BCO-ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    DailyAuthCode::updateOrCreate(
        ['date' => now()->format('Y-m-d')],
        ['code' => '1234', 'override_code' => null, 'generated_at' => now()]
    );

    actingAs($admin)
        ->from(route('admin.bookings.index', ['tab' => 'active']))
        ->post(route('admin.bookings.cancelOrder', $booking), [
            'order_id' => $order->id,
            'cancel_auth_code' => '9999',
        ])
        ->assertSessionHasErrors('cancel_auth_code');

    expect($order->fresh()->status)->toBe('pending');
});

test('only pending order can be cancelled', function () {
    $admin = adminUser();
    $customer = User::factory()->create();

    $area = Area::create([
        'code' => 'BCO-AREA-'.uniqid(),
        'name' => 'Cancel Order Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'BCO-T-'.uniqid(),
        'qr_code' => 'BCO-QR-'.uniqid(),
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
        'session_code' => 'BCO-SES-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'BCO-ORD-'.uniqid(),
        'status' => 'completed',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    DailyAuthCode::updateOrCreate(
        ['date' => now()->format('Y-m-d')],
        ['code' => '1234', 'override_code' => null, 'generated_at' => now()]
    );

    actingAs($admin)
        ->from(route('admin.bookings.index', ['tab' => 'active']))
        ->post(route('admin.bookings.cancelOrder', $booking), [
            'order_id' => $order->id,
            'cancel_auth_code' => '1234',
        ])
        ->assertSessionHasErrors('order_id');

    expect($order->fresh()->status)->toBe('completed');
});
