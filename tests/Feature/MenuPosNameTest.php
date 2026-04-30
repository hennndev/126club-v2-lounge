<?php

use App\Models\InventoryItem;
use App\Models\PosCategorySetting;

use function Pest\Laravel\actingAs;

test('update pos name endpoint saves pos_name on inventory item', function () {
    $admin = adminUser();

    $item = InventoryItem::create([
        'code' => 'MENU-001',
        'accurate_id' => 1001,
        'name' => 'Nasi Goreng Spesial',
        'pos_name' => null,
        'category_type' => 'food',
        'price' => 45000,
        'stock_quantity' => 0,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    actingAs($admin)
        ->patchJson(route('admin.menus.update-pos-name', $item), ['pos_name' => 'Nasgor Spesial'])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('pos_name', 'Nasgor Spesial');

    expect($item->fresh()->pos_name)->toBe('Nasgor Spesial');
});

test('update pos name requires a non-empty string', function () {
    $admin = adminUser();

    $item = InventoryItem::create([
        'code' => 'MENU-002',
        'accurate_id' => 1002,
        'name' => 'Ayam Bakar',
        'category_type' => 'food',
        'price' => 40000,
        'stock_quantity' => 0,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    actingAs($admin)
        ->patchJson(route('admin.menus.update-pos-name', $item), ['pos_name' => ''])
        ->assertUnprocessable();
});

test('pos index returns pos_name as product name when set', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'food',
        'show_in_pos' => true,
        'is_menu' => true,
        'preparation_location' => 'kitchen',
    ]);

    InventoryItem::create([
        'code' => 'MENU-003',
        'accurate_id' => 1003,
        'name' => 'Nasi Goreng Ayam',
        'pos_name' => 'Nasgor Ayam',
        'category_type' => 'food',
        'price' => 35000,
        'stock_quantity' => 10,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    $response = actingAs($admin)->get(route('admin.pos.index'));

    $response->assertOk()->assertSee('Nasgor Ayam');
});

test('pos index falls back to name when pos_name is null', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'food',
        'show_in_pos' => true,
        'is_menu' => true,
        'preparation_location' => 'kitchen',
    ]);

    InventoryItem::create([
        'code' => 'MENU-004',
        'accurate_id' => 1004,
        'name' => 'Mie Goreng Spesial',
        'pos_name' => null,
        'category_type' => 'food',
        'price' => 30000,
        'stock_quantity' => 10,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    $response = actingAs($admin)->get(route('admin.pos.index'));

    $response->assertOk()->assertSee('Mie Goreng Spesial');
});

test('pos search finds item by pos_name', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'food',
        'show_in_pos' => true,
        'is_menu' => true,
        'preparation_location' => 'kitchen',
    ]);

    InventoryItem::create([
        'code' => 'MENU-005',
        'accurate_id' => 1005,
        'name' => 'Nasi Uduk Betawi',
        'pos_name' => 'Nasi Uduk',
        'category_type' => 'food',
        'price' => 25000,
        'stock_quantity' => 10,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
    ]);

    $response = actingAs($admin)->get(route('admin.pos.index', ['search' => 'Nasi Uduk']));

    $response->assertOk()->assertSee('Nasi Uduk');
});

test('pos index hides inventory items marked invisible in pos', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'food',
        'show_in_pos' => true,
        'is_menu' => true,
        'preparation_location' => 'kitchen',
    ]);

    InventoryItem::create([
        'code' => 'MENU-006',
        'accurate_id' => 1006,
        'name' => 'Ayam Bakar Hidden',
        'pos_name' => 'Ayam Bakar POS Hidden',
        'category_type' => 'food',
        'price' => 42000,
        'stock_quantity' => 10,
        'threshold' => 0,
        'unit' => 'porsi',
        'is_active' => true,
        'is_visible_in_pos' => false,
    ]);

    $response = actingAs($admin)->get(route('admin.pos.index'));

    $response->assertOk()->assertDontSee('Ayam Bakar Hidden')->assertDontSee('Ayam Bakar POS Hidden');
});
