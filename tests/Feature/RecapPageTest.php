<?php

use App\Models\Area;
use App\Models\BarOrder;
use App\Models\BarOrderItem;
use App\Models\Billing;
use App\Models\Dashboard;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RecapHistory;
use App\Models\Tabel;
use App\Models\TableSession;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

function makeRecapInventoryItem(array $attributes = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'code' => 'RCP-ITEM-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Recap Item '.uniqid(),
        'category_type' => 'food',
        'price' => 15000,
        'stock_quantity' => 100,
        'threshold' => 5,
        'unit' => 'unit',
        'is_active' => true,
    ], $attributes));
}

function makeRecapOrder(int $createdById, \Illuminate\Support\Carbon $orderedAt, string $orderNumber, array $attributes = []): Order
{
    return Order::create(array_merge([
        'table_session_id' => null,
        'customer_user_id' => null,
        'created_by' => $createdById,
        'order_number' => $orderNumber,
        'status' => 'completed',
        'items_total' => 30000,
        'discount_amount' => 0,
        'total' => 30000,
        'ordered_at' => $orderedAt,
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ], $attributes));
}

function makeRecapTableSessionWithBilling(int $customerId, array $billingAttributes = []): TableSession
{
    $area = Area::create([
        'code' => 'RCP-AREA-'.uniqid(),
        'name' => 'Recap Area '.uniqid(),
        'is_active' => true,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'RCP-TBL-'.uniqid(),
        'qr_code' => 'RCP-QR-'.uniqid(),
        'capacity' => 4,
        'status' => 'available',
        'is_active' => true,
    ]);

    $tableSession = TableSession::create([
        'table_reservation_id' => null,
        'table_id' => $table->id,
        'customer_id' => $customerId,
        'session_code' => 'RCP-SES-'.uniqid(),
        'status' => 'active',
    ]);

    $billing = Billing::create(array_merge([
        'table_session_id' => $tableSession->id,
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
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ], $billingAttributes));

    $tableSession->update([
        'billing_id' => $billing->id,
    ]);

    return $tableSession->fresh();
}

function makeRecapTableSessionWithoutBillingLink(int $customerId): TableSession
{
    $area = Area::create([
        'code' => 'RCP-AREA-'.uniqid(),
        'name' => 'Recap Area '.uniqid(),
        'is_active' => true,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'RCP-TBL-'.uniqid(),
        'qr_code' => 'RCP-QR-'.uniqid(),
        'capacity' => 4,
        'status' => 'available',
        'is_active' => true,
    ]);

    return TableSession::create([
        'table_reservation_id' => null,
        'table_id' => $table->id,
        'customer_id' => $customerId,
        'session_code' => 'RCP-SES-'.uniqid(),
        'status' => 'active',
        'billing_id' => null,
    ]);
}

test('admin can open recap page', function () {
    $admin = adminUser();
    $start = now()->startOfDay()->addHours(8);
    $end = now()->startOfDay()->addHours(23)->addMinutes(59);

    Dashboard::query()->create([
        'total_amount' => 500000,
        'total_tax' => 15000,
        'total_service_charge' => 12000,
        'total_cash' => 100000,
        'total_transfer' => 120000,
        'total_debit' => 90000,
        'total_kredit' => 80000,
        'total_qris' => 110000,
        'total_transactions' => 10,
        'last_synced_at' => now(),
    ]);

    actingAs($admin)
        ->get(route('admin.recap.index', [
            'start_datetime' => $start->format('Y-m-d\TH:i'),
            'end_datetime' => $end->format('Y-m-d\TH:i'),
        ]))
        ->assertSuccessful()
        ->assertViewIs('recap.index')
        ->assertSeeText('Rekap End Day')
        ->assertSeeText('Recap')
        ->assertSeeText('History')
        ->assertSeeText('Export Excel (.xlsx)')
        ->assertSeeText('Transaksi Kasir')
        ->assertSeeText('Metode Pembayaran')
        ->assertSeeText('Total Pembayaran Tunai')
        ->assertSeeText('Total Tunai')
        ->assertSeeText('Rp 100.000')
        ->assertSeeText('Item Keluar Kitchen')
        ->assertSeeText('Item Keluar Bar')
        ->assertSee(route('admin.recap.close-export'))
        ->assertSeeText('Konfirmasi End Day')
        ->assertDontSeeText('Filter Rekapan')
        ->assertDontSeeText('Timeline Kejadian');
});

