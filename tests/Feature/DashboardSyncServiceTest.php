<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\Dashboard;
use App\Models\Tabel;
use App\Models\TableSession;
use App\Services\DashboardSyncService;
use Illuminate\Support\Facades\DB;

function makeDashboardSession(int $customerId): TableSession
{
    $area = Area::create([
        'code' => 'DSH-AREA-'.uniqid(),
        'name' => 'Dashboard Area '.uniqid(),
        'is_active' => true,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'DSH-TBL-'.uniqid(),
        'qr_code' => 'DSH-QR-'.uniqid(),
        'capacity' => 4,
        'status' => 'available',
        'is_active' => true,
    ]);

    return TableSession::create([
        'table_reservation_id' => null,
        'table_id' => $table->id,
        'customer_id' => $customerId,
        'session_code' => 'DSH-SES-'.uniqid(),
        'status' => 'active',
        'billing_id' => null,
    ]);
}

test('dashboard sync aggregates totals from paid billings and walk-in orders', function () {
    $admin = adminUser();

    $sessionTransfer = makeDashboardSession($admin->id);
    Billing::create([
        'table_session_id' => $sessionTransfer->id,
        'is_walk_in' => false,
        'is_booking' => true,
        'minimum_charge' => 0,
        'orders_total' => 50000,
        'subtotal' => 50000,
        'tax' => 3000,
        'tax_percentage' => 10,
        'service_charge' => 2000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 50000,
        'paid_amount' => 50000,
        'billing_status' => 'paid',
        'payment_method' => 'transfer',
        'payment_mode' => 'normal',
    ]);

    $sessionSplit = makeDashboardSession($admin->id);
    Billing::create([
        'table_session_id' => $sessionSplit->id,
        'is_walk_in' => false,
        'is_booking' => true,
        'minimum_charge' => 0,
        'orders_total' => 70000,
        'subtotal' => 70000,
        'tax' => 1400,
        'tax_percentage' => 10,
        'service_charge' => 700,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 70000,
        'paid_amount' => 70000,
        'billing_status' => 'paid',
        'payment_method' => null,
        'payment_mode' => 'split',
        'split_cash_amount' => 30000,
        'split_debit_amount' => 20000,
        'split_non_cash_method' => 'qris',
        'split_non_cash_reference_number' => 'SPLIT-001',
    ]);

    Billing::create([
        'table_session_id' => null,
        'order_id' => null,
        'is_walk_in' => true,
        'is_booking' => false,
        'minimum_charge' => 0,
        'orders_total' => 100000,
        'subtotal' => 100000,
        'tax' => 11000,
        'tax_percentage' => 10,
        'service_charge' => 10000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 121000,
        'paid_amount' => 121000,
        'billing_status' => 'paid',
        'payment_method' => 'debit',
        'payment_mode' => 'normal',
    ]);

    (new DashboardSyncService)->sync();

    $dashboard = Dashboard::query()->findOrFail(1);

    expect((float) $dashboard->total_amount)->toBe(241000.0)
        ->and((float) $dashboard->total_tax)->toBe(15400.0)
        ->and((float) $dashboard->total_service_charge)->toBe(12700.0)
        ->and((float) $dashboard->total_cash)->toBe(30000.0)
        ->and((float) $dashboard->total_transfer)->toBe(50000.0)
        ->and((float) $dashboard->total_debit)->toBe(121000.0)
        ->and((float) $dashboard->total_qris)->toBe(20000.0)
        ->and((float) $dashboard->total_kredit)->toBe(0.0)
        ->and((int) $dashboard->total_transactions)->toBe(3);
});

