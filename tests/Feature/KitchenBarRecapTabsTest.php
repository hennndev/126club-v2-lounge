<?php

use App\Models\DailyBarSnapshot;
use App\Models\DailyKitchenSnapshot;
use App\Models\Dashboard;
use App\Models\EndayBarItem;
use App\Models\EndayKitchenItem;
use App\Models\InventoryItem;
use App\Models\Printer;
use App\Models\RecapHistoryBar;
use App\Models\RecapHistoryKitchen;
use App\Services\PrinterService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

test('kitchen page shows end day and history tabs with recap snapshot data', function () {
    $admin = adminUser();

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 0,
            'total_tax' => 0,
            'total_service_charge' => 0,
            'total_cash' => 0,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 11,
            'total_bar_items' => 7,
            'total_transactions' => 0,
            'last_synced_at' => now(),
        ]
    );

    $inventoryItem = InventoryItem::query()->create([
        'code' => 'K-HISTORY-ITEM',
        'accurate_id' => 901001,
        'name' => 'Nasi Goreng',
        'category_type' => 'food',
        'price' => 25000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $history = RecapHistoryKitchen::query()->create([
        'end_day' => now()->subDay()->toDateString(),
        'total_items' => 9,
        'last_synced_at' => now()->subDay()->setTime(23, 55),
    ]);

    EndayKitchenItem::query()->create([
        'recap_history_kitchen_id' => $history->id,
        'end_day' => now()->subDay()->toDateString(),
        'inventory_item_id' => $inventoryItem->id,
        'quantity' => 4,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.kitchen.index'))
        ->assertSuccessful()
        ->assertSeeText('Order')
        ->assertSeeText('End Day')
        ->assertSeeText('History')
        ->assertSeeText('End Day Kitchen')
        ->assertSeeText('History End Day Kitchen')
        ->assertSeeText('Klik baris history untuk melihat detail item dan quantity.')
        ->assertSeeText('Detail Item Kitchen')
        ->assertSeeText('Nasi Goreng')
        ->assertSeeText('0')
        ->assertSeeText('9');
});

test('bar page shows end day and history tabs with recap snapshot data', function () {
    $admin = adminUser();

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 0,
            'total_tax' => 0,
            'total_service_charge' => 0,
            'total_cash' => 0,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 4,
            'total_bar_items' => 13,
            'total_transactions' => 0,
            'last_synced_at' => now(),
        ]
    );

    $inventoryItem = InventoryItem::query()->create([
        'code' => 'B-HISTORY-ITEM',
        'accurate_id' => 902001,
        'name' => 'Mojito',
        'category_type' => 'beverage',
        'price' => 35000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'gelas',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $history = RecapHistoryBar::query()->create([
        'end_day' => now()->subDay()->toDateString(),
        'total_items' => 10,
        'last_synced_at' => now()->subDay()->setTime(23, 50),
    ]);

    EndayBarItem::query()->create([
        'recap_history_bar_id' => $history->id,
        'end_day' => now()->subDay()->toDateString(),
        'inventory_item_id' => $inventoryItem->id,
        'quantity' => 5,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.bar.index'))
        ->assertSuccessful()
        ->assertSeeText('Order')
        ->assertSeeText('End Day')
        ->assertSeeText('History')
        ->assertSeeText('End Day Bar')
        ->assertSeeText('History End Day Bar')
        ->assertSeeText('Klik baris history untuk melihat detail item dan quantity.')
        ->assertSeeText('Detail Item Bar')
        ->assertSeeText('Mojito')
        ->assertSeeText('0')
        ->assertSeeText('10');
});

test('kitchen end day submit stores recap and item detail snapshots', function () {
    $admin = adminUser();

    $kitchenPrinter = Printer::query()->create([
        'name' => 'Kitchen End Day Printer',
        'location' => 'kitchen',
        'printer_type' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 0,
            'total_tax' => 0,
            'total_service_charge' => 0,
            'total_cash' => 0,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 20,
            'total_bar_items' => 0,
            'total_transactions' => 0,
            'last_synced_at' => now(),
        ]
    );

    \App\Models\GeneralSetting::instance()->update([
        'end_day_kitchen_printer_id' => $kitchenPrinter->id,
    ]);

    mock(PrinterService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('printEndDayKitchenSummary')->twice()->andReturnTrue();
    });

    Carbon::setTestNow(Carbon::create(2026, 3, 27, 16, 0, 0, 'Asia/Jakarta'));

    $foodA = InventoryItem::query()->create([
        'code' => 'FOOD-A',
        'accurate_id' => 10001,
        'name' => 'Food A',
        'category_type' => 'food',
        'price' => 10000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $foodB = InventoryItem::query()->create([
        'code' => 'FOOD-B',
        'accurate_id' => 10002,
        'name' => 'Food B',
        'category_type' => 'food',
        'price' => 15000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $staleSnapshot = DailyKitchenSnapshot::query()->create([
        'end_day' => '2026-03-26',
        'total_items' => 99,
        'last_synced_at' => now(),
    ]);

    DB::table('daily_kitchen_items')->insert([
        'daily_kitchen_snapshot_id' => $staleSnapshot->id,
        'end_day' => '2026-03-26',
        'inventory_item_id' => $foodA->id,
        'quantity' => 99,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $activeSnapshot = DailyKitchenSnapshot::query()->create([
        'end_day' => '2026-03-27',
        'total_items' => 6,
        'last_synced_at' => now(),
    ]);

    DB::table('daily_kitchen_items')->insert([
        [
            'daily_kitchen_snapshot_id' => $activeSnapshot->id,
            'end_day' => '2026-03-27',
            'inventory_item_id' => $foodA->id,
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'daily_kitchen_snapshot_id' => $activeSnapshot->id,
            'end_day' => '2026-03-27',
            'inventory_item_id' => $foodB->id,
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->from(route('admin.kitchen.index'))
        ->post(route('admin.kitchen.end-day'))
        ->assertRedirect(route('admin.kitchen.index'))
        ->assertSessionHas('success');

    $kitchenHistory = RecapHistoryKitchen::query()
        ->whereDate('end_day', '2026-03-27')
        ->first();

    expect($kitchenHistory)->not->toBeNull();
    expect((int) $kitchenHistory->total_items)->toBe(6);

    $historyId = $kitchenHistory->id;

    $this->assertDatabaseHas('enday_kitchen_items', [
        'recap_history_kitchen_id' => $historyId,
        'end_day' => '2026-03-27',
        'inventory_item_id' => $foodA->id,
        'quantity' => 3,
    ]);

    $this->assertDatabaseHas('enday_kitchen_items', [
        'recap_history_kitchen_id' => $historyId,
        'end_day' => '2026-03-27',
        'inventory_item_id' => $foodB->id,
        'quantity' => 3,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->from(route('admin.kitchen.index'))
        ->post(route('admin.kitchen.end-day'))
        ->assertRedirect(route('admin.kitchen.index'))
        ->assertSessionHas('success');

    expect(RecapHistoryKitchen::query()->count())->toBe(1);
    expect(DB::table('enday_kitchen_items')->count())->toBe(2);
    expect(DB::table('daily_kitchen_snapshots')->count())->toBe(0);
    expect(DB::table('daily_kitchen_items')->count())->toBe(0);

    Carbon::setTestNow();
});

test('bar end day submit stores recap and item detail snapshots', function () {
    $admin = adminUser();

    $barPrinter = Printer::query()->create([
        'name' => 'Bar End Day Printer',
        'location' => 'bar',
        'printer_type' => 'bar',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 0,
            'total_tax' => 0,
            'total_service_charge' => 0,
            'total_cash' => 0,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 0,
            'total_bar_items' => 20,
            'total_transactions' => 0,
            'last_synced_at' => now(),
        ]
    );

    \App\Models\GeneralSetting::instance()->update([
        'end_day_bar_printer_id' => $barPrinter->id,
    ]);

    mock(PrinterService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('printEndDayBarSummary')->once()->andReturnTrue();
    });

    Carbon::setTestNow(Carbon::create(2026, 3, 27, 16, 0, 0, 'Asia/Jakarta'));

    $drinkA = InventoryItem::query()->create([
        'code' => 'DRINK-A',
        'accurate_id' => 20001,
        'name' => 'Drink A',
        'category_type' => 'beverage',
        'price' => 10000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'gelas',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $drinkB = InventoryItem::query()->create([
        'code' => 'DRINK-B',
        'accurate_id' => 20002,
        'name' => 'Drink B',
        'category_type' => 'beverage',
        'price' => 12000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'gelas',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $staleSnapshot = DailyBarSnapshot::query()->create([
        'end_day' => '2026-03-26',
        'total_items' => 99,
        'last_synced_at' => now(),
    ]);

    DB::table('daily_bar_items')->insert([
        'daily_bar_snapshot_id' => $staleSnapshot->id,
        'end_day' => '2026-03-26',
        'inventory_item_id' => $drinkA->id,
        'quantity' => 99,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $activeSnapshot = DailyBarSnapshot::query()->create([
        'end_day' => '2026-03-27',
        'total_items' => 6,
        'last_synced_at' => now(),
    ]);

    DB::table('daily_bar_items')->insert([
        [
            'daily_bar_snapshot_id' => $activeSnapshot->id,
            'end_day' => '2026-03-27',
            'inventory_item_id' => $drinkA->id,
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'daily_bar_snapshot_id' => $activeSnapshot->id,
            'end_day' => '2026-03-27',
            'inventory_item_id' => $drinkB->id,
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->from(route('admin.bar.index'))
        ->post(route('admin.bar.end-day'))
        ->assertRedirect(route('admin.bar.index'))
        ->assertSessionHas('success');

    $barHistory = RecapHistoryBar::query()
        ->whereDate('end_day', '2026-03-27')
        ->first();

    expect($barHistory)->not->toBeNull();
    expect((int) $barHistory->total_items)->toBe(6);

    $historyId = $barHistory->id;

    $this->assertDatabaseHas('enday_bar_items', [
        'recap_history_bar_id' => $historyId,
        'end_day' => '2026-03-27',
        'inventory_item_id' => $drinkA->id,
        'quantity' => 3,
    ]);

    $this->assertDatabaseHas('enday_bar_items', [
        'recap_history_bar_id' => $historyId,
        'end_day' => '2026-03-27',
        'inventory_item_id' => $drinkB->id,
        'quantity' => 3,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->from(route('admin.bar.index'))
        ->post(route('admin.bar.end-day'))
        ->assertRedirect(route('admin.bar.index'))
        ->assertSessionHas('error');

    expect(RecapHistoryBar::query()->count())->toBe(1);
    expect(DB::table('daily_bar_snapshots')->count())->toBe(0);
    expect(DB::table('enday_bar_items')->count())->toBe(2);
    expect(DB::table('daily_bar_items')->count())->toBe(0);

    Carbon::setTestNow();
});

test('kitchen end day history can be reprinted', function () {
    $admin = adminUser();

    $kitchenPrinter = Printer::query()->create([
        'name' => 'Kitchen Reprint Printer',
        'location' => 'kitchen',
        'printer_type' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    \App\Models\GeneralSetting::instance()->update([
        'end_day_kitchen_printer_id' => $kitchenPrinter->id,
    ]);

    $inventoryItem = InventoryItem::query()->create([
        'code' => 'K-REPRINT-ITEM',
        'accurate_id' => 930001,
        'name' => 'Ayam Bakar',
        'category_type' => 'food',
        'price' => 30000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $history = RecapHistoryKitchen::query()->create([
        'end_day' => now()->toDateString(),
        'total_items' => 2,
        'last_synced_at' => now(),
    ]);

    EndayKitchenItem::query()->create([
        'recap_history_kitchen_id' => $history->id,
        'end_day' => now()->toDateString(),
        'inventory_item_id' => $inventoryItem->id,
        'quantity' => 2,
    ]);

    mock(PrinterService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('printEndDayKitchenSummary')->once()->andReturnTrue();
    });

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.kitchen.end-day.reprint', $history))
        ->assertSuccessful()
        ->assertJsonPath('success', true);
});

test('bar end day history can be reprinted', function () {
    $admin = adminUser();

    $barPrinter = Printer::query()->create([
        'name' => 'Bar Reprint Printer',
        'location' => 'bar',
        'printer_type' => 'bar',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    \App\Models\GeneralSetting::instance()->update([
        'end_day_bar_printer_id' => $barPrinter->id,
    ]);

    $inventoryItem = InventoryItem::query()->create([
        'code' => 'B-REPRINT-ITEM',
        'accurate_id' => 930002,
        'name' => 'Blue Ocean',
        'category_type' => 'beverage',
        'price' => 35000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'gelas',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $history = RecapHistoryBar::query()->create([
        'end_day' => now()->toDateString(),
        'total_items' => 3,
        'last_synced_at' => now(),
    ]);

    EndayBarItem::query()->create([
        'recap_history_bar_id' => $history->id,
        'end_day' => now()->toDateString(),
        'inventory_item_id' => $inventoryItem->id,
        'quantity' => 3,
    ]);

    mock(PrinterService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('printEndDayBarSummary')->once()->andReturnTrue();
    });

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.bar.end-day.reprint', $history))
        ->assertSuccessful()
        ->assertJsonPath('success', true);
});

test('kitchen end day history preview page shows item details', function () {
    $admin = adminUser();

    $inventoryItem = InventoryItem::query()->create([
        'code' => 'K-PREVIEW-ITEM',
        'accurate_id' => 930101,
        'name' => 'Sop Buntut',
        'category_type' => 'food',
        'price' => 40000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $history = RecapHistoryKitchen::query()->create([
        'end_day' => now()->toDateString(),
        'total_items' => 2,
        'last_synced_at' => now(),
    ]);

    EndayKitchenItem::query()->create([
        'recap_history_kitchen_id' => $history->id,
        'end_day' => now()->toDateString(),
        'inventory_item_id' => $inventoryItem->id,
        'quantity' => 2,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.kitchen.end-day.preview', $history))
        ->assertSuccessful()
        ->assertSeeText('Preview Print Struk - End Day Kitchen')
        ->assertSeeText('Reprint Sekarang')
        ->assertSeeText('Sop Buntut')
        ->assertSeeText('2');
});

test('bar end day history preview page shows item details', function () {
    $admin = adminUser();

    $inventoryItem = InventoryItem::query()->create([
        'code' => 'B-PREVIEW-ITEM',
        'accurate_id' => 930102,
        'name' => 'Lemon Tea',
        'category_type' => 'beverage',
        'price' => 20000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'gelas',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $history = RecapHistoryBar::query()->create([
        'end_day' => now()->toDateString(),
        'total_items' => 3,
        'last_synced_at' => now(),
    ]);

    EndayBarItem::query()->create([
        'recap_history_bar_id' => $history->id,
        'end_day' => now()->toDateString(),
        'inventory_item_id' => $inventoryItem->id,
        'quantity' => 3,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.bar.end-day.preview', $history))
        ->assertSuccessful()
        ->assertSeeText('Preview Print Struk - End Day Bar')
        ->assertSeeText('Reprint Sekarang')
        ->assertSeeText('Lemon Tea')
        ->assertSeeText('3');
});

test('kitchen end day tab shows data from daily snapshot parent-child', function () {
    $admin = adminUser();

    $todayEndDay = now('Asia/Jakarta')->toDateString();

    $item = InventoryItem::query()->create([
        'code' => 'K-SNAPSHOT-IN',
        'accurate_id' => 940101,
        'name' => 'Kitchen Snapshot Item',
        'category_type' => 'food',
        'price' => 12000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $snapshot = DailyKitchenSnapshot::query()->create([
        'end_day' => $todayEndDay,
        'total_items' => 2,
        'last_synced_at' => now(),
    ]);

    DB::table('daily_kitchen_items')->insert([
        'daily_kitchen_snapshot_id' => $snapshot->id,
        'end_day' => $todayEndDay,
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.kitchen.index'))
        ->assertSuccessful()
        ->assertSeeText('2');
});

test('bar end day tab shows data from daily snapshot parent-child', function () {
    $admin = adminUser();

    $todayEndDay = now('Asia/Jakarta')->toDateString();

    $item = InventoryItem::query()->create([
        'code' => 'B-SNAPSHOT-IN',
        'accurate_id' => 950101,
        'name' => 'Bar Snapshot Item',
        'category_type' => 'beverage',
        'price' => 14000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'gelas',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $snapshot = DailyBarSnapshot::query()->create([
        'end_day' => $todayEndDay,
        'total_items' => 3,
        'last_synced_at' => now(),
    ]);

    DB::table('daily_bar_items')->insert([
        'daily_bar_snapshot_id' => $snapshot->id,
        'end_day' => $todayEndDay,
        'inventory_item_id' => $item->id,
        'quantity' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.bar.index'))
        ->assertSuccessful()
        ->assertSeeText('3');
});

test('kitchen sync snapshot endpoint rebuilds snapshot from new kitchen orders', function () {
    $admin = adminUser();

    Carbon::setTestNow(Carbon::create(2026, 3, 27, 16, 0, 0, 'Asia/Jakarta'));

    $item = InventoryItem::query()->create([
        'code' => 'K-SYNC-ITEM',
        'accurate_id' => 970001,
        'name' => 'Kitchen Sync Item',
        'category_type' => 'food',
        'price' => 14000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $order = \App\Models\KitchenOrder::query()->create([
        'order_id' => null,
        'order_number' => 'K-SYNC-001',
        'customer_user_id' => null,
        'table_id' => null,
        'total_amount' => 28000,
        'payment_method' => 'cash',
        'status' => 'selesai',
        'progress' => 100,
    ]);

    \App\Models\KitchenOrderItem::query()->create([
        'kitchen_order_id' => $order->id,
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'price' => 14000,
        'is_completed' => true,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->post(route('admin.kitchen.end-day.sync-snapshot'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DB::table('daily_kitchen_snapshots')->count())->toBe(1);
    expect(DB::table('daily_kitchen_items')->count())->toBe(1);

    Carbon::setTestNow();
});

test('bar sync snapshot endpoint rebuilds snapshot from new bar orders', function () {
    $admin = adminUser();

    Carbon::setTestNow(Carbon::create(2026, 3, 27, 16, 0, 0, 'Asia/Jakarta'));

    $item = InventoryItem::query()->create([
        'code' => 'B-SYNC-ITEM',
        'accurate_id' => 970002,
        'name' => 'Bar Sync Item',
        'category_type' => 'beverage',
        'price' => 15000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'gelas',
        'is_active' => true,
        'item_produced' => false,
        'material_produced' => false,
    ]);

    $order = \App\Models\BarOrder::query()->create([
        'order_id' => null,
        'order_number' => 'B-SYNC-001',
        'customer_user_id' => null,
        'table_id' => null,
        'total_amount' => 30000,
        'payment_method' => 'cash',
        'status' => 'selesai',
        'progress' => 100,
    ]);

    \App\Models\BarOrderItem::query()->create([
        'bar_order_id' => $order->id,
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'price' => 15000,
        'is_completed' => true,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->post(route('admin.bar.end-day.sync-snapshot'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DB::table('daily_bar_snapshots')->count())->toBe(1);
    expect(DB::table('daily_bar_items')->count())->toBe(1);

    Carbon::setTestNow();
});