test('recap page filters cashier kitchen and bar events by selected datetime range', function () {
    $admin = adminUser();

    $today = now()->startOfDay()->addHours(10);
    $yesterday = now()->subDay()->startOfDay()->addHours(11);
    $rangeStart = now()->startOfDay()->addHours(9);
    $rangeEnd = now()->startOfDay()->addHours(23);

    $todayOrder = makeRecapOrder($admin->id, $today, 'RCP-TODAY-001');
    $yesterdayOrder = makeRecapOrder($admin->id, $yesterday, 'RCP-YEST-001');

    $foodToday = makeRecapInventoryItem([
        'name' => 'Nasi Goreng Recap',
        'category_type' => 'food',
    ]);
    $foodYesterday = makeRecapInventoryItem([
        'name' => 'Mie Goreng Lama',
        'category_type' => 'food',
    ]);
    $drinkToday = makeRecapInventoryItem([
        'name' => 'Es Teh Recap',
        'category_type' => 'beverage',
    ]);
    $drinkYesterday = makeRecapInventoryItem([
        'name' => 'Jus Lama',
        'category_type' => 'beverage',
    ]);

    OrderItem::create([
        'order_id' => $todayOrder->id,
        'inventory_item_id' => $foodToday->id,
        'item_name' => $foodToday->name,
        'item_code' => $foodToday->code,
        'quantity' => 2,
        'price' => 15000,
        'subtotal' => 30000,
        'discount_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'served',
    ]);

    $kitchenOrderToday = KitchenOrder::create([
        'order_id' => $todayOrder->id,
        'order_number' => $todayOrder->order_number,
        'customer_user_id' => null,
        'table_id' => null,
        'total_amount' => 15000,
        'status' => 'selesai',
        'progress' => 100,
    ]);
    $kitchenOrderToday->forceFill(['created_at' => $today, 'updated_at' => $today])->save();

    KitchenOrderItem::create([
        'kitchen_order_id' => $kitchenOrderToday->id,
        'inventory_item_id' => $foodToday->id,
        'quantity' => 1,
        'price' => 15000,
        'is_completed' => true,
    ]);

    $kitchenOrderYesterday = KitchenOrder::create([
        'order_id' => $yesterdayOrder->id,
        'order_number' => $yesterdayOrder->order_number,
        'customer_user_id' => null,
        'table_id' => null,
        'total_amount' => 15000,
        'status' => 'selesai',
        'progress' => 100,
    ]);
    $kitchenOrderYesterday->forceFill(['created_at' => $yesterday, 'updated_at' => $yesterday])->save();

    KitchenOrderItem::create([
        'kitchen_order_id' => $kitchenOrderYesterday->id,
        'inventory_item_id' => $foodYesterday->id,
        'quantity' => 1,
        'price' => 15000,
        'is_completed' => true,
    ]);

    $barOrderToday = BarOrder::create([
        'order_id' => $todayOrder->id,
        'order_number' => $todayOrder->order_number,
        'customer_user_id' => null,
        'table_id' => null,
        'total_amount' => 15000,
        'payment_method' => 'cash',
        'status' => 'selesai',
        'progress' => 100,
    ]);
    $barOrderToday->forceFill(['created_at' => $today, 'updated_at' => $today])->save();

    BarOrderItem::create([
        'bar_order_id' => $barOrderToday->id,
        'inventory_item_id' => $drinkToday->id,
        'quantity' => 1,
        'price' => 15000,
        'is_completed' => true,
    ]);

    $barOrderYesterday = BarOrder::create([
        'order_id' => $yesterdayOrder->id,
        'order_number' => $yesterdayOrder->order_number,
        'customer_user_id' => null,
        'table_id' => null,
        'total_amount' => 15000,
        'payment_method' => 'cash',
        'status' => 'selesai',
        'progress' => 100,
    ]);
    $barOrderYesterday->forceFill(['created_at' => $yesterday, 'updated_at' => $yesterday])->save();

    BarOrderItem::create([
        'bar_order_id' => $barOrderYesterday->id,
        'inventory_item_id' => $drinkYesterday->id,
        'quantity' => 1,
        'price' => 15000,
        'is_completed' => true,
    ]);

    actingAs($admin)
        ->get(route('admin.recap.index', [
            'start_datetime' => $rangeStart->format('Y-m-d\TH:i'),
            'end_datetime' => $rangeEnd->format('Y-m-d\TH:i'),
        ]))
        ->assertSuccessful()
        ->assertSee('RCP-TODAY-001')
        ->assertSee($today->format('d/m/Y H:i'))
        ->assertDontSee('RCP-YEST-001')
        ->assertSee('Nasi Goreng Recap')
        ->assertDontSee('Mie Goreng Lama')
        ->assertSee('Es Teh Recap')
        ->assertDontSee('Jus Lama')
        ->assertSee('Tunai')
        ->assertSee('Rp 30.000');
});

