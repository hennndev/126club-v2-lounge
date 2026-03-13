<?php

use App\Models\InventoryItem;
use App\Models\PosCategorySetting;
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
        ->assertSee('Nasi Goreng Spesial')
        ->assertDontSee('raw-material');
});

test('menu store rejects category not marked as menu in pos settings', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'food-menu',
        'show_in_pos' => true,
        'is_menu' => true,
        'preparation_location' => 'kitchen',
    ]);

    $response = actingAs($admin)->postJson(route('admin.menus.store'), [
        'no' => 'MENU-002',
        'name' => 'Menu Tidak Valid',
        'category_type' => 'raw-material',
        'unit' => 'porsi',
        'selling_price' => 30000,
        'detail_group' => [],
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['category_type']);
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

test('menu store rejects ingredient quantity that is zero or decimal', function (int|float $quantity) {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'food-menu',
        'show_in_pos' => true,
        'is_menu' => true,
        'preparation_location' => 'kitchen',
    ]);

    $response = actingAs($admin)->postJson(route('admin.menus.store'), [
        'no' => 'MENU-INVALID-QTY',
        'name' => 'Menu Invalid Qty',
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
