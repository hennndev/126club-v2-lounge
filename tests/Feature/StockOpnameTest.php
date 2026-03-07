<?php

use App\Models\InventoryItem;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;

function makeInventoryItem(array $attrs = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'code' => 'TEST-'.uniqid(),
        'accurate_id' => rand(1, 999999),
        'name' => 'Test Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 10000,
        'stock_quantity' => 50,
        'threshold' => 5,
        'unit' => 'bottle',
        'is_active' => true,
    ], $attrs));
}

test('index page is accessible to authenticated users', function () {
    $user = adminUser();

    $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.stock-opname.index'))
        ->assertOk()
        ->assertViewIs('stock-opname.index');
});

test('store creates a draft stock opname', function () {
    $user = adminUser();
    $item = makeInventoryItem();

    $response = $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.stock-opname.store'), [
            'opname_date' => now()->format('Y-m-d'),
            'officer_name' => 'Test Officer',
            'notes' => 'Test notes',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'system_stock' => $item->stock_quantity,
                    'physical_stock' => 45,
                    'notes' => null,
                ],
            ],
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    expect(StockOpname::where('officer_name', 'Test Officer')->exists())->toBeTrue();
    expect(StockOpnameItem::where('inventory_item_id', $item->id)->where('physical_stock', 45)->exists())->toBeTrue();
});

test('complete finalizes opname and adjusts inventory stock', function () {
    $user = adminUser();
    $item = makeInventoryItem(['stock_quantity' => 50]);

    $opname = StockOpname::create([
        'opname_date' => now()->format('Y-m-d'),
        'officer_name' => 'Officer',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.stock-opname.complete', $opname), [
            'opname_date' => now()->format('Y-m-d'),
            'officer_name' => 'Officer',
            'notes' => null,
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'system_stock' => 50,
                    'physical_stock' => 40,
                    'notes' => null,
                ],
            ],
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $opname->refresh();
    expect($opname->status)->toBe('completed');
    expect($item->fresh()->stock_quantity)->toBe(40);
});

test('history page is accessible', function () {
    $user = adminUser();

    $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.stock-opname.history'))
        ->assertOk()
        ->assertViewIs('stock-opname.history');
});

test('show page displays a specific opname', function () {
    $user = adminUser();
    $opname = StockOpname::create([
        'opname_date' => now()->format('Y-m-d'),
        'officer_name' => 'Officer',
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('admin.stock-opname.show', $opname))
        ->assertOk()
        ->assertViewIs('stock-opname.show');
});

test('stock opname index requires authentication', function () {
    $this->get(route('admin.stock-opname.index'))
        ->assertRedirect();
});
