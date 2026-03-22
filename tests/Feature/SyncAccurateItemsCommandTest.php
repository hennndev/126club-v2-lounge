<?php

use App\Models\InventoryItem;
use App\Services\AccurateService;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

test('accurate sync items saves detail group from list payload without detail fallback', function () {
    config(['accurate.api_token' => 'dummy-token']);

    $itemPayload = [
        'id' => 919,
        'name' => 'Saus Bangkok',
        'no' => 'BRG-919',
        'unit1Name' => 'Pcs',
        'itemCategory' => ['name' => 'Bahan Baku'],
        'unitPrice' => 12000,
        'suspended' => false,
        'detailGroup' => [
            [
                'id' => 178,
                'detailName' => 'Bawang Bombay',
                'quantity' => 100,
            ],
            [
                'id' => 179,
                'detailName' => 'Bawang Merah',
                'quantity' => 80,
            ],
        ],
    ];

    mock(AccurateService::class, function (MockInterface $mock) use ($itemPayload): void {
        $mock->shouldReceive('getItems')
            ->once()
            ->andReturn(collect([$itemPayload]));
    });

    $this->artisan('accurate:sync-items --force')->assertExitCode(0);

    $item = InventoryItem::query()->where('accurate_id', 919)->first();

    expect($item)->not->toBeNull();
    expect($item->stock_quantity)->toBe(0);
    expect($item->detail_group)->toBe([
        [
            'accurate_id' => 178,
            'name' => 'Bawang Bombay',
            'quantity' => 100,
        ],
        [
            'accurate_id' => 179,
            'name' => 'Bawang Merah',
            'quantity' => 80,
        ],
    ]);
});

test('accurate sync items deletes local items removed from accurate', function () {
    config(['accurate.api_token' => 'dummy-token']);

    InventoryItem::create([
        'accurate_id' => 1001,
        'name' => 'Masih Ada di Accurate',
        'code' => 'BRG-1001',
        'unit' => 'Pcs',
        'category_type' => 'Bahan Baku',
        'price' => 10000,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);

    InventoryItem::create([
        'accurate_id' => 1002,
        'name' => 'Sudah Dihapus di Accurate',
        'code' => 'BRG-1002',
        'unit' => 'Pcs',
        'category_type' => 'Bahan Baku',
        'price' => 12000,
        'stock_quantity' => 4,
        'is_active' => true,
    ]);

    $itemPayload = [
        'id' => 1001,
        'name' => 'Masih Ada di Accurate',
        'no' => 'BRG-1001',
        'unit1Name' => 'Pcs',
        'itemCategory' => ['name' => 'Bahan Baku'],
        'unitPrice' => 10000,
        'allQuantity' => 7,
        'suspended' => false,
        'detailGroup' => [],
    ];

    mock(AccurateService::class, function (MockInterface $mock) use ($itemPayload): void {
        $mock->shouldReceive('getItems')
            ->once()
            ->andReturn(collect([$itemPayload]));
    });

    $this->artisan('accurate:sync-items --force')->assertExitCode(0);

    expect(InventoryItem::query()->where('accurate_id', 1001)->exists())->toBeTrue()
        ->and(InventoryItem::query()->where('accurate_id', 1002)->exists())->toBeFalse();
});
