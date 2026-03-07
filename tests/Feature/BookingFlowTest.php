<?php

use App\Models\Area;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\User;

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeArea(): Area
{
    return Area::create([
        'code' => 'AREA-'.uniqid(),
        'name' => 'Test Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function makeTable(Area $area, array $attrs = []): Tabel
{
    return Tabel::create(array_merge([
        'area_id' => $area->id,
        'table_number' => 'T-'.uniqid(),
        'qr_code' => 'QR-'.uniqid(),
        'capacity' => 4,
        'minimum_charge' => 100000,
        'status' => 'available',
        'is_active' => true,
    ], $attrs));
}

function makeBookingCustomer(): User
{
    // A plain User suffices — store/updateStatus only validate customer_id exists in users
    return User::factory()->create();
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('creating a booking always starts with pending status', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $customer = makeBookingCustomer();

    $this->actingAs($admin)
        ->post(route('admin.bookings.store'), [
            'table_id' => $table->id,
            'customer_id' => $customer->id,
            'reservation_date' => now()->addDays(3)->toDateString(),
            'reservation_time' => '19:00',
            'status' => 'confirmed', // attempt to bypass — should be ignored
            'note' => null,
        ])
        ->assertRedirect(route('admin.bookings.index'));

    $booking = TableReservation::where('table_id', $table->id)
        ->where('customer_id', $customer->id)
        ->first();

    expect($booking)->not->toBeNull()
        ->and($booking->status)->toBe('pending');
});

test('creating a booking does not change table status to reserved', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $customer = makeBookingCustomer();

    $this->actingAs($admin)
        ->post(route('admin.bookings.store'), [
            'table_id' => $table->id,
            'customer_id' => $customer->id,
            'reservation_date' => now()->addDays(3)->toDateString(),
            'reservation_time' => '19:00',
        ]);

    $table->refresh();
    expect($table->status)->toBe('available');
});

test('confirming a booking sets table status to reserved', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $customer = makeBookingCustomer();

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->addDays(3)->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.bookings.updateStatus', $booking), ['status' => 'confirmed'])
        ->assertRedirect(route('admin.bookings.index'));

    $booking->refresh();
    $table->refresh();

    expect($booking->status)->toBe('confirmed')
        ->and($table->status)->toBe('reserved');
});

test('confirming a second booking for the same table and date fails', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $customerA = makeBookingCustomer();
    $customerB = makeBookingCustomer();
    $date = now()->addDays(3)->toDateString();

    // First booking already confirmed
    TableReservation::create([
        'booking_code' => rand(1000, 4999),
        'table_id' => $table->id,
        'customer_id' => $customerA->id,
        'reservation_date' => $date,
        'reservation_time' => '19:00',
        'status' => 'confirmed',
    ]);
    Tabel::where('id', $table->id)->update(['status' => 'reserved']);

    // Second pending booking for the same table/date
    $bookingB = TableReservation::create([
        'booking_code' => rand(5000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customerB->id,
        'reservation_date' => $date,
        'reservation_time' => '20:00',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.bookings.updateStatus', $bookingB), ['status' => 'confirmed'])
        ->assertRedirect();

    $bookingB->refresh();
    expect($bookingB->status)->toBe('pending'); // still pending, not confirmed
});

test('completing a booking sets table status back to available', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area, ['status' => 'reserved']);
    $customer = makeBookingCustomer();

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.bookings.updateStatus', $booking), ['status' => 'completed'])
        ->assertRedirect(route('admin.bookings.index'));

    $table->refresh();
    expect($table->status)->toBe('available');
});

test('cancelling a booking sets table status back to available', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area, ['status' => 'reserved']);
    $customer = makeBookingCustomer();

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.bookings.updateStatus', $booking), ['status' => 'cancelled'])
        ->assertRedirect(route('admin.bookings.index'));

    $table->refresh();
    expect($table->status)->toBe('available');
});

test('pending tab shows pending bookings and is accessible', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $customer = makeBookingCustomer();

    TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->addDays(2)->toDateString(),
        'reservation_time' => '20:00',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['tab' => 'pending']))
        ->assertOk()
        ->assertViewIs('bookings.index')
        ->assertViewHas('tab', 'pending')
        ->assertViewHas('conflictingPendingKeys')
        ->assertViewHas('blockedPendingKeys');
});

test('pending tab conflict keys include competing bookings', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $date = now()->addDays(5)->toDateString();
    $customerA = makeBookingCustomer();
    $customerB = makeBookingCustomer();

    TableReservation::create([
        'booking_code' => rand(1000, 4999),
        'table_id' => $table->id,
        'customer_id' => $customerA->id,
        'reservation_date' => $date,
        'reservation_time' => '19:00',
        'status' => 'pending',
    ]);

    TableReservation::create([
        'booking_code' => rand(5000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customerB->id,
        'reservation_date' => $date,
        'reservation_time' => '20:00',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['tab' => 'pending']));

    $response->assertOk();

    $conflictKeys = $response->viewData('conflictingPendingKeys');
    expect($conflictKeys)->toContain($table->id.'_'.$date);
});

test('admin can assign a waiter to an active session', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $customer = makeBookingCustomer();

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '20:00',
        'status' => 'checked_in',
    ]);

    $session = \App\Models\TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SES-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    $waiterRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Waiter/Server', 'guard_name' => 'web']);
    $waiter = User::factory()->create(['name' => 'Waiter John']);
    $waiter->assignRole($waiterRole);

    $this->actingAs($admin)
        ->post(route('admin.bookings.assignWaiter', $booking->id), [
            'waiter_id' => $waiter->id,
        ])
        ->assertRedirect();

    expect($session->fresh()->waiter_id)->toBe($waiter->id);
});

test('admin can unassign a waiter from an active session', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $customer = makeBookingCustomer();

    $waiterRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Waiter/Server', 'guard_name' => 'web']);
    $waiter = User::factory()->create(['name' => 'Waiter Jane']);
    $waiter->assignRole($waiterRole);

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '21:00',
        'status' => 'checked_in',
    ]);

    $session = \App\Models\TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiter->id,
        'session_code' => 'SES-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.bookings.assignWaiter', $booking->id), [
            'waiter_id' => '',
        ])
        ->assertRedirect();

    expect($session->fresh()->waiter_id)->toBeNull();
});
