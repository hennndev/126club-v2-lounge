<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\Tabel;
use App\Models\TableSession;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

function makeWaiterForEstimateTest(): User
{
    Role::firstOrCreate(['name' => 'Waiter/Server', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('Waiter/Server');

    return $user;
}

test('active tables estimate uses total pesanan as the base instead of minimum charge', function () {
    $waiter = makeWaiterForEstimateTest();
    $customer = User::factory()->create();

    GeneralSetting::instance()->update([
        'service_charge_percentage' => 25,
        'tax_percentage' => 10,
    ]);

    $area = Area::create([
        'code' => 'VIP',
        'name' => 'VIP Room',
        'capacity' => 20,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'VIP-TABLE-1',
        'qr_code' => 'QR-VIP-1-'.uniqid(),
        'capacity' => 10,
        'minimum_charge' => 10000000,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $session = TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiter->id,
        'session_code' => 'SESSION-VIP-'.uniqid(),
        'checked_in_at' => now()->subMinutes(25),
        'status' => 'active',
        'pax' => 10,
    ]);

    $billing = Billing::create([
        'table_session_id' => $session->id,
        'minimum_charge' => 10000000,
        'orders_total' => 6600000,
        'subtotal' => 10000000,
        'tax' => 0,
        'tax_percentage' => 10,
        'service_charge' => 0,
        'service_charge_percentage' => 25,
        'discount_amount' => 0,
        'grand_total' => 0,
        'paid_amount' => 0,
        'billing_status' => 'draft',
    ]);

    $session->update(['billing_id' => $billing->id]);

    Order::create([
        'table_session_id' => $session->id,
        'created_by' => $waiter->id,
        'order_number' => 'ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 6600000,
        'discount_amount' => 0,
        'total' => 6600000,
        'ordered_at' => now(),
    ]);

    actingAs($waiter)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('waiter.active-tables'))
        ->assertSuccessful()
        ->assertViewIs('waiter.active-tables')
        ->assertSee('Total Pesanan')
        ->assertSee('Rp 6.600.000')
        ->assertDontSee('Subtotal Tagihan')
        ->assertSee('Service Charge (25%)')
        ->assertSee('Rp 1.650.000')
        ->assertSee('PPN (10%)')
        ->assertSee('Rp 825.000')
        ->assertSeeInOrder(['Estimasi Total', 'Rp 9.075.000']);
});

test('active tables estimate does not become negative when discount exceeds total pesanan', function () {
    $waiter = makeWaiterForEstimateTest();
    $customer = User::factory()->create();

    GeneralSetting::instance()->update([
        'service_charge_percentage' => 10,
        'tax_percentage' => 11,
    ]);

    $area = Area::create([
        'code' => 'MAIN',
        'name' => 'Main Area',
        'capacity' => 20,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => '12',
        'qr_code' => 'QR-12-'.uniqid(),
        'capacity' => 4,
        'minimum_charge' => 0,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $session = TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiter->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now()->subHour(),
        'status' => 'active',
    ]);

    $billing = Billing::create([
        'table_session_id' => $session->id,
        'minimum_charge' => 0,
        'orders_total' => 50000,
        'subtotal' => 50000,
        'tax' => 0,
        'tax_percentage' => 11,
        'service_charge' => 0,
        'service_charge_percentage' => 10,
        'discount_amount' => 60000,
        'grand_total' => 0,
        'paid_amount' => 0,
        'billing_status' => 'draft',
    ]);

    $session->update(['billing_id' => $billing->id]);

    Order::create([
        'table_session_id' => $session->id,
        'created_by' => $waiter->id,
        'order_number' => 'ORD-'.uniqid(),
        'status' => 'pending',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    actingAs($waiter)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('waiter.active-tables'))
        ->assertSuccessful()
        ->assertViewIs('waiter.active-tables')
        ->assertSee('Rp 50.000')
        ->assertSee('Diskon')
        ->assertSeeInOrder(['Estimasi Total', 'Rp 1.050']);
});
