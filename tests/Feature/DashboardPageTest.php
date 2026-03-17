<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\Dashboard;
use App\Models\Tabel;
use App\Models\TableSession;

use function Pest\Laravel\actingAs;

test('dashboard page shows aggregated transaction metrics from dashboard table', function () {
    $admin = adminUser();

    Dashboard::query()->create([
        'total_amount' => 500000,
        'total_tax' => 15000,
        'total_service_charge' => 12000,
        'total_cash' => 100000,
        'total_transfer' => 120000,
        'total_debit' => 90000,
        'total_kredit' => 80000,
        'total_qris' => 110000,
        'total_kitchen_items' => 25,
        'total_bar_items' => 30,
        'total_transactions' => 10,
        'last_synced_at' => now(),
    ]);

    actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Ringkasan Transaksi Dashboard')
        ->assertSeeText('Total Pajak')
        ->assertSeeText('Total Service Charge')
        ->assertSeeText('Total Pembayaran Tunai')
        ->assertSeeText('Total Pembayaran Transfer')
        ->assertSeeText('Total Pembayaran Debit')
        ->assertSeeText('Total Pembayaran Kredit')
        ->assertSeeText('Total Pembayaran QRIS')
        ->assertSeeText('Total Item Keluar Kitchen')
        ->assertSeeText('Total Item Keluar Bar')
        ->assertSeeText('Rp 15.000')
        ->assertSeeText('Rp 12.000')
        ->assertSeeText('Rp 100.000')
        ->assertSeeText('Rp 120.000')
        ->assertSeeText('Rp 90.000')
        ->assertSeeText('Rp 80.000')
        ->assertSeeText('Rp 110.000')
        ->assertSeeText('25')
        ->assertSeeText('30')
        ->assertSeeText('Sync Dashboard Hari Ini');
});

test('dashboard sync button triggers today sync and redirects back', function () {
    $admin = adminUser();

    $area = Area::create([
        'code' => 'DPG-AREA-'.uniqid(),
        'name' => 'Dashboard Page Area '.uniqid(),
        'is_active' => true,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'DPG-TBL-'.uniqid(),
        'qr_code' => 'DPG-QR-'.uniqid(),
        'capacity' => 4,
        'status' => 'available',
        'is_active' => true,
    ]);

    $session = TableSession::create([
        'table_reservation_id' => null,
        'table_id' => $table->id,
        'customer_id' => $admin->id,
        'session_code' => 'DPG-SES-'.uniqid(),
        'status' => 'active',
        'billing_id' => null,
    ]);

    Billing::create([
        'table_session_id' => $session->id,
        'minimum_charge' => 0,
        'orders_total' => 20000,
        'subtotal' => 20000,
        'tax' => 2200,
        'tax_percentage' => 11,
        'service_charge' => 2000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 24200,
        'paid_amount' => 24200,
        'billing_status' => 'paid',
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);

    actingAs($admin)
        ->post(route('admin.dashboard.sync'))
        ->assertRedirect(route('admin.dashboard'));

    $dashboard = Dashboard::query()->findOrFail(1);

    expect((float) $dashboard->total_amount)->toBe(24200.0)
        ->and((int) $dashboard->total_transactions)->toBe(1);
});