test('recap page shows total tax total service charge and payment method totals', function () {
    $admin = adminUser();
    $rangeStart = now()->startOfDay()->addHours(8);
    $rangeEnd = now()->startOfDay()->addHours(23);
    $orderedAt = now()->startOfDay()->addHours(12);

    $sessionTransfer = makeRecapTableSessionWithBilling($admin->id, [
        'tax' => 3000,
        'service_charge' => 2000,
        'payment_method' => 'transfer',
        'paid_amount' => 50000,
        'grand_total' => 50000,
    ]);
    makeRecapOrder($admin->id, $orderedAt, 'RCP-PAY-TRF', [
        'table_session_id' => $sessionTransfer->id,
        'items_total' => 50000,
        'total' => 50000,
        'payment_method' => 'transfer',
    ]);

    $sessionDebit = makeRecapTableSessionWithBilling($admin->id, [
        'tax' => 2000,
        'service_charge' => 1500,
        'payment_method' => 'debit',
        'paid_amount' => 40000,
        'grand_total' => 40000,
    ]);
    makeRecapOrder($admin->id, $orderedAt, 'RCP-PAY-DBT', [
        'table_session_id' => $sessionDebit->id,
        'items_total' => 40000,
        'total' => 40000,
        'payment_method' => 'debit',
    ]);

    $sessionCredit = makeRecapTableSessionWithBilling($admin->id, [
        'tax' => 1000,
        'service_charge' => 1000,
        'payment_method' => 'kredit',
        'paid_amount' => 30000,
        'grand_total' => 30000,
    ]);
    makeRecapOrder($admin->id, $orderedAt, 'RCP-PAY-KRD', [
        'table_session_id' => $sessionCredit->id,
        'items_total' => 30000,
        'total' => 30000,
        'payment_method' => 'kredit',
    ]);

    $sessionQris = makeRecapTableSessionWithBilling($admin->id, [
        'tax' => 500,
        'service_charge' => 500,
        'payment_method' => 'qris',
        'paid_amount' => 20000,
        'grand_total' => 20000,
    ]);
    makeRecapOrder($admin->id, $orderedAt, 'RCP-PAY-QRS', [
        'table_session_id' => $sessionQris->id,
        'items_total' => 20000,
        'total' => 20000,
        'payment_method' => 'qris',
    ]);

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 140000,
            'total_tax' => 6500,
            'total_service_charge' => 5000,
            'total_transfer' => 50000,
            'total_debit' => 40000,
            'total_kredit' => 30000,
            'total_qris' => 20000,
            'total_cash' => 0,
            'total_transactions' => 4,
            'last_synced_at' => now(),
        ]
    );

    actingAs($admin)
        ->get(route('admin.recap.index', [
            'start_datetime' => $rangeStart->format('Y-m-d\TH:i'),
            'end_datetime' => $rangeEnd->format('Y-m-d\TH:i'),
        ]))
        ->assertSuccessful()
        ->assertSeeText('Total Pajak')
        ->assertSeeText('Total Service Charge')
        ->assertSeeText('Total Pembayaran Transfer')
        ->assertSeeText('Total Pembayaran Debit')
        ->assertSeeText('Total Pembayaran Kredit')
        ->assertSeeText('Total Pembayaran QRIS')
        ->assertSeeText('Rp 6.500')
        ->assertSeeText('Rp 5.000')
        ->assertSeeText('Rp 50.000')
        ->assertSeeText('Rp 40.000')
        ->assertSeeText('Rp 30.000')
        ->assertSeeText('Rp 20.000');
});

