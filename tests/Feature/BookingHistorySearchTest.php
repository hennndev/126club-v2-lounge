<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('bookings history can be filtered by billing transaction code', function () {
    $admin = adminUser();
    $customer = User::factory()->create(['name' => 'History Search Customer']);

    $area = Area::create([
        'code' => 'BHS-AREA-'.uniqid(),
        'name' => 'Booking History Search Area '.uniqid(),
        'is_active' => true,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'BHS-TBL-'.uniqid(),
        'qr_code' => 'BHS-QR-'.uniqid(),
        'capacity' => 4,
        'status' => 'available',
        'is_active' => true,
    ]);

    $booking = TableReservation::create([
        'booking_code' => random_int(100000, 999999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '20:00:00',
        'status' => 'completed',
    ]);

    $session = TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'BHS-SES-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'completed',
    ]);

    $billing = Billing::create([
        'table_session_id' => $session->id,
        'is_booking' => true,
        'is_walk_in' => false,
        'transaction_code' => 'BILLING-BHS-001',
        'orders_total' => 250000,
        'subtotal' => 250000,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => 250000,
        'paid_amount' => 250000,
        'billing_status' => 'paid',
        'paid_at' => now(),
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
        'foc_comp_payment_method' => 'FOC',
    ]);

    $session->update(['billing_id' => $billing->id]);

    actingAs($admin)
        ->get(route('admin.bookings.index', [
            'tab' => 'history',
            'search' => 'BILLING-BHS-001',
        ]))
        ->assertSuccessful()
        ->assertSeeText('History Search Customer');
});

test('bookings history can be filtered by session id', function () {
    $admin = adminUser();

    $firstCustomer = User::factory()->create(['name' => 'History Session Match']);
    $secondCustomer = User::factory()->create(['name' => 'History Session Other']);

    $area = Area::create([
        'code' => 'BHS-SESSION-AREA-'.uniqid(),
        'name' => 'Booking History Session Area '.uniqid(),
        'is_active' => true,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'BHS-SESSION-TBL-'.uniqid(),
        'qr_code' => 'BHS-SESSION-QR-'.uniqid(),
        'capacity' => 4,
        'status' => 'available',
        'is_active' => true,
    ]);

    $firstBooking = TableReservation::create([
        'booking_code' => random_int(100000, 999999),
        'table_id' => $table->id,
        'customer_id' => $firstCustomer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00:00',
        'status' => 'completed',
    ]);

    $secondBooking = TableReservation::create([
        'booking_code' => random_int(100000, 999999),
        'table_id' => $table->id,
        'customer_id' => $secondCustomer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '21:00:00',
        'status' => 'completed',
    ]);

    $firstSession = TableSession::create([
        'table_reservation_id' => $firstBooking->id,
        'table_id' => $table->id,
        'customer_id' => $firstCustomer->id,
        'session_code' => 'BHS-SESSION-1-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'completed',
    ]);

    $secondSession = TableSession::create([
        'table_reservation_id' => $secondBooking->id,
        'table_id' => $table->id,
        'customer_id' => $secondCustomer->id,
        'session_code' => 'BHS-SESSION-2-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'completed',
    ]);

    $firstBilling = Billing::create([
        'table_session_id' => $firstSession->id,
        'is_booking' => true,
        'is_walk_in' => false,
        'transaction_code' => 'BILLING-BHS-SESSION-001',
        'orders_total' => 250000,
        'subtotal' => 250000,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => 250000,
        'paid_amount' => 250000,
        'billing_status' => 'paid',
        'paid_at' => now(),
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);

    $secondBilling = Billing::create([
        'table_session_id' => $secondSession->id,
        'is_booking' => true,
        'is_walk_in' => false,
        'transaction_code' => 'BILLING-BHS-SESSION-002',
        'orders_total' => 180000,
        'subtotal' => 180000,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => 180000,
        'paid_amount' => 180000,
        'billing_status' => 'paid',
        'paid_at' => now(),
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);

    $firstSession->update(['billing_id' => $firstBilling->id]);
    $secondSession->update(['billing_id' => $secondBilling->id]);

    actingAs($admin)
        ->get(route('admin.bookings.index', [
            'tab' => 'history',
            'session_id' => $firstSession->id,
        ]))
        ->assertSuccessful()
        ->assertSeeText('History Session Match')
        ->assertDontSeeText('History Session Other');
});
