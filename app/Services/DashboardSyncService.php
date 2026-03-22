<?php

namespace App\Services;

use App\Models\BarOrderItem;
use App\Models\Billing;
use App\Models\Dashboard;
use App\Models\KitchenOrderItem;
use App\Models\RecapHistory;
use Illuminate\Support\Carbon;

class DashboardSyncService
{
    public function sync(): Dashboard
    {
        $today = Carbon::today();
        $lastCloseAt = RecapHistory::query()->latest('created_at')->value('created_at');

        $totals = [
            'total_amount' => 0.0,
            'total_tax' => 0.0,
            'total_service_charge' => 0.0,
            'total_cash' => 0.0,
            'total_transfer' => 0.0,
            'total_debit' => 0.0,
            'total_kredit' => 0.0,
            'total_qris' => 0.0,
            'total_kitchen_items' => 0,
            'total_bar_items' => 0,
            'total_transactions' => 0,
        ];

        $totals['total_kitchen_items'] = (int) KitchenOrderItem::query()
            ->whereHas('kitchenOrder', function ($query) use ($today): void {
                $query->whereDate('created_at', $today);
            })
            ->when($lastCloseAt, fn ($query) => $query->whereHas('kitchenOrder', fn ($innerQuery) => $innerQuery->where('created_at', '>', $lastCloseAt)))
            ->sum('quantity');

        $totals['total_bar_items'] = (int) BarOrderItem::query()
            ->whereHas('barOrder', function ($query) use ($today): void {
                $query->whereDate('created_at', $today);
            })
            ->when($lastCloseAt, fn ($query) => $query->whereHas('barOrder', fn ($innerQuery) => $innerQuery->where('created_at', '>', $lastCloseAt)))
            ->sum('quantity');

        $paidBillings = Billing::query()
            ->where('billing_status', 'paid')
            ->whereDate('updated_at', $today)
            ->where(function ($query) {
                $query->where('is_booking', true)
                    ->orWhere('is_walk_in', true);
            })
            ->when($lastCloseAt, fn ($query) => $query->where('updated_at', '>', $lastCloseAt))
            ->get();

        foreach ($paidBillings as $billing) {
            $paidAmount = (float) ($billing->paid_amount ?? $billing->grand_total ?? 0);

            $totals['total_transactions']++;
            $totals['total_amount'] += $paidAmount;
            $totals['total_tax'] += (float) ($billing->tax ?? 0);
            $totals['total_service_charge'] += (float) ($billing->service_charge ?? 0);

            if (strtolower((string) ($billing->payment_mode ?? 'normal')) === 'split') {
                $splitCashAmount = (float) ($billing->split_cash_amount ?? 0);
                $splitNonCashAmount = (float) ($billing->split_debit_amount ?? 0);
                $splitSecondNonCashAmount = (float) ($billing->split_second_non_cash_amount ?? 0);

                $totals['total_cash'] += $splitCashAmount;
                $splitNonCashAmount = $splitNonCashAmount > 0
                    ? $splitNonCashAmount
                    : max($paidAmount - $splitCashAmount, 0);

                $splitNonCashMethod = $billing->split_non_cash_method ?: 'debit';
                $this->addPaymentAmount($totals, $this->normalizePaymentMethod($splitNonCashMethod), $splitNonCashAmount);

                if ($splitSecondNonCashAmount > 0) {
                    $splitSecondNonCashMethod = $billing->split_second_non_cash_method ?: 'debit';
                    $this->addPaymentAmount($totals, $this->normalizePaymentMethod($splitSecondNonCashMethod), $splitSecondNonCashAmount);
                }

                continue;
            }

            $this->addPaymentAmount($totals, $this->normalizePaymentMethod($billing->payment_method), $paidAmount);
        }

        return Dashboard::query()->updateOrCreate(
            ['id' => 1],
            [
                ...$totals,
                'last_synced_at' => now(),
            ]
        );
    }

    private function normalizePaymentMethod(?string $paymentMethod): ?string
    {
        return match (strtolower(trim((string) $paymentMethod))) {
            'cash' => 'total_cash',
            'transfer' => 'total_transfer',
            'debit', 'debit-card' => 'total_debit',
            'kredit', 'credit-card' => 'total_kredit',
            'qris' => 'total_qris',
            default => null,
        };
    }

    /**
     * @param  array<string, float|int>  $totals
     */
    private function addPaymentAmount(array &$totals, ?string $bucket, float $amount): void
    {
        if ($bucket === null || $amount <= 0 || ! array_key_exists($bucket, $totals)) {
            return;
        }

        $totals[$bucket] += $amount;
    }
}
