<?php

use App\Models\InventoryItem;
use App\Models\PosCategorySetting;
use App\Services\AccurateService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

function makeInventoryForActivationTest(array $attributes = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'code' => 'INV-ACT-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Inventory Active Test '.uniqid(),
        'category_type' => 'beverage',
        'price' => 12000,
        'stock_quantity' => 20,
        'threshold' => 5,
        'unit' => 'unit',
        'is_active' => true,
    ], $attributes));
}

test('inventory item can be toggled inactive from inventory page', function () {
    $admin = adminUser();
    $item = makeInventoryForActivationTest(['is_active' => true]);

    mock(AccurateService::class, function (MockInterface $mock) use ($item): void {
        $mock->shouldReceive('saveItem')
            ->once()
            ->with([
                'id' => $item->accurate_id,
                'suspended' => true,
            ])
            ->andReturn(['s' => true]);
    });

    $response = actingAs($admin)
        ->patch(route('admin.inventory.toggle-active', $item));

    $response
        ->assertRedirect(route('admin.inventory.index'))
        ->assertSessionHas('success');

    expect($item->fresh()->is_active)->toBeFalse();
});

test('inventory item can be toggled active from inventory page', function () {
    $admin = adminUser();
    $item = makeInventoryForActivationTest(['is_active' => false]);

    mock(AccurateService::class, function (MockInterface $mock) use ($item): void {
        $mock->shouldReceive('saveItem')
            ->once()
            ->with([
                'id' => $item->accurate_id,
                'suspended' => false,
            ])
            ->andReturn(['s' => true]);
    });

    $response = actingAs($admin)
        ->patch(route('admin.inventory.toggle-active', $item));

    $response
        ->assertRedirect(route('admin.inventory.index'))
        ->assertSessionHas('success');

    expect($item->fresh()->is_active)->toBeTrue();
});

test('inactive inventory item is not shown on pos page', function () {
    $admin = adminUser();

    PosCategorySetting::query()->updateOrCreate(
        ['category_type' => 'beverage'],
        [
            'show_in_pos' => true,
            'is_menu' => false,
            'preparation_location' => 'bar',
        ],
    );

    $activeItem = makeInventoryForActivationTest([
        'name' => 'POS ACTIVE ITEM',
        'is_active' => true,
    ]);

    $inactiveItem = makeInventoryForActivationTest([
        'name' => 'POS INACTIVE ITEM',
        'is_active' => false,
    ]);

    $response = actingAs($admin)->get(route('admin.pos.index'));

    $response->assertSuccessful();

    $products = collect($response->viewData('products'))->pluck('name')->all();

    expect($products)->toContain($activeItem->name)
        ->and($products)->not->toContain($inactiveItem->name);
});

test('inventory page always shows stock column for menu categories', function () {
    $admin = adminUser();

    PosCategorySetting::query()->updateOrCreate(
        ['category_type' => 'main-course'],
        [
            'show_in_pos' => true,
            'is_menu' => true,
            'preparation_location' => 'kitchen',
        ],
    );

    makeInventoryForActivationTest([
        'name' => 'MENU STOCK BLANK TEST',
        'category_type' => 'main-course',
        'stock_quantity' => 77,
        'unit' => 'porsi',
    ]);

    actingAs($admin)
        ->get(route('admin.inventory.index'))
        ->assertSuccessful()
        ->assertSee('MENU STOCK BLANK TEST')
        ->assertSee('77 porsi');
});
