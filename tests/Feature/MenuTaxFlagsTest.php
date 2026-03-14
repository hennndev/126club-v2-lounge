<?php

use App\Models\InventoryItem;

test('admin can toggle include tax flag for menu item', function () {
    $admin = adminUser();

    $menu = InventoryItem::create([
        'code' => 'MENU-TAX-001',
        'accurate_id' => 900001,
        'name' => 'Menu Tax Toggle',
        'category_type' => 'food',
        'price' => 25000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'include_tax' => true,
        'include_service_charge' => true,
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.menus.update-tax-flags', ['inventory' => $menu->id]), [
            'field' => 'include_tax',
            'value' => false,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'value' => false,
        ]);

    expect((bool) $menu->fresh()->include_tax)->toBeFalse();
});

test('tax flag endpoint validates allowed field', function () {
    $admin = adminUser();

    $menu = InventoryItem::create([
        'code' => 'MENU-TAX-002',
        'accurate_id' => 900002,
        'name' => 'Menu Validation',
        'category_type' => 'food',
        'price' => 30000,
        'stock_quantity' => 100,
        'threshold' => 10,
        'unit' => 'porsi',
        'is_active' => true,
        'include_tax' => true,
        'include_service_charge' => true,
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.menus.update-tax-flags', ['inventory' => $menu->id]), [
            'field' => 'invalid_field',
            'value' => true,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('field');
});