test('recap includes booking billing by table_session_id and walk-in calculated tax service', function () {
    $admin = adminUser();
    $rangeStart = now()->startOfDay()->addHours(8);
    $rangeEnd = now()->startOfDay()->addHours(23);
    $orderedAt = now()->startOfDay()->addHours(12);

    $settings = \App\Models\GeneralSetting::instance();
    $settings->update([
        'tax_percentage' => 10,
        'service_charge_percentage' => 10,
    ]);

    $bookingSession = makeRecapTableSessionWithoutBillingLink($admin->id);
    Billing::create([
        'table_session_id' => $bookingSession->id,
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

    makeRecapOrder($admin->id, $orderedAt, 'RCP-LINKLESS-BOOKING', [
        'table_session_id' => $bookingSession->id,
        'items_total' => 50000,
        'total' => 50000,
        'payment_method' => null,
    ]);

    $walkInOrder = makeRecapOrder($admin->id, $orderedAt, 'RCP-WALKIN-001', [
        'table_session_id' => null,
        'items_total' => 100000,
        'total' => 100000,
        'payment_method' => 'debit',
        'payment_mode' => 'normal',
    ]);

    $walkInItem = makeRecapInventoryItem([
        'name' => 'Walkin Charged Item',
        'include_tax' => true,
        'include_service_charge' => true,
    ]);

    OrderItem::create([
        'order_id' => $walkInOrder->id,
        'inventory_item_id' => $walkInItem->id,
        'item_name' => $walkInItem->name,
        'item_code' => $walkInItem->code,
        'quantity' => 1,
        'price' => 100000,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'served',
    ]);

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 150000,
            'total_tax' => 14000,
            'total_service_charge' => 12000,
            'total_transfer' => 50000,
            'total_debit' => 100000,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_cash' => 0,
            'total_transactions' => 2,
            'last_synced_at' => now(),
        ]
    );

    actingAs($admin)
        ->get(route('admin.recap.index', [
            'start_datetime' => $rangeStart->format('Y-m-d\TH:i'),
            'end_datetime' => $rangeEnd->format('Y-m-d\TH:i'),
        ]))
        ->assertSuccessful()
        ->assertSeeText('Total Pajak')
        ->assertSeeText('Total Service Charge')
        ->assertSeeText('Total Pembayaran Transfer')
        ->assertSeeText('Total Pembayaran Debit')
        ->assertSeeText('Rp 14.000')
        ->assertSeeText('Rp 12.000')
        ->assertSeeText('Rp 50.000')
        ->assertSeeText('Rp 100.000');
});

test('recap page shows dashboard preview aggregates', function () {
    $admin = adminUser();
    $start = now()->startOfDay()->addHours(8);
    $end = now()->startOfDay()->addHours(23)->addMinutes(59);

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 500000,
            'total_tax' => 15000,
            'total_service_charge' => 12000,
            'total_cash' => 100000,
            'total_transfer' => 120000,
            'total_debit' => 90000,
            'total_kredit' => 80000,
            'total_qris' => 110000,
            'total_transactions' => 10,
            'last_synced_at' => now(),
        ]
    );

    actingAs($admin)
        ->get(route('admin.recap.index', [
            'start_datetime' => $start->format('Y-m-d\TH:i'),
            'end_datetime' => $end->format('Y-m-d\TH:i'),
        ]))
        ->assertSuccessful()
        ->assertSeeText('Preview Dashboard (Akumulasi)')
        ->assertSeeText('Semua transaksi booking + walk-in')
        ->assertSeeText('Rp 15.000')
        ->assertSeeText('Rp 12.000')
        ->assertSeeText('Rp 120.000')
        ->assertSeeText('Rp 90.000')
        ->assertSeeText('Rp 80.000')
        ->assertSeeText('Rp 110.000')
        ->assertSeeText('10');
});

