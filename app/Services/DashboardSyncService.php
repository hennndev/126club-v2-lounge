<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\Dashboard;
use Illuminate\Support\Carbon;

class DashboardSyncService
{
    public function sync(): Dashboard
    {
        $today = Carbon::today();

        $totals = [
            'total_amount' => 0.0,
            'total_tax' => 0.0,
            'total_service_charge' => 0.0,
            'total_cash' => 0.0,
            'total_transfer' => 0.0,
            'total_debit' => 0.0,
            'total_kredit' => 0.0,
            'total_qris' => 0.0,
            'total_transactions' => 0,
        ];

        $paidBillings = Billing::query()
            ->where('billing_status', 'paid')
            ->whereDate('updated_at', $today)
            ->where(function ($query) {
                $query->where('is_booking', true)
                    ->orWhere('is_walk_in', true);
            })
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

                $totals['total_cash'] += $splitCashAmount;
                $splitNonCashAmount = $splitNonCashAmount > 0
                    ? $splitNonCashAmount
                    : max($paidAmount - $splitCashAmount, 0);

                $splitNonCashMethod = $billing->split_non_cash_method ?: 'debit';
                $this->addPaymentAmount($totals, $this->normalizePaymentMethod($splitNonCashMethod), $splitNonCashAmount);

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
