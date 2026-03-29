<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\CustomerUser;
use App\Models\Order;
use App\Models\Tabel;
use App\Models\TableSession;
use App\Models\User;
use App\Models\UserProfile;

function createCustomerForLeaderboard(array $attributes): CustomerUser
{
    $user = User::factory()->create([
        'name' => $attributes['name'],
        'email' => $attributes['email'],
    ]);

    $profile = UserProfile::create([
        'user_id' => $user->id,
        'phone' => $attributes['phone'] ?? null,
    ]);

    return CustomerUser::create([
        'accurate_id' => $attributes['accurate_id'],
        'customer_code' => $attributes['customer_code'],
        'user_id' => $user->id,
        'user_profile_id' => $profile->id,
        'total_visits' => $attributes['total_visits'],
        'lifetime_spending' => $attributes['lifetime_spending'],
    ]);
}

function createWalkInBillingHistory(CustomerUser $customer, float $grandTotal, string $status = 'paid'): void
{
    $cashier = User::factory()->create();

    $order = Order::create([
        'table_session_id' => null,
        'customer_user_id' => $customer->id,
        'created_by' => $cashier->id,
        'order_number' => 'ORD-WALKIN-'.uniqid(),
        'status' => 'pending',
        'items_total' => $grandTotal,
        'discount_amount' => 0,
        'total' => $grandTotal,
        'ordered_at' => now(),
    ]);

    Billing::create([
        'table_session_id' => null,
        'order_id' => $order->id,
        'is_walk_in' => true,
        'is_booking' => false,
        'minimum_charge' => 0,
        'orders_total' => $grandTotal,
        'subtotal' => $grandTotal,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => $grandTotal,
        'paid_amount' => $grandTotal,
        'billing_status' => $status,
        'transaction_code' => 'WALKIN-'.uniqid(),
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);
}

function createWalkInTransactionOnly(CustomerUser $customer, float $grandTotal, string $status = 'pending'): void
{
    $cashier = User::factory()->create();

    Order::create([
        'table_session_id' => null,
        'customer_user_id' => $customer->id,
        'created_by' => $cashier->id,
        'order_number' => 'ORD-WALKIN-TX-'.uniqid(),
        'status' => $status,
        'items_total' => $grandTotal,
        'discount_amount' => 0,
        'total' => $grandTotal,
        'ordered_at' => now(),
    ]);
}

function createBookingBillingHistory(CustomerUser $customer, float $grandTotal, string $status = 'paid'): void
{
    $area = Area::create([
        'code' => 'AREA-'.uniqid(),
        'name' => 'Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'TB-'.uniqid(),
        'qr_code' => 'QR-'.uniqid(),
        'capacity' => 4,
        'minimum_charge' => 0,
        'status' => 'available',
        'is_active' => true,
    ]);

    $session = TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->user_id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'completed',
    ]);

    Billing::create([
        'table_session_id' => $session->id,
        'order_id' => null,
        'is_walk_in' => false,
        'is_booking' => true,
        'minimum_charge' => 0,
        'orders_total' => $grandTotal,
        'subtotal' => $grandTotal,
        'tax' => 0,
        'tax_percentage' => 0,
        'service_charge' => 0,
        'service_charge_percentage' => 0,
        'discount_amount' => 0,
        'grand_total' => $grandTotal,
        'paid_amount' => $grandTotal,
        'billing_status' => $status,
        'transaction_code' => 'BILLING-'.uniqid(),
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);
}

test('admin customers leaderboard is sorted by leaderboard score', function () {
    $admin = adminUser();

    $alice = createCustomerForLeaderboard([
        'name' => 'Alice Score',
        'email' => 'alice.score@example.com',
        'accurate_id' => 991001,
        'customer_code' => 'CUST-991001',
        'total_visits' => 99,
        'lifetime_spending' => 999999,
    ]);

    $bravo = createCustomerForLeaderboard([
        'name' => 'Bravo Score',
        'email' => 'bravo.score@example.com',
        'accurate_id' => 991002,
        'customer_code' => 'CUST-991002',
        'total_visits' => 0,
        'lifetime_spending' => 0,
    ]);

    $charlie = createCustomerForLeaderboard([
        'name' => 'Charlie Score',
        'email' => 'charlie.score@example.com',
        'accurate_id' => 991003,
        'customer_code' => 'CUST-991003',
        'total_visits' => 0,
        'lifetime_spending' => 0,
    ]);

    createWalkInTransactionOnly($charlie, 120000);
    createWalkInTransactionOnly($bravo, 30000);
    createBookingBillingHistory($bravo, 20000);
    createBookingBillingHistory($alice, 450000, 'draft');

    $response = $this->actingAs($admin)
        ->get(route('admin.customers.index'));

    $response->assertOk();

    $response->assertViewHas('leaderboard', function ($leaderboard) use ($alice, $bravo, $charlie) {
        $orderedIds = $leaderboard->pluck('id')->values()->all();

        if ($orderedIds !== [$charlie->id, $bravo->id, $alice->id]) {
            return false;
        }

        $topScore = (int) ($leaderboard->first()->leaderboard_score ?? 0);

        return $topScore === 13;
    });

    $response->assertSeeInOrder([
        'Charlie Score',
        'Bravo Score',
        'Alice Score',
    ]);

    $response->assertSee('Rp 120.000');
    $response->assertSee('Rp 50.000');
    $response->assertSee('Rp 0');
    $response->assertDontSee('Points');
    $response->assertDontSee(' points');
});

test('admin customers leaderboard limit can be configured via query parameter', function () {
    $admin = adminUser();

    for ($index = 1; $index <= 25; $index++) {
        $customer = createCustomerForLeaderboard([
            'name' => "Leaderboard Customer {$index}",
            'email' => "leaderboard-customer-{$index}@example.com",
            'accurate_id' => 992000 + $index,
            'customer_code' => 'CUST-'.(992000 + $index),
            'total_visits' => 0,
            'lifetime_spending' => 0,
        ]);

        createWalkInTransactionOnly($customer, $index * 1000);
    }

    $response = $this->actingAs($admin)
        ->get(route('admin.customers.index', [
            'tab' => 'leaderboard',
            'leaderboard_limit' => 20,
        ]));

    $response->assertOk();
    $response->assertViewHas('leaderboardLimit', 20);
    $response->assertViewHas('leaderboard', fn ($leaderboard) => $leaderboard->count() === 20);
    $response->assertSee('Top 20 Spenders');
});

test('admin customers leaderboard uses default limit when query value is invalid', function () {
    $admin = adminUser();

    for ($index = 1; $index <= 25; $index++) {
        $customer = createCustomerForLeaderboard([
            'name' => "Fallback Customer {$index}",
            'email' => "fallback-customer-{$index}@example.com",
            'accurate_id' => 993000 + $index,
            'customer_code' => 'CUST-'.(993000 + $index),
            'total_visits' => 0,
            'lifetime_spending' => 0,
        ]);

        createWalkInTransactionOnly($customer, $index * 1000);
    }

    $response = $this->actingAs($admin)
        ->get(route('admin.customers.index', [
            'tab' => 'leaderboard',
            'leaderboard_limit' => 15,
        ]));

    $response->assertOk();
    $response->assertViewHas('leaderboardLimit', 10);
    $response->assertViewHas('leaderboard', fn ($leaderboard) => $leaderboard->count() === 10);
    $response->assertSee('Top 10 Spenders');
});
