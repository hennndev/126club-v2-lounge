<?php

use App\Models\Dashboard;
use App\Models\RecapHistory;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('manual close export uses today as end day when closed at night', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 18, 23, 0, 0, 'Asia/Jakarta'));

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 100000,
            'total_tax' => 10000,
            'total_service_charge' => 5000,
            'total_cash' => 100000,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 4,
            'total_bar_items' => 2,
            'total_transactions' => 3,
            'last_synced_at' => now('Asia/Jakarta'),
        ]
    );

    $admin = adminUser();

    $response = actingAs($admin)
        ->post(route('admin.recap.close-export'));

    $response->assertSuccessful();

    $history = RecapHistory::query()->latest('id')->first();
    $dashboard = Dashboard::query()->findOrFail(1);

    expect($history)->not->toBeNull()
        ->and($history->end_day?->toDateString())->toBe('2026-03-18')
        ->and(DB::table('recap_history_kitchen')->count())->toBe(0)
        ->and(DB::table('recap_history_bar')->count())->toBe(0)
        ->and((int) $history->total_kitchen_items)->toBe(4)
        ->and((int) $history->total_bar_items)->toBe(2)
        ->and((float) $dashboard->total_amount)->toBe(0.0)
        ->and((int) $dashboard->total_transactions)->toBe(0);
});

test('manual close export uses yesterday as end day when closed in morning before 9am', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 18, 8, 0, 0, 'Asia/Jakarta'));

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 150000,
            'total_tax' => 15000,
            'total_service_charge' => 7000,
            'total_cash' => 150000,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 5,
            'total_bar_items' => 3,
            'total_transactions' => 4,
            'last_synced_at' => now('Asia/Jakarta'),
        ]
    );

    $admin = adminUser();

    $response = actingAs($admin)
        ->post(route('admin.recap.close-export'));

    $response->assertSuccessful();

    $history = RecapHistory::query()->latest('id')->first();
    $dashboard = Dashboard::query()->findOrFail(1);

    expect($history)->not->toBeNull()
        ->and($history->end_day?->toDateString())->toBe('2026-03-17')
        ->and(DB::table('recap_history_kitchen')->count())->toBe(0)
        ->and(DB::table('recap_history_bar')->count())->toBe(0)
        ->and((int) $history->total_kitchen_items)->toBe(5)
        ->and((int) $history->total_bar_items)->toBe(3)
        ->and((float) $dashboard->total_amount)->toBe(0.0)
        ->and((int) $dashboard->total_transactions)->toBe(0);
});

test('manual close export uses today as end day when closed at exactly 9am', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 18, 9, 0, 0, 'Asia/Jakarta'));

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 80000,
            'total_tax' => 8000,
            'total_service_charge' => 4000,
            'total_cash' => 80000,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 2,
            'total_bar_items' => 1,
            'total_transactions' => 2,
            'last_synced_at' => now('Asia/Jakarta'),
        ]
    );

    $admin = adminUser();

    $response = actingAs($admin)
        ->post(route('admin.recap.close-export'));

    $response->assertSuccessful();

    $history = RecapHistory::query()->latest('id')->first();
    $dashboard = Dashboard::query()->findOrFail(1);

    expect($history)->not->toBeNull()
        ->and($history->end_day?->toDateString())->toBe('2026-03-18')
        ->and(DB::table('recap_history_kitchen')->count())->toBe(0)
        ->and(DB::table('recap_history_bar')->count())->toBe(0)
        ->and((int) $history->total_kitchen_items)->toBe(2)
        ->and((int) $history->total_bar_items)->toBe(1)
        ->and((float) $dashboard->total_amount)->toBe(0.0)
        ->and((int) $dashboard->total_transactions)->toBe(0);
});
