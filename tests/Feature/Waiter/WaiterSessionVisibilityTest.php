<?php

use App\Models\Area;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

function makeWaiterUser(string $name): User
{
    Role::firstOrCreate(['name' => 'Waiter/Server', 'guard_name' => 'web']);

    $user = User::factory()->create(['name' => $name]);
    $user->assignRole('Waiter/Server');

    return $user;
}

function makeWaiterArea(): Area
{
    return Area::create([
        'code' => 'WTR-'.uniqid(),
        'name' => 'Waiter Area '.uniqid(),
        'capacity' => 20,
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function makeWaiterTable(Area $area, string $number): Tabel
{
    return Tabel::create([
        'area_id' => $area->id,
        'table_number' => $number,
        'qr_code' => 'QR-'.$number.'-'.uniqid(),
        'capacity' => 4,
        'minimum_charge' => 0,
        'status' => 'occupied',
        'is_active' => true,
    ]);
}

test('waiter active tables page only shows sessions assigned to logged in waiter', function () {
    $waiterHendra = makeWaiterUser('Hendra');
    $waiterBudi = makeWaiterUser('Budi');
    $customer = User::factory()->create();

    $area = makeWaiterArea();
    $tableForHendra = makeWaiterTable($area, 'A-01');
    $tableForBudi = makeWaiterTable($area, 'A-02');

    TableSession::create([
        'table_id' => $tableForHendra->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiterHendra->id,
        'session_code' => 'SES-HND-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    TableSession::create([
        'table_id' => $tableForBudi->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiterBudi->id,
        'session_code' => 'SES-BUD-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    actingAs($waiterHendra)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('waiter.active-tables'))
        ->assertSuccessful()
        ->assertSee('A-01')
        ->assertDontSee('A-02');
});

test('waiter pos page only lists active sessions assigned to logged in waiter', function () {
    $waiterHendra = makeWaiterUser('Hendra');
    $waiterBudi = makeWaiterUser('Budi');
    $customer = User::factory()->create();

    $area = makeWaiterArea();
    $tableForHendra = makeWaiterTable($area, 'B-01');
    $tableForBudi = makeWaiterTable($area, 'B-02');

    $reservationForHendra = TableReservation::create([
        'booking_code' => random_int(100000, 999999),
        'table_id' => $tableForHendra->id,
        'customer_id' => $customer->id,
        'reservation_date' => today(),
        'reservation_time' => now()->format('H:i:s'),
        'status' => 'checked_in',
    ]);

    $reservationForBudi = TableReservation::create([
        'booking_code' => random_int(100000, 999999),
        'table_id' => $tableForBudi->id,
        'customer_id' => $customer->id,
        'reservation_date' => today(),
        'reservation_time' => now()->format('H:i:s'),
        'status' => 'checked_in',
    ]);

    TableSession::create([
        'table_reservation_id' => $reservationForHendra->id,
        'table_id' => $tableForHendra->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiterHendra->id,
        'session_code' => 'SES2-HND-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    TableSession::create([
        'table_reservation_id' => $reservationForBudi->id,
        'table_id' => $tableForBudi->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiterBudi->id,
        'session_code' => 'SES2-BUD-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    $response = actingAs($waiterHendra)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('waiter.pos'))
        ->assertSuccessful();

    $activeSessions = collect($response->viewData('activeSessions'));
    $tableNumbers = $activeSessions->pluck('table.table_number')->all();

    expect($tableNumbers)
        ->toContain('B-01')
        ->not->toContain('B-02');
});