test('recap page shows automatic closing history list and modal content shell', function () {
    $admin = adminUser();
    $start = now()->startOfDay()->addHours(8);
    $end = now()->startOfDay()->addHours(23)->addMinutes(59);

    RecapHistory::query()->create([
        'end_day' => now()->subDay()->toDateString(),
        'total_amount' => 120000,
        'total_tax' => 12000,
        'total_service_charge' => 8000,
        'total_cash' => 50000,
        'total_transfer' => 30000,
        'total_debit' => 20000,
        'total_kredit' => 10000,
        'total_qris' => 10000,
        'total_transactions' => 4,
        'last_synced_at' => now()->subMinutes(10),
    ]);

    actingAs($admin)
        ->get(route('admin.recap.index', [
            'start_datetime' => $start->format('Y-m-d\TH:i'),
            'end_datetime' => $end->format('Y-m-d\TH:i'),
        ]))
        ->assertSuccessful()
        ->assertSeeText('History Closing')
        ->assertSeeText('List snapshot dashboard yang otomatis tersimpan setiap jam 12 malam.')
        ->assertSeeText('Detail History Closing')
        ->assertSeeText('Export History (.xlsx)')
        ->assertSeeText(now()->subDay()->format('d/m/Y'))
        ->assertSeeText('Rp 120.000')
        ->assertSeeText('Rp 12.000')
        ->assertSeeText('Rp 8.000')
        ->assertSeeText('Lihat Detail');
});

test('recap export returns native xlsx file', function () {
    $admin = adminUser();
    $start = now()->startOfDay()->addHours(8);
    $end = now()->startOfDay()->addHours(23)->addMinutes(59);

    $order = makeRecapOrder($admin->id, now(), 'RCP-EXPORT-001');
    $item = makeRecapInventoryItem(['name' => 'Export Item']);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $item->id,
        'item_name' => $item->name,
        'item_code' => $item->code,
        'quantity' => 1,
        'price' => 15000,
        'subtotal' => 15000,
        'discount_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'served',
    ]);

    $response = actingAs($admin)
        ->get(route('admin.recap.export', [
            'start_datetime' => $start->format('Y-m-d\TH:i'),
            'end_datetime' => $end->format('Y-m-d\TH:i'),
        ]));

    $response
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertHeader('content-disposition', 'attachment; filename=rekapan-'.$start->format('Ymd_Hi').'-'.$end->format('Ymd_Hi').'.xlsx');
});

test('recap history export returns native xlsx file', function () {
    $admin = adminUser();

    $history = RecapHistory::query()->create([
        'end_day' => now()->subDay()->toDateString(),
        'total_amount' => 120000,
        'total_tax' => 12000,
        'total_service_charge' => 8000,
        'total_cash' => 50000,
        'total_transfer' => 30000,
        'total_debit' => 20000,
        'total_kredit' => 10000,
        'total_qris' => 10000,
        'total_transactions' => 4,
        'last_synced_at' => now()->subMinutes(10),
    ]);

    $response = actingAs($admin)
        ->get(route('admin.recap.history.export', $history));

    $response
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertHeader('content-disposition', 'attachment; filename=rekapan-history-'.$history->end_day?->format('Ymd').'.xlsx');
});

test('user without recap permission cannot access recap route', function () {
    $user = \App\Models\User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Cashier']);
    $user->assignRole($role);

    actingAs($user)
        ->get(route('admin.recap.index'))
        ->assertForbidden();
});

test('user with recap permission can access recap route', function () {
    $user = \App\Models\User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Cashier']);
    $permission = Permission::firstOrCreate(['name' => 'admin.recap.*', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    actingAs($user)
        ->get(route('admin.recap.index'))
        ->assertSuccessful();
});
