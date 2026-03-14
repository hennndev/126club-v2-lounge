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

use function Pest\Laravel\actingAs;

function makeBookingCloseBillingFixture(User $admin): array
{
    $customer = User::factory()->create();

    $area = Area::create([
        'code' => 'AREA-'.uniqid(),
        'name' => 'Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'TBL-'.uniqid(),
        'qr_code' => 'QR-'.uniqid(),
        'capacity' => 4,
        'minimum_charge' => 0,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $booking = TableReservation::create([
        'booking_code' => random_int(100000, 999999),
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
        'orders_total' => 0,
        'subtotal' => 0,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => 0,
        'paid_amount' => 0,
        'billing_status' => 'draft',
    ]);

    $session->update(['billing_id' => $billing->id]);

    $inventoryItem = InventoryItem::create([
        'code' => 'INV-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Billing Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 60000,
        'stock_quantity' => 50,
        'threshold' => 5,
        'unit' => 'glass',
        'is_active' => true,
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'ORD-'.uniqid(),
        'status' => 'pending',
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
        'quantity' => 2,
        'price' => 60000,
        'subtotal' => 120000,
        'discount_amount' => 0,
        'preparation_location' => 'bar',
        'status' => 'served',
    ]);

    return [$booking, $session, $billing];
}

test('close billing works with normal payment mode', function () {
    $admin = adminUser();
    [$booking] = makeBookingCloseBillingFixture($admin);

    $response = actingAs($admin)->postJson(route('admin.bookings.closeBilling', $booking), [
        'payment_mode' => 'normal',
        'payment_method' => 'cash',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $updatedBilling = $booking->fresh()->tableSession->billing;
    $updatedBooking = $booking->fresh();
    $updatedSession = $updatedBooking->tableSession;
    $updatedTable = $updatedBooking->table;

    expect($updatedBilling->billing_status)->toBe('paid')
        ->and($updatedBilling->payment_mode)->toBe('normal')
        ->and($updatedBilling->payment_method)->toBe('cash')
        ->and((float) $updatedBilling->split_cash_amount)->toBe(0.0)
        ->and((float) $updatedBilling->split_debit_amount)->toBe(0.0)
        ->and($updatedBooking->status)->toBe('completed')
        ->and($updatedSession?->status)->toBe('completed')
        ->and($updatedTable?->status)->toBe('available');
});

test('close billing works with normal non-cash payment and reference number', function () {
    $admin = adminUser();
    [$booking] = makeBookingCloseBillingFixture($admin);

    $response = actingAs($admin)->postJson(route('admin.bookings.closeBilling', $booking), [
        'payment_mode' => 'normal',
        'payment_method' => 'debit',
        'payment_reference_number' => 'APPROVAL-12345',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('receipt.payment_method', 'DEBIT')
        ->assertJsonPath('receipt.payment_reference_number', 'APPROVAL-12345');

    $updatedBilling = $booking->fresh()->tableSession->billing;

    expect($updatedBilling->billing_status)->toBe('paid')
        ->and($updatedBilling->payment_mode)->toBe('normal')
        ->and($updatedBilling->payment_method)->toBe('debit')
        ->and($updatedBilling->payment_reference_number)->toBe('APPROVAL-12345');
});

test('close billing works with split payment mode cash and non-cash method', function () {
    $admin = adminUser();
    [$booking] = makeBookingCloseBillingFixture($admin);

    // grand total on this fixture is 120000 when tax/service settings are 0,
    // but we derive it from response payload for stronger assertion.
    $previewResponse = actingAs($admin)->postJson(route('admin.bookings.closeBilling', $booking), [
        'payment_mode' => 'split',
        'split_cash_amount' => 70000,
        'split_non_cash_amount' => 50000,
        'split_non_cash_method' => 'kredit',
        'split_non_cash_reference_number' => 'KREDIT-9988',
    ]);

    $previewResponse
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $updatedBilling = $booking->fresh()->tableSession->billing;

    expect($updatedBilling->billing_status)->toBe('paid')
        ->and($updatedBilling->payment_mode)->toBe('split')
        ->and($updatedBilling->payment_method)->toBeNull()
        ->and((float) $updatedBilling->split_cash_amount)->toBe(70000.0)
        ->and((float) $updatedBilling->split_debit_amount)->toBe(50000.0)
        ->and($updatedBilling->split_non_cash_method)->toBe('kredit')
        ->and($updatedBilling->split_non_cash_reference_number)->toBe('KREDIT-9988')
        ->and((float) $updatedBilling->paid_amount)->toBe((float) $updatedBilling->grand_total);
});

test('close billing rejects split payment when totals do not match', function () {
    $admin = adminUser();
    [$booking] = makeBookingCloseBillingFixture($admin);

    $response = actingAs($admin)->postJson(route('admin.bookings.closeBilling', $booking), [
        'payment_mode' => 'split',
        'split_cash_amount' => 10000,
        'split_non_cash_amount' => 10000,
        'split_non_cash_method' => 'debit',
        'split_non_cash_reference_number' => 'DB-01',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    $updatedBilling = $booking->fresh()->tableSession->billing;

    expect($updatedBilling->billing_status)->toBe('draft')
        ->and($updatedBilling->payment_mode)->toBeNull()
        ->and($updatedBilling->payment_method)->toBeNull();
});

test('close billing rejects split payment when non-cash reference is missing', function () {
    $admin = adminUser();
    [$booking] = makeBookingCloseBillingFixture($admin);

    $response = actingAs($admin)->postJson(route('admin.bookings.closeBilling', $booking), [
        'payment_mode' => 'split',
        'split_cash_amount' => 70000,
        'split_non_cash_amount' => 50000,
        'split_non_cash_method' => 'debit',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('errors.split_non_cash_reference_number.0', 'Nomor referensi non-cash untuk split bill wajib diisi.');
});

test('close billing prioritizes active session when booking has multiple sessions', function () {
    $admin = adminUser();
    [$booking, $activeSession] = makeBookingCloseBillingFixture($admin);

    $olderSession = TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $booking->table_id,
        'customer_id' => $booking->customer_id,
        'session_code' => 'SESSION-OLDER-'.uniqid(),
        'checked_in_at' => now()->subHours(5),
        'checked_out_at' => now()->subHours(3),
        'status' => 'completed',
    ]);

    Billing::create([
        'table_session_id' => $olderSession->id,
        'minimum_charge' => 0,
        'orders_total' => 0,
        'subtotal' => 0,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => 0,
        'paid_amount' => 0,
        'billing_status' => 'paid',
        'payment_mode' => 'normal',
        'payment_method' => 'cash',
    ]);

    $response = actingAs($admin)->postJson(route('admin.bookings.closeBilling', $booking), [
        'payment_mode' => 'normal',
        'payment_method' => 'cash',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($activeSession->fresh()->status)->toBe('completed')
        ->and($activeSession->fresh()->billing?->billing_status)->toBe('paid')
        ->and($booking->fresh()->status)->toBe('completed')
        ->and($booking->fresh()->table?->status)->toBe('available');
});
