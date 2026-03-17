<?php

use App\Models\Dashboard;
use App\Models\RecapHistory;
use Illuminate\Support\Carbon;

use function Pest\Laravel\artisan;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('recap close command snapshots dashboard totals into recap history and resets dashboard', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 17, 0, 0, 0, 'Asia/Jakarta'));

    $lastSyncedAt = Carbon::create(2026, 3, 16, 23, 55, 0, 'Asia/Jakarta');

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 250000,
            'total_tax' => 15000,
            'total_service_charge' => 10000,
            'total_cash' => 50000,
            'total_transfer' => 100000,
            'total_debit' => 30000,
            'total_kredit' => 40000,
            'total_qris' => 30000,
            'total_kitchen_items' => 12,
            'total_bar_items' => 8,
            'total_transactions' => 5,
            'last_synced_at' => $lastSyncedAt,
        ]
    );

    artisan('recap:close-day')
        ->expectsOutput('Recap closing completed for 2026-03-16.')
        ->assertExitCode(0);

    $history = RecapHistory::query()->whereDate('end_day', '2026-03-16')->first();
    $dashboard = Dashboard::query()->findOrFail(1);

    expect($history)->not->toBeNull()
        ->and((float) $history->total_amount)->toBe(250000.0)
        ->and((float) $history->total_tax)->toBe(15000.0)
        ->and((float) $history->total_service_charge)->toBe(10000.0)
        ->and((float) $history->total_cash)->toBe(50000.0)
        ->and((float) $history->total_transfer)->toBe(100000.0)
        ->and((float) $history->total_debit)->toBe(30000.0)
        ->and((float) $history->total_kredit)->toBe(40000.0)
        ->and((float) $history->total_qris)->toBe(30000.0)
        ->and((int) $history->total_transactions)->toBe(5)
        ->and($history->last_synced_at?->format('Y-m-d H:i:s'))->toBe($lastSyncedAt->format('Y-m-d H:i:s'))
        ->and((float) $dashboard->total_amount)->toBe(0.0)
        ->and((float) $dashboard->total_tax)->toBe(0.0)
        ->and((float) $dashboard->total_service_charge)->toBe(0.0)
        ->and((float) $dashboard->total_cash)->toBe(0.0)
        ->and((float) $dashboard->total_transfer)->toBe(0.0)
        ->and((float) $dashboard->total_debit)->toBe(0.0)
        ->and((float) $dashboard->total_kredit)->toBe(0.0)
        ->and((float) $dashboard->total_qris)->toBe(0.0)
        ->and((int) $dashboard->total_kitchen_items)->toBe(0)
        ->and((int) $dashboard->total_bar_items)->toBe(0)
        ->and((int) $dashboard->total_transactions)->toBe(0)
        ->and($dashboard->last_synced_at)->toBeNull();
});

test('recap close command skips when recap for the same day already exists', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 17, 0, 0, 0, 'Asia/Jakarta'));

    Dashboard::query()->updateOrCreate(
        ['id' => 1],
        [
            'total_amount' => 99000,
            'total_tax' => 9000,
            'total_service_charge' => 5000,
            'total_cash' => 99000,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 3,
            'total_bar_items' => 2,
            'total_transactions' => 3,
            'last_synced_at' => now('Asia/Jakarta'),
        ]
    );

    RecapHistory::query()->create([
        'end_day' => '2026-03-16',
        'total_amount' => 1000,
        'total_tax' => 100,
        'total_service_charge' => 100,
        'total_cash' => 1000,
        'total_transfer' => 0,
        'total_debit' => 0,
        'total_kredit' => 0,
        'total_qris' => 0,
        'total_transactions' => 1,
        'last_synced_at' => now('Asia/Jakarta')->subMinute(),
    ]);

    artisan('recap:close-day')
        ->expectsOutput('Recap for 2026-03-16 has already been closed.')
        ->assertExitCode(0);

    $dashboard = Dashboard::query()->findOrFail(1);

    expect(RecapHistory::query()->count())->toBe(1)
        ->and((float) $dashboard->total_amount)->toBe(99000.0)
        ->and((int) $dashboard->total_kitchen_items)->toBe(3)
        ->and((int) $dashboard->total_bar_items)->toBe(2)
        ->and((int) $dashboard->total_transactions)->toBe(3);
});

test('recap close command reports no data when dashboard totals are empty', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 17, 0, 0, 0, 'Asia/Jakarta'));

    artisan('recap:close-day')
        ->expectsOutput('No dashboard totals to close for 2026-03-16.')
        ->assertExitCode(0);

    $dashboard = Dashboard::query()->findOrFail(1);

    expect(RecapHistory::query()->count())->toBe(0)
        ->and((float) $dashboard->total_amount)->toBe(0.0)
        ->and((int) $dashboard->total_transactions)->toBe(0);
});
