<?php

use App\Jobs\NightlyTableReset;
use App\Models\Area;
use App\Models\Billing;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;

// Helper to create the minimum required records
function makeTableAndSession(string $tableStatus = 'occupied', string $sessionStatus = 'active'): array
{
    $area = Area::create(['code' => 'TEST-'.uniqid(), 'name' => 'Test Area', 'is_active' => true, 'sort_order' => 0]);
    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'T'.uniqid(),
        'qr_code' => 'QR-'.uniqid(),
        'capacity' => 4,
        'status' => $tableStatus,
        'is_active' => true,
    ]);
    $customer = User::factory()->create();
    $session = TableSession::create(['table_id' => $table->id, 'customer_id' => $customer->id, 'session_code' => 'SES-'.uniqid(), 'status' => $sessionStatus]);

    return compact('area', 'table', 'session');
}

it('completes active sessions and resets tables to available', function (): void {
    [, $table, $session] = array_values(makeTableAndSession('occupied', 'active'));

    (new NightlyTableReset)->handle();

    expect($session->fresh()->status)->toBe('force_closed')
        ->and($session->fresh()->checked_out_at)->not->toBeNull()
        ->and($table->fresh()->status)->toBe('available');
});

it('force-closes unpaid draft billings and keeps them in history', function (): void {
    [, $table, $session] = array_values(makeTableAndSession('occupied', 'active'));
    $billing = Billing::create(['table_session_id' => $session->id, 'billing_status' => 'draft']);

    (new NightlyTableReset)->handle();

    expect($billing->fresh()->billing_status)->toBe('force_closed')
        ->and($billing->fresh()->closing_notes)->not->toBeNull();
});

it('force-closes finalized unpaid billings', function (): void {
    [, , $session] = array_values(makeTableAndSession('occupied', 'active'));
    $billing = Billing::create(['table_session_id' => $session->id, 'billing_status' => 'finalized']);

    (new NightlyTableReset)->handle();

    expect($billing->fresh()->billing_status)->toBe('force_closed');
});

it('does not touch already paid billings', function (): void {
    [, , $session] = array_values(makeTableAndSession('occupied', 'active'));
    $billing = Billing::create(['table_session_id' => $session->id, 'billing_status' => 'paid']);

    (new NightlyTableReset)->handle();

    expect($billing->fresh()->billing_status)->toBe('paid');
});

it('resets stray reserved tables that have no active session', function (): void {
    $area = Area::create(['code' => 'STRAY-'.uniqid(), 'name' => 'Stray', 'is_active' => true, 'sort_order' => 0]);
    $table = Tabel::create(['area_id' => $area->id, 'table_number' => 'S'.uniqid(), 'qr_code' => 'QR-'.uniqid(), 'capacity' => 4, 'status' => 'reserved', 'is_active' => true]);

    (new NightlyTableReset)->handle();

    expect($table->fresh()->status)->toBe('available');
});

it('resets multiple active sessions at once', function (): void {
    $data1 = makeTableAndSession();
    $data2 = makeTableAndSession();

    (new NightlyTableReset)->handle();

    expect($data1['session']->fresh()->status)->toBe('force_closed')
        ->and($data2['session']->fresh()->status)->toBe('force_closed')
        ->and($data1['table']->fresh()->status)->toBe('available')
        ->and($data2['table']->fresh()->status)->toBe('available');
});

it('does nothing when no active sessions exist', function (): void {
    $area = Area::create(['code' => 'IDLE-'.uniqid(), 'name' => 'Idle', 'is_active' => true, 'sort_order' => 0]);
    $table = Tabel::create(['area_id' => $area->id, 'table_number' => 'I'.uniqid(), 'qr_code' => 'QR-'.uniqid(), 'capacity' => 4, 'status' => 'available', 'is_active' => true]);

    (new NightlyTableReset)->handle();

    expect($table->fresh()->status)->toBe('available');
});

it('marks the linked table reservation as force_closed', function (): void {
    ['table' => $table, 'session' => $session] = makeTableAndSession('occupied', 'active');
    $customer = User::factory()->create();
    $reservation = TableReservation::create([
        'booking_code' => 'BK-'.uniqid(),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '21:00:00',
        'status' => 'checked_in',
    ]);
    $session->update(['table_reservation_id' => $reservation->id]);

    (new NightlyTableReset)->handle();

    expect($reservation->fresh()->status)->toBe('force_closed');
});
