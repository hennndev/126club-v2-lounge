<?php

use App\Models\InventoryItem;
use App\Models\PosCategorySetting;
use App\Models\Printer;
use App\Services\AccurateService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

test('menus page uses categories marked as menu and shows grouped menu cards', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'food-menu',
        'show_in_pos' => true,
        'is_menu' => true,
        'preparation_location' => 'kitchen',
    ]);

    PosCategorySetting::create([
        'category_type' => 'raw-material',
        'show_in_pos' => true,
        'is_menu' => false,
        'preparation_location' => 'direct',
    ]);

    InventoryItem::create([
        'code' => 'MENU-001',
        'accurate_id' => 1001,
        'name' => 'Nasi Goreng Spesial',
        'category_type' => 'food-menu',
        'price' => 45000,
        'stock_quantity' => 0,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    InventoryItem::create([
        'code' => 'RAW-001',
        'accurate_id' => 1002,
        'name' => 'Beras Premium',
        'category_type' => 'raw-material',
        'price' => 12000,
        'stock_quantity' => 10,
        'threshold' => 1,
        'unit' => 'kg',
        'is_active' => true,
    ]);

    actingAs($admin)
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertSee('food-menu')
        ->assertSee('Nasi Goreng Spesial');
});

test('menu store can save printer targets for a menu item', function () {
    $admin = adminUser();

    $printerOne = Printer::create([
        'name' => 'Kitchen A',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $printerTwo = Printer::create([
        'name' => 'Kitchen B',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('saveItem')
            ->once()
            ->andReturn([
                'r' => [
                    'id' => 777001,
                    'no' => 'MENU-777001',
                ],
            ]);
    });

    $response = actingAs($admin)->postJson(route('admin.menus.store'), [
        'code_mode' => 'manual',
        'no' => 'MENU-002',
        'name' => 'Menu Printer',
        'item_type' => 'GROUP',
        'category_type' => 'food-menu',
        'unit' => 'porsi',
        'selling_price' => 30000,
        'printer_ids' => [$printerOne->id, $printerTwo->id],
        'detail_group' => [],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true);

    $menu = InventoryItem::where('accurate_id', 777001)->firstOrFail();

    expect($menu->printers->pluck('id')->all())
        ->toEqualCanonicalizing([$printerOne->id, $printerTwo->id]);
});

test('menu detail endpoint returns error when accurate detail cannot be fetched', function () {
    $admin = adminUser();

    $menuItem = InventoryItem::create([
        'code' => 'MENU-DETAIL-FAIL',
        'accurate_id' => 998877,
        'name' => 'Menu Detail Gagal',
        'category_type' => 'food-menu',
        'price' => 25000,
        'stock_quantity' => 0,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getDetailItem')
            ->once()
            ->andReturn(null);
    });

    actingAs($admin)
        ->getJson(route('admin.menus.fetch-detail', $menuItem))
        ->assertStatus(502)
        ->assertJsonPath('success', false);
});

test('admin can update printer targets for menu item', function () {
    $admin = adminUser();

    $menu = InventoryItem::create([
        'code' => 'MENU-PRN-001',
        'accurate_id' => 991001,
        'name' => 'Menu Printer Targets',
        'category_type' => 'food-menu',
        'price' => 25000,
        'stock_quantity' => 0,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    $printerOne = Printer::create([
        'name' => 'Kitchen A',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $printerTwo = Printer::create([
        'name' => 'Bar A',
        'location' => 'bar',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    actingAs($admin)
        ->patchJson(route('admin.menus.update-printer-targets', ['inventory' => $menu->id]), [
            'printer_ids' => [$printerOne->id, $printerTwo->id],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($menu->fresh()->printers->pluck('id')->all())
        ->toEqualCanonicalizing([$printerOne->id, $printerTwo->id]);
});

test('menu store rejects ingredient quantity that is zero or decimal', function (int|float $quantity) {
    $admin = adminUser();

    $response = actingAs($admin)->postJson(route('admin.menus.store'), [
        'code_mode' => 'manual',
        'no' => 'MENU-INVALID-QTY',
        'name' => 'Menu Invalid Qty',
        'item_type' => 'GROUP',
        'category_type' => 'food-menu',
        'unit' => 'porsi',
        'selling_price' => 50000,
        'detail_group' => [
            [
                'item_no' => 'RAW-001',
                'detail_name' => 'Bahan Test',
                'quantity' => $quantity,
            ],
        ],
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['detail_group.0.quantity']);
})->with([
    'zero quantity' => 0,
    'decimal quantity' => 1.5,
]);