test('dashboard sync includes walk-in split orders with null payment method', function () {
    Billing::create([
        'table_session_id' => null,
        'order_id' => null,
        'is_walk_in' => true,
        'is_booking' => false,
        'minimum_charge' => 0,
        'orders_total' => 100000,
        'subtotal' => 100000,
        'tax' => 11000,
        'tax_percentage' => 10,
        'service_charge' => 10000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 121000,
        'paid_amount' => 121000,
        'billing_status' => 'paid',
        'payment_method' => null,
        'payment_mode' => 'split',
        'split_cash_amount' => 21000,
        'split_debit_amount' => 100000,
        'split_non_cash_method' => 'qris',
        'split_non_cash_reference_number' => 'WALKIN-SPLIT-001',
    ]);

    (new DashboardSyncService)->sync();

    $dashboard = Dashboard::query()->findOrFail(1);

    expect((float) $dashboard->total_amount)->toBe(121000.0)
        ->and((float) $dashboard->total_tax)->toBe(11000.0)
        ->and((float) $dashboard->total_service_charge)->toBe(10000.0)
        ->and((float) $dashboard->total_cash)->toBe(21000.0)
        ->and((float) $dashboard->total_qris)->toBe(100000.0)
        ->and((int) $dashboard->total_transactions)->toBe(1);
});

test('dashboard sync aggregates only today transactions', function () {
    $admin = adminUser();
    $today = now();
    $yesterday = now()->subDay();

    $sessionYesterday = makeDashboardSession($admin->id);
    $yesterdayBilling = Billing::create([
        'table_session_id' => $sessionYesterday->id,
        'is_walk_in' => false,
        'is_booking' => true,
        'minimum_charge' => 0,
        'orders_total' => 50000,
        'subtotal' => 50000,
        'tax' => 5000,
        'tax_percentage' => 10,
        'service_charge' => 5000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 60000,
        'paid_amount' => 60000,
        'billing_status' => 'paid',
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);

    DB::table('billings')
        ->where('id', $yesterdayBilling->id)
        ->update([
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ]);

    $sessionToday = makeDashboardSession($admin->id);
    Billing::create([
        'table_session_id' => $sessionToday->id,
        'is_walk_in' => false,
        'is_booking' => true,
        'minimum_charge' => 0,
        'orders_total' => 40000,
        'subtotal' => 40000,
        'tax' => 4000,
        'tax_percentage' => 10,
        'service_charge' => 2000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 46000,
        'paid_amount' => 46000,
        'billing_status' => 'paid',
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
        'created_at' => $today,
        'updated_at' => $today,
    ]);

    $walkInYesterdayBilling = Billing::create([
        'table_session_id' => null,
        'order_id' => null,
        'is_walk_in' => true,
        'is_booking' => false,
        'minimum_charge' => 0,
        'orders_total' => 100000,
        'subtotal' => 100000,
        'tax' => 11000,
        'tax_percentage' => 10,
        'service_charge' => 10000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 121000,
        'paid_amount' => 121000,
        'billing_status' => 'paid',
        'payment_method' => 'debit',
        'payment_mode' => 'normal',
    ]);

    DB::table('billings')
        ->where('id', $walkInYesterdayBilling->id)
        ->update([
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ]);

    Billing::create([
        'table_session_id' => null,
        'order_id' => null,
        'is_walk_in' => true,
        'is_booking' => false,
        'minimum_charge' => 0,
        'orders_total' => 100000,
        'subtotal' => 100000,
        'tax' => 11000,
        'tax_percentage' => 10,
        'service_charge' => 10000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 121000,
        'paid_amount' => 121000,
        'billing_status' => 'paid',
        'payment_method' => 'debit',
        'payment_mode' => 'normal',
    ]);

    (new DashboardSyncService)->sync();

    $dashboard = Dashboard::query()->findOrFail(1);

    expect((float) $dashboard->total_amount)->toBe(167000.0)
        ->and((float) $dashboard->total_tax)->toBe(15000.0)
        ->and((float) $dashboard->total_service_charge)->toBe(12000.0)
        ->and((float) $dashboard->total_cash)->toBe(46000.0)
        ->and((float) $dashboard->total_debit)->toBe(121000.0)
        ->and((int) $dashboard->total_transactions)->toBe(2);
});
