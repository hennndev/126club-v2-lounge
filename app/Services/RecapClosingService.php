<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\RecapHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecapClosingService
{
    /**
     * @return array{status: string, end_day: string, recap_history: ?RecapHistory}
     */
    public function closeDay(?Carbon $closingAt = null): array
    {
        $closingAt ??= now('Asia/Jakarta');
        $closingAt = $closingAt->copy()->timezone('Asia/Jakarta');
        $endDay = $closingAt->hour < 12
            ? $closingAt->copy()->subDay()->toDateString()
            : $closingAt->toDateString();

        return DB::transaction(function () use ($endDay): array {
            $dashboard = Dashboard::query()->firstOrCreate(
                ['id' => 1],
                $this->zeroedDashboardPayload()
            );

            $existingHistory = RecapHistory::query()
                ->whereDate('end_day', $endDay)
                ->first();

            if ($existingHistory !== null) {
                return [
                    'status' => 'already_closed',
                    'end_day' => $endDay,
                    'recap_history' => $existingHistory,
                ];
            }

            if (! $this->hasDashboardData($dashboard)) {
                return [
                    'status' => 'no_data',
                    'end_day' => $endDay,
                    'recap_history' => null,
                ];
            }

            $recapHistory = RecapHistory::query()->create([
                'end_day' => $endDay,
                'total_amount' => (float) $dashboard->total_amount,
                'total_tax' => (float) $dashboard->total_tax,
                'total_service_charge' => (float) $dashboard->total_service_charge,
                'total_cash' => (float) $dashboard->total_cash,
                'total_transfer' => (float) $dashboard->total_transfer,
                'total_debit' => (float) $dashboard->total_debit,
                'total_kredit' => (float) $dashboard->total_kredit,
                'total_qris' => (float) $dashboard->total_qris,
                'total_transactions' => (int) $dashboard->total_transactions,
                'last_synced_at' => $dashboard->last_synced_at,
            ]);

            $dashboard->update($this->zeroedDashboardPayload());

            return [
                'status' => 'closed',
                'end_day' => $endDay,
                'recap_history' => $recapHistory,
            ];
        });
    }

    private function hasDashboardData(Dashboard $dashboard): bool
    {
        return (float) $dashboard->total_amount > 0
            || (float) $dashboard->total_tax > 0
            || (float) $dashboard->total_service_charge > 0
            || (float) $dashboard->total_cash > 0
            || (float) $dashboard->total_transfer > 0
            || (float) $dashboard->total_debit > 0
            || (float) $dashboard->total_kredit > 0
            || (float) $dashboard->total_qris > 0
            || (int) $dashboard->total_kitchen_items > 0
            || (int) $dashboard->total_bar_items > 0
            || (int) $dashboard->total_transactions > 0;
    }

    /**
     * @return array<string, int|float|null>
     */
    private function zeroedDashboardPayload(): array
    {
        return [
            'total_amount' => 0,
            'total_tax' => 0,
            'total_service_charge' => 0,
            'total_cash' => 0,
            'total_transfer' => 0,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_qris' => 0,
            'total_kitchen_items' => 0,
            'total_bar_items' => 0,
            'total_transactions' => 0,
            'last_synced_at' => null,
        ];
    }
}
