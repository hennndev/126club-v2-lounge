<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
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

test('creating a booking is blocked when customer has an active table session', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area);
    $otherTable = makeTable($area, ['table_number' => 'T-OTHER-'.uniqid()]);
    $customer = makeBookingCustomer();

    TableSession::create([
        'table_id' => $otherTable->id,
        'customer_id' => $customer->id,
        'session_code' => 'SES-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.bookings.store'), [
            'table_id' => $table->id,
            'customer_id' => $customer->id,
            'reservation_date' => now()->addDays(1)->toDateString(),
            'reservation_time' => '19:00',
        ])
        ->assertRedirect(route('admin.bookings.index'))
        ->assertSessionHasErrors('customer_id');

    $bookingCount = TableReservation::where('table_id', $table->id)
        ->where('customer_id', $customer->id)
        ->count();

    expect($bookingCount)->toBe(0);
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

test('booking modal keeps table selectable when only cancelled booking exists', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area, ['status' => 'reserved']);
    $customer = makeBookingCustomer();

    TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'cancelled',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.bookings.index'))
        ->assertOk();

    $response->assertSee($table->table_number)
        ->assertSee('•Free')
        ->assertDontSee('•Busy');
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

test('active bookings page includes transaction checker progress for close billing', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area, ['status' => 'occupied']);
    $customer = makeBookingCustomer();

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => now()->format('H:i:s'),
        'status' => 'checked_in',
    ]);

    $session = TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    $billing = Billing::create([
        'table_session_id' => $session->id,
        'minimum_charge' => 0,
        'orders_total' => 120000,
        'subtotal' => 120000,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => 120000,
        'paid_amount' => 0,
        'billing_status' => 'draft',
    ]);

    $session->update(['billing_id' => $billing->id]);

    $inventoryItem = InventoryItem::create([
        'code' => 'INV-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Checker Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 60000,
        'stock_quantity' => 10,
        'threshold' => 2,
        'unit' => 'glass',
        'is_active' => true,
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'ORD-'.uniqid(),
        'status' => 'ready',
        'items_total' => 120000,
        'discount_amount' => 0,
        'total' => 120000,
        'ordered_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => $inventoryItem->name,
        'item_code' => $inventoryItem->code,
        'quantity' => 1,
        'price' => 60000,
        'subtotal' => 60000,
        'discount_amount' => 0,
        'preparation_location' => 'bar',
        'status' => 'served',
        'served_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => $inventoryItem->name,
        'item_code' => $inventoryItem->code,
        'quantity' => 1,
        'price' => 60000,
        'subtotal' => 60000,
        'discount_amount' => 0,
        'preparation_location' => 'bar',
        'status' => 'ready',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['tab' => 'active']))
        ->assertOk()
        ->assertSee('data-checker-checked="1"', false)
        ->assertSee('data-checker-total="2"', false)
        ->assertSee('Transaction Checker belum lengkap');
});

test('billing cannot be closed while transaction checker is incomplete', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area, ['status' => 'occupied']);
    $customer = makeBookingCustomer();

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => now()->format('H:i:s'),
        'status' => 'checked_in',
    ]);

    $session = TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    $billing = Billing::create([
        'table_session_id' => $session->id,
        'minimum_charge' => 0,
        'orders_total' => 120000,
        'subtotal' => 120000,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => 120000,
        'paid_amount' => 0,
        'billing_status' => 'draft',
    ]);

    $session->update(['billing_id' => $billing->id]);

    $inventoryItem = InventoryItem::create([
        'code' => 'INV-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Checker Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 60000,
        'stock_quantity' => 10,
        'threshold' => 2,
        'unit' => 'glass',
        'is_active' => true,
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'ORD-'.uniqid(),
        'status' => 'ready',
        'items_total' => 120000,
        'discount_amount' => 0,
        'total' => 120000,
        'ordered_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => $inventoryItem->name,
        'item_code' => $inventoryItem->code,
        'quantity' => 1,
        'price' => 60000,
        'subtotal' => 60000,
        'discount_amount' => 0,
        'preparation_location' => 'bar',
        'status' => 'served',
        'served_at' => now(),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => $inventoryItem->name,
        'item_code' => $inventoryItem->code,
        'quantity' => 1,
        'price' => 60000,
        'subtotal' => 60000,
        'discount_amount' => 0,
        'preparation_location' => 'bar',
        'status' => 'ready',
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.bookings.closeBilling', $booking), [
            'payment_mode' => 'normal',
            'payment_method' => 'cash',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Billing tidak bisa ditutup karena masih ada item di Transaction Checker yang belum selesai.');

    expect($billing->fresh()->billing_status)->toBe('draft')
        ->and($session->fresh()->status)->toBe('active')
        ->and($booking->fresh()->status)->toBe('checked_in')
        ->and($table->fresh()->status)->toBe('occupied');
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

test('history tab shows ordered items for each booking customer', function () {
    $admin = adminUser();
    $area = makeArea();
    $table = makeTable($area, ['status' => 'available']);
    $customer = makeBookingCustomer();

    $booking = TableReservation::create([
        'booking_code' => rand(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'completed',
    ]);

    $session = TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SES-'.uniqid(),
        'checked_in_at' => now()->subHours(2),
        'checked_out_at' => now()->subHour(),
        'status' => 'completed',
    ]);

    $item = InventoryItem::create([
        'code' => 'HIST-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Nasi Goreng Test',
        'category_type' => 'food',
        'price' => 50000,
        'stock_quantity' => 20,
        'threshold' => 5,
        'unit' => 'plate',
        'is_active' => true,
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'ORD-'.uniqid(),
        'status' => 'completed',
        'items_total' => 100000,
        'discount_amount' => 0,
        'total' => 100000,
        'ordered_at' => now()->subHours(2),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $item->id,
        'item_name' => $item->name,
        'item_code' => $item->code,
        'quantity' => 2,
        'price' => 50000,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'served',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['tab' => 'history']))
        ->assertOk()
        ->assertSee('Lihat Orders (1 item)');
});
