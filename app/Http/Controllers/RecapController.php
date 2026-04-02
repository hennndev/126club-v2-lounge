<?php

namespace App\Http\Controllers;

use App\Models\BarOrderItem;
use App\Models\Billing;
use App\Models\Dashboard;
use App\Models\GeneralSetting;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\Printer;
use App\Models\RecapHistory;
use App\Services\PrinterService;
use App\Services\RecapClosingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecapController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
        ]);

        [$startAt, $endAt] = $this->resolveRange($validated);
        $recapData = $this->buildRecapData($startAt, $endAt, (bool) ($validated['reprint'] ?? false));

        return view('recap.index', $recapData);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
        ]);

        [$startAt, $endAt] = $this->resolveRange($validated);
        $recapData = $this->buildRecapData($startAt, $endAt);

        return $this->downloadRows(
            $this->buildLiveRecapExportRows($recapData),
            'rekapan-'.$startAt->format('Ymd_Hi').'-'.$endAt->format('Ymd_Hi').'.xlsx'
        );
    }

    public function closePreview(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'reprint' => ['nullable', 'boolean'],
            'recap_history_id' => ['nullable', 'integer', 'exists:recap_history,id'],
        ]);

        [$startAt, $endAt] = $this->resolveRange($validated);
        $recapHistory = ! empty($validated['recap_history_id'])
            ? RecapHistory::query()->find((int) $validated['recap_history_id'])
            : null;

        $recapData = $recapHistory
            ? $this->buildRecapDataFromHistory($recapHistory)
            : $this->buildRecapData($startAt, $endAt, (bool) ($validated['reprint'] ?? false));

        return view('recap.close-preview', array_merge($recapData, [
            'printedAt' => now(),
            'isReprintPreview' => (bool) ($validated['reprint'] ?? false),
            'reprintHistoryId' => (int) ($validated['recap_history_id'] ?? 0),
        ]));
    }

    public function printClosePreview(Request $request, PrinterService $printerService): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'recap_history_id' => ['nullable', 'integer', 'exists:recap_history,id'],
        ]);

        [$startAt, $endAt] = $this->resolveRange($validated);
        $recapHistory = ! empty($validated['recap_history_id'])
            ? RecapHistory::query()->find((int) $validated['recap_history_id'])
            : null;

        $recapData = $recapHistory
            ? $this->buildRecapDataFromHistory($recapHistory)
            : $this->buildRecapData($startAt, $endAt);

        $printer = $this->resolveEndDayPrinter();

        if (! $printer) {
            return response()->json([
                'success' => false,
                'message' => 'Printer End Day belum dikonfigurasi atau tidak aktif.',
            ], 422);
        }

        try {
            $printerService->printEndDayRecap($recapData, $printer);

            $message = "Print End Day berhasil dikirim ke printer {$printer->name}.";

            if ($printer->connection_type === 'log') {
                $message .= ' (LOG MODE)';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'printer_name' => $printer->name,
                'connection_type' => $printer->connection_type,
                'log_path' => $printer->connection_type === 'log' ? storage_path('logs/printer.log') : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal print End Day: '.$e->getMessage(),
            ], 500);
        }
    }

    public function closeAndExport(RecapClosingService $recapClosingService): BinaryFileResponse|RedirectResponse
    {
        $result = $recapClosingService->closeDay();

        if ($result['status'] === 'no_data') {
            return back()->with('error', 'Tidak ada data dashboard untuk ditutup.');
        }

        $recapHistory = $result['recap_history'];

        if (! $recapHistory) {
            return back()->with('error', 'Gagal menyiapkan data end day.');
        }

        return $this->downloadRows(
            $this->buildHistoryExportRows($recapHistory),
            'rekapan-history-'.$recapHistory->end_day?->format('Ymd').'.xlsx'
        );
    }

    public function exportHistory(RecapHistory $recapHistory): BinaryFileResponse
    {
        return $this->downloadRows(
            $this->buildHistoryExportRows($recapHistory),
            'rekapan-history-'.$recapHistory->end_day?->format('Ymd').'.xlsx'
        );
    }

    public function reprintHistory(RecapHistory $recapHistory, PrinterService $printerService): RedirectResponse
    {
        if (! $recapHistory->end_day) {
            return back()->with('error', 'Tanggal end day history tidak valid.');
        }

        [$startAt, $endAt] = $this->resolveEndDayWindow($recapHistory->end_day->copy());

        $printer = $this->resolveEndDayPrinter();

        if (! $printer) {
            return back()->with('error', 'Printer End Day belum dikonfigurasi atau tidak aktif.');
        }

        $recapData = $this->buildRecapDataFromHistory($recapHistory);

        try {
            $printerService->printEndDayRecap($recapData, $printer);

            $message = "Reprint history end day berhasil dikirim ke printer {$printer->name}.";

            if ($printer->connection_type === 'log') {
                $message .= ' (LOG MODE)';
            }

            return redirect()->route('admin.recap.close-preview', [
                'start_datetime' => $startAt->format('Y-m-d\TH:i'),
                'end_datetime' => $endAt->format('Y-m-d\TH:i'),
                'reprint' => 1,
                'recap_history_id' => $recapHistory->id,
            ])->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal reprint history End Day: '.$e->getMessage());
        }
    }

    private function buildRecapData(Carbon $startAt, Carbon $endAt, bool $includeClosedEndDayData = false): array
    {
        $isSelectedEndDayClosed = ! $includeClosedEndDayData
            && $this->isSelectedEndDayClosed($startAt);

        $orders = Order::query()
            ->with(['items.inventoryItem', 'tableSession.customer.profile', 'tableSession.reservation', 'customer.user'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startAt, $endAt): void {
                $query->whereBetween('ordered_at', [$startAt, $endAt])
                    ->orWhere(function ($fallbackQuery) use ($startAt, $endAt): void {
                        $fallbackQuery->whereNull('ordered_at')
                            ->whereBetween('created_at', [$startAt, $endAt]);
                    });
            })
            ->orderByRaw('COALESCE(ordered_at, created_at) ASC')
            ->get();

        if ($isSelectedEndDayClosed) {
            $orders = collect();
        }

        $billingsBySessionId = Billing::query()
            ->whereIn('table_session_id', $orders->pluck('table_session_id')->filter()->unique()->values())
            ->get()
            ->keyBy('table_session_id');

        $billingsByOrderId = Billing::query()
            ->whereIn('order_id', $orders->pluck('id')->filter()->unique()->values())
            ->get()
            ->keyBy('order_id');

        $cashierTransactions = $orders
            ->map(function (Order $order) use ($billingsBySessionId, $billingsByOrderId): array {
                $eventTime = $order->ordered_at ?? $order->created_at;
                $customerName = $order->tableSession?->customer?->profile?->name
                    ?? $order->tableSession?->customer?->name
                    ?? $order->customer?->user?->name
                    ?? 'Walk-in';
                $billing = $billingsByOrderId->get($order->id)
                    ?? ($order->table_session_id ? $billingsBySessionId->get($order->table_session_id) : null);
                $paymentMode = $order->payment_mode ?? $billing?->payment_mode;
                $paymentMethod = $order->payment_method ?? $billing?->payment_method;
                $orderItems = $order->items
                    ->where('status', '!=', 'cancelled')
                    ->values();
                $paymentReferenceNumber = $order->payment_reference_number
                    ?? $billing?->payment_reference_number
                    ?? $billing?->split_non_cash_reference_number;

                $itemsSubtotal = (float) $orderItems->sum(fn ($item) => (float) ($item->subtotal ?? 0));
                $itemTaxTotal = (float) $orderItems->sum(fn ($item) => (float) ($item->tax_amount ?? 0));
                $itemServiceChargeTotal = (float) $orderItems->sum(fn ($item) => (float) ($item->service_charge_amount ?? 0));

                $totalBill = $billing
                    ? max((float) ($billing->minimum_charge ?? 0), (float) ($billing->orders_total ?? 0))
                    : $itemsSubtotal;
                $taxTotal = $billing ? (float) ($billing->tax ?? 0) : $itemTaxTotal;
                $serviceChargeTotal = $billing ? (float) ($billing->service_charge ?? 0) : $itemServiceChargeTotal;
                $subTotal = $totalBill + $taxTotal + $serviceChargeTotal;

                $discountAmount = (float) ($billing?->discount_amount ?? $order->discount_amount ?? 0);
                $downPaymentAmount = (float) ($order->tableSession?->reservation?->down_payment_amount ?? 0);

                $remainingTotal = max($subTotal - $discountAmount - $downPaymentAmount, 0);

                return [
                    'timestamp' => $eventTime,
                    'datetime' => $eventTime?->format('d/m/Y H:i') ?? '-',
                    'order_number' => $order->order_number
                        ?? $billing?->transaction_code
                        ?? ('TRX-'.$order->id),
                    'customer_name' => $customerName,
                    'payment_method' => $this->formatPaymentMethod($paymentMethod, $paymentMode),
                    'payment_reference_number' => $paymentReferenceNumber,
                    'items_count' => $orderItems->count(),
                    'total_bill' => $totalBill,
                    'tax_total' => $taxTotal,
                    'service_charge_total' => $serviceChargeTotal,
                    'sub_total' => $subTotal,
                    'discount_amount' => $discountAmount,
                    'down_payment_amount' => $downPaymentAmount,
                    'order_status' => (string) $order->status,
                    'billing_status' => (string) ($billing?->billing_status ?? ''),
                    'items' => $orderItems
                        ->map(fn ($item): array => [
                            'name' => (string) ($item->inventoryItem?->pos_name ?? $item->inventoryItem?->name ?? $item->item_name ?? '-'),
                            'quantity' => (int) ($item->quantity ?? 0),
                            'price' => (float) ($item->price ?? 0),
                            'subtotal' => (float) ($item->subtotal ?? 0),
                            'tax_amount' => (float) ($item->tax_amount ?? 0),
                            'service_charge_amount' => (float) ($item->service_charge_amount ?? 0),
                        ])
                        ->values()
                        ->all(),
                    'total' => $remainingTotal,
                ];
            })
            ->filter(function (array $transaction): bool {
                $isPaidByBilling = $transaction['billing_status'] === 'paid';
                $isCompletedOrder = $transaction['order_status'] === 'completed';

                if (! $isPaidByBilling && ! $isCompletedOrder) {
                    return false;
                }

                return $transaction['items_count'] > 0
                    || (float) $transaction['total'] > 0
                    || filled($transaction['payment_reference_number'])
                    || ($transaction['payment_method'] ?? '-') !== '-';
            })
            ->map(function (array $transaction): array {
                unset($transaction['order_status'], $transaction['billing_status']);

                return $transaction;
            })
            ->values();

        $rokokItems = $orders
            ->flatMap(function (Order $order) {
                return $order->items
                    ->where('status', '!=', 'cancelled')
                    ->filter(function ($item): bool {
                        $categoryType = strtolower(trim((string) ($item->inventoryItem?->category_type ?? '')));

                        return $categoryType !== '' && str_contains($categoryType, 'rokok');
                    })
                    ->map(function ($item): array {
                        return [
                            'name' => (string) ($item->inventoryItem?->pos_name ?? $item->inventoryItem?->name ?? $item->item_name ?? '-'),
                            'quantity' => (int) ($item->quantity ?? 0),
                        ];
                    });
            })
            ->groupBy('name')
            ->map(function ($group, $name): array {
                return [
                    'name' => (string) $name,
                    'quantity' => (int) collect($group)->sum('quantity'),
                ];
            })
            ->sortBy('name')
            ->values();

        $totalDiscount = (float) $cashierTransactions->sum('discount_amount');
        $totalDownPayment = (float) $cashierTransactions->sum('down_payment_amount');

        $dashboardAggregate = Dashboard::query()->find(1);

        $recapHistories = RecapHistory::query()
            ->latest('end_day')
            ->limit(10)
            ->get();

        $dashboardPaymentMethodTotals = [
            'cash' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_cash ?? 0),
            'transfer' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_transfer ?? 0),
            'debit' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_debit ?? 0),
            'kredit' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_kredit ?? 0),
            'qris' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_qris ?? 0),
        ];

        $paymentMethodTotals = $dashboardPaymentMethodTotals;

        if (! $isSelectedEndDayClosed) {
            $livePaymentMethodSummary = $this->resolveLivePaymentMethodTotals($startAt, $endAt);

            if ($livePaymentMethodSummary['has_paid_billings']) {
                $paymentMethodTotals = $livePaymentMethodSummary['totals'];
            }
        }

        $kitchenItems = $isSelectedEndDayClosed
            ? collect()
            : KitchenOrderItem::query()
                ->with(['inventoryItem', 'kitchenOrder.order'])
                ->whereHas('kitchenOrder', function ($query) use ($startAt, $endAt): void {
                    $query->whereBetween('created_at', [$startAt, $endAt]);
                })
                ->get()
                ->map(function (KitchenOrderItem $item): array {
                    $eventTime = $item->kitchenOrder?->order?->ordered_at
                        ?? $item->kitchenOrder?->created_at
                        ?? $item->created_at;

                    return [
                        'timestamp' => $eventTime,
                        'datetime' => $eventTime?->format('d/m/Y H:i') ?? '-',
                        'order_number' => $item->kitchenOrder?->order_number ?? '-',
                        'item_name' => $item->inventoryItem?->pos_name ?? $item->inventoryItem?->name ?? 'Unknown Item',
                        'qty' => (int) $item->quantity,
                    ];
                })
                ->sortBy(fn (array $event) => $event['timestamp'] ?? now())
                ->values();

        $barItems = $isSelectedEndDayClosed
            ? collect()
            : BarOrderItem::query()
                ->with(['inventoryItem', 'barOrder.order'])
                ->whereHas('barOrder', function ($query) use ($startAt, $endAt): void {
                    $query->whereBetween('created_at', [$startAt, $endAt]);
                })
                ->get()
                ->map(function (BarOrderItem $item): array {
                    $eventTime = $item->barOrder?->order?->ordered_at
                        ?? $item->barOrder?->created_at
                        ?? $item->created_at;

                    return [
                        'timestamp' => $eventTime,
                        'datetime' => $eventTime?->format('d/m/Y H:i') ?? '-',
                        'order_number' => $item->barOrder?->order_number ?? '-',
                        'item_name' => $item->inventoryItem?->pos_name ?? $item->inventoryItem?->name ?? 'Unknown Item',
                        'qty' => (int) $item->quantity,
                    ];
                })
                ->sortBy(fn (array $event) => $event['timestamp'] ?? now())
                ->values();

        return [
            'selectedDate' => $startAt->toDateString(),
            'selectedStartDatetime' => $startAt->format('Y-m-d\TH:i'),
            'selectedEndDatetime' => $endAt->format('Y-m-d\TH:i'),
            'cashierTransactions' => $cashierTransactions,
            'cashierCount' => $isSelectedEndDayClosed ? 0 : (int) ($dashboardAggregate?->total_transactions ?? 0),
            'cashierRevenue' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_amount ?? 0),
            'totalPenjualanRokok' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_penjualan_rokok ?? 0),
            'totalFood' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_food ?? 0),
            'totalAlcohol' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_alcohol ?? 0),
            'totalBeverage' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_beverage ?? 0),
            'totalCigarette' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_cigarette ?? 0),
            'totalBreakage' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_breakage ?? 0),
            'totalRoom' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_room ?? 0),
            'totalLd' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_ld ?? 0),
            'totalTax' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_tax ?? 0),
            'totalServiceCharge' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_service_charge ?? 0),
            'totalDiscount' => $totalDiscount,
            'totalDownPayment' => $totalDownPayment,
            'totalCash' => $isSelectedEndDayClosed ? 0.0 : (float) $paymentMethodTotals['cash'],
            'paymentMethodTotals' => $paymentMethodTotals,
            'kitchenItems' => $kitchenItems,
            'kitchenQtyTotal' => $isSelectedEndDayClosed ? 0 : (int) ($dashboardAggregate?->total_kitchen_items ?? 0),
            'barItems' => $barItems,
            'barQtyTotal' => $isSelectedEndDayClosed ? 0 : (int) ($dashboardAggregate?->total_bar_items ?? 0),
            'rokokItems' => $rokokItems,
            'dashboardPreview' => [
                'total_food' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_food ?? 0),
                'total_alcohol' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_alcohol ?? 0),
                'total_beverage' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_beverage ?? 0),
                'total_cigarette' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_cigarette ?? 0),
                'total_breakage' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_breakage ?? 0),
                'total_room' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_room ?? 0),
                'total_ld' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_ld ?? 0),
                'total_penjualan_rokok' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_penjualan_rokok ?? 0),
                'total_tax' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_tax ?? 0),
                'total_service_charge' => $isSelectedEndDayClosed ? 0.0 : (float) ($dashboardAggregate?->total_service_charge ?? 0),
                'total_discount' => $totalDiscount,
                'total_down_payment' => $totalDownPayment,
                'total_cash' => $isSelectedEndDayClosed ? 0.0 : (float) $paymentMethodTotals['cash'],
                'total_transfer' => $isSelectedEndDayClosed ? 0.0 : (float) $paymentMethodTotals['transfer'],
                'total_debit' => $isSelectedEndDayClosed ? 0.0 : (float) $paymentMethodTotals['debit'],
                'total_kredit' => $isSelectedEndDayClosed ? 0.0 : (float) $paymentMethodTotals['kredit'],
                'total_qris' => $isSelectedEndDayClosed ? 0.0 : (float) $paymentMethodTotals['qris'],
                'total_kitchen_items' => $isSelectedEndDayClosed ? 0 : (int) ($dashboardAggregate?->total_kitchen_items ?? 0),
                'total_bar_items' => $isSelectedEndDayClosed ? 0 : (int) ($dashboardAggregate?->total_bar_items ?? 0),
                'total_transactions' => $isSelectedEndDayClosed ? 0 : (int) ($dashboardAggregate?->total_transactions ?? 0),
            ],
            'recapHistories' => $recapHistories,
        ];
    }

    /**
     * @return array{has_paid_billings: bool, totals: array{cash: float, transfer: float, debit: float, kredit: float, qris: float}}
     */
    private function resolveLivePaymentMethodTotals(Carbon $startAt, Carbon $endAt): array
    {
        $paymentMethodTotals = [
            'cash' => 0.0,
            'transfer' => 0.0,
            'debit' => 0.0,
            'kredit' => 0.0,
            'qris' => 0.0,
        ];

        $paidBillings = Billing::query()
            ->where('billing_status', 'paid')
            ->where(function ($query): void {
                $query->where('is_booking', true)
                    ->orWhere('is_walk_in', true);
            })
            ->whereBetween('updated_at', [$startAt, $endAt])
            ->get();

        foreach ($paidBillings as $billing) {
            $paidAmount = (float) ($billing->paid_amount ?? $billing->grand_total ?? 0);

            if (strtolower((string) ($billing->payment_mode ?? 'normal')) === 'split') {
                $paymentMethodTotals['cash'] += (float) ($billing->split_cash_amount ?? 0);

                $this->addPaymentMethodAmount(
                    $paymentMethodTotals,
                    $this->normalizePaymentMethod($billing->split_non_cash_method),
                    (float) ($billing->split_debit_amount ?? 0)
                );

                $this->addPaymentMethodAmount(
                    $paymentMethodTotals,
                    $this->normalizePaymentMethod($billing->split_second_non_cash_method),
                    (float) ($billing->split_second_non_cash_amount ?? 0)
                );

                continue;
            }

            if (strtolower((string) ($billing->payment_method ?? '')) === 'cash') {
                $paymentMethodTotals['cash'] += $paidAmount;

                continue;
            }

            $this->addPaymentMethodAmount(
                $paymentMethodTotals,
                $this->normalizePaymentMethod($billing->payment_method),
                $paidAmount
            );
        }

        $walkInOrders = Order::query()
            ->whereNull('table_session_id')
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startAt, $endAt): void {
                $query->whereBetween('ordered_at', [$startAt, $endAt])
                    ->orWhere(function ($fallbackQuery) use ($startAt, $endAt): void {
                        $fallbackQuery->whereNull('ordered_at')
                            ->whereBetween('created_at', [$startAt, $endAt]);
                    });
            })
            ->get(['id', 'payment_method', 'payment_mode', 'total']);

        $paidBillingOrderIds = Billing::query()
            ->where('billing_status', 'paid')
            ->whereIn('order_id', $walkInOrders->pluck('id')->filter()->values())
            ->pluck('order_id')
            ->filter()
            ->unique();

        $walkInOrdersWithoutPaidBilling = $walkInOrders
            ->reject(fn (Order $order): bool => $paidBillingOrderIds->contains($order->id));

        foreach ($walkInOrdersWithoutPaidBilling as $order) {
            $paidAmount = (float) ($order->total ?? 0);

            if (strtolower((string) ($order->payment_method ?? '')) === 'cash') {
                $paymentMethodTotals['cash'] += $paidAmount;

                continue;
            }

            $this->addPaymentMethodAmount(
                $paymentMethodTotals,
                $this->normalizePaymentMethod($order->payment_method),
                $paidAmount
            );
        }

        return [
            'has_paid_billings' => $paidBillings->isNotEmpty() || $walkInOrdersWithoutPaidBilling->isNotEmpty(),
            'totals' => $paymentMethodTotals,
        ];
    }

    private function resolveEndDayPrinter(): ?Printer
    {
        $settings = GeneralSetting::instance();
        $configuredPrinterId = (int) ($settings->end_day_receipt_printer_id ?? 0);

        if ($configuredPrinterId > 0) {
            $configuredPrinter = Printer::active()->where('id', $configuredPrinterId)->first();

            if ($configuredPrinter) {
                return $configuredPrinter;
            }
        }

        return Printer::active()->byType('cashier')->first()
            ?? Printer::active()->default()->first()
            ?? Printer::active()->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRecapDataFromHistory(RecapHistory $recapHistory): array
    {
        if (! $recapHistory->end_day) {
            return [
                'selectedStartDatetime' => '-',
                'selectedEndDatetime' => '-',
                'cashierCount' => 0,
                'cashierRevenue' => 0.0,
                'totalPenjualanRokok' => 0.0,
                'totalTax' => 0.0,
                'totalServiceCharge' => 0.0,
                'totalDiscount' => 0.0,
                'totalDownPayment' => 0.0,
                'paymentMethodTotals' => [
                    'cash' => 0.0,
                    'transfer' => 0.0,
                    'debit' => 0.0,
                    'kredit' => 0.0,
                    'qris' => 0.0,
                ],
                'rokokItems' => [],
                'kitchenQtyTotal' => 0,
                'barQtyTotal' => 0,
                'dashboardPreview' => [
                    'total_food' => 0.0,
                    'total_alcohol' => 0.0,
                    'total_beverage' => 0.0,
                    'total_cigarette' => 0.0,
                    'total_breakage' => 0.0,
                    'total_room' => 0.0,
                    'total_ld' => 0.0,
                    'total_penjualan_rokok' => 0.0,
                    'total_kitchen_items' => 0,
                    'total_bar_items' => 0,
                ],
                'cashierTransactions' => [],
            ];
        }

        [$startAt, $endAt] = $this->resolveEndDayWindow($recapHistory->end_day->copy());
        $liveRecapData = $this->buildRecapData($startAt, $endAt, true);

        return array_merge($liveRecapData, [
            'selectedDate' => $recapHistory->end_day->toDateString(),
            'selectedStartDatetime' => $startAt->format('d/m/Y H:i'),
            'selectedEndDatetime' => $endAt->format('d/m/Y H:i'),
            'cashierCount' => (int) $recapHistory->total_transactions,
            'cashierRevenue' => (float) $recapHistory->total_amount,
            'totalPenjualanRokok' => (float) $recapHistory->total_penjualan_rokok,
            'totalFood' => (float) ($recapHistory->total_food ?? 0),
            'totalAlcohol' => (float) ($recapHistory->total_alcohol ?? 0),
            'totalBeverage' => (float) ($recapHistory->total_beverage ?? 0),
            'totalCigarette' => (float) ($recapHistory->total_cigarette ?? 0),
            'totalBreakage' => (float) ($recapHistory->total_breakage ?? 0),
            'totalRoom' => (float) ($recapHistory->total_room ?? 0),
            'totalLd' => (float) ($recapHistory->total_ld ?? 0),
            'totalTax' => (float) $recapHistory->total_tax,
            'totalServiceCharge' => (float) $recapHistory->total_service_charge,
            'totalDiscount' => (float) ($liveRecapData['totalDiscount'] ?? 0),
            'totalDownPayment' => (float) ($liveRecapData['totalDownPayment'] ?? 0),
            'paymentMethodTotals' => [
                'cash' => (float) $recapHistory->total_cash,
                'transfer' => (float) $recapHistory->total_transfer,
                'debit' => (float) $recapHistory->total_debit,
                'kredit' => (float) $recapHistory->total_kredit,
                'qris' => (float) $recapHistory->total_qris,
            ],
            'rokokItems' => $liveRecapData['rokokItems'] ?? [],
            'kitchenQtyTotal' => (int) $recapHistory->total_kitchen_items,
            'barQtyTotal' => (int) $recapHistory->total_bar_items,
            'dashboardPreview' => [
                'total_food' => (float) ($recapHistory->total_food ?? 0),
                'total_alcohol' => (float) ($recapHistory->total_alcohol ?? 0),
                'total_beverage' => (float) ($recapHistory->total_beverage ?? 0),
                'total_cigarette' => (float) ($recapHistory->total_cigarette ?? 0),
                'total_breakage' => (float) ($recapHistory->total_breakage ?? 0),
                'total_room' => (float) ($recapHistory->total_room ?? 0),
                'total_ld' => (float) ($recapHistory->total_ld ?? 0),
                'total_penjualan_rokok' => (float) $recapHistory->total_penjualan_rokok,
                'total_tax' => (float) $recapHistory->total_tax,
                'total_service_charge' => (float) $recapHistory->total_service_charge,
                'total_discount' => (float) ($liveRecapData['totalDiscount'] ?? 0),
                'total_down_payment' => (float) ($liveRecapData['totalDownPayment'] ?? 0),
                'total_cash' => (float) $recapHistory->total_cash,
                'total_transfer' => (float) $recapHistory->total_transfer,
                'total_debit' => (float) $recapHistory->total_debit,
                'total_kredit' => (float) $recapHistory->total_kredit,
                'total_qris' => (float) $recapHistory->total_qris,
                'total_kitchen_items' => (int) $recapHistory->total_kitchen_items,
                'total_bar_items' => (int) $recapHistory->total_bar_items,
                'total_transactions' => (int) $recapHistory->total_transactions,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $recapData
     * @return array<int, array<int, string|int|float>>
     */
    private function buildLiveRecapExportRows(array $recapData): array
    {
        $rows = [
            ['Rekapan End Day'],
            ['Rentang', $recapData['selectedStartDatetime'].' - '.$recapData['selectedEndDatetime']],
            [],
            ['Ringkasan'],
            ['Transaksi Kasir', $recapData['cashierCount']],
            ['Total Penjualan Kasir', $recapData['cashierRevenue']],
            ['Total Food', (float) ($recapData['dashboardPreview']['total_food'] ?? 0)],
            ['Total Alcohol', (float) ($recapData['dashboardPreview']['total_alcohol'] ?? 0)],
            ['Total Beverage', (float) ($recapData['dashboardPreview']['total_beverage'] ?? 0)],
            ['Total Cigarette', (float) ($recapData['dashboardPreview']['total_cigarette'] ?? 0)],
            ['Total Breakage', (float) ($recapData['dashboardPreview']['total_breakage'] ?? 0)],
            ['Total Room', (float) ($recapData['dashboardPreview']['total_room'] ?? 0)],
            ['Total LD', (float) ($recapData['dashboardPreview']['total_ld'] ?? 0)],
            ['Total Pajak', $recapData['totalTax']],
            ['Total Service Charge', $recapData['totalServiceCharge']],
            ['Total Pembayaran Tunai', $recapData['paymentMethodTotals']['cash']],
            ['Total Pembayaran Transfer', $recapData['paymentMethodTotals']['transfer']],
            ['Total Pembayaran Debit', $recapData['paymentMethodTotals']['debit']],
            ['Total Pembayaran Kredit', $recapData['paymentMethodTotals']['kredit']],
            ['Total Pembayaran QRIS', $recapData['paymentMethodTotals']['qris']],
            ['Item Keluar Kitchen', $recapData['kitchenQtyTotal']],
            ['Item Keluar Bar', $recapData['barQtyTotal']],
            [],
            ['Kasir (Harga Ditampilkan)'],
            ['Tanggal & Jam', 'No. Transaksi', 'Customer', 'Metode Pembayaran', 'Qty Item', 'Total'],
        ];

        foreach ($recapData['cashierTransactions'] as $transaction) {
            $rows[] = [
                $transaction['datetime'],
                $transaction['order_number'],
                $transaction['customer_name'],
                $transaction['payment_method'],
                $transaction['items_count'],
                $transaction['total'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Item Keluar Kitchen'];
        $rows[] = ['Tanggal & Jam', 'Order', 'Item', 'Qty'];
        foreach ($recapData['kitchenItems'] as $item) {
            $rows[] = [
                $item['datetime'],
                $item['order_number'],
                $item['item_name'],
                $item['qty'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Item Keluar Bar'];
        $rows[] = ['Tanggal & Jam', 'Order', 'Item', 'Qty'];
        foreach ($recapData['barItems'] as $item) {
            $rows[] = [
                $item['datetime'],
                $item['order_number'],
                $item['item_name'],
                $item['qty'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    private function buildHistoryExportRows(RecapHistory $recapHistory): array
    {
        return [
            ['History Rekapan End Day'],
            ['Tanggal End Day', $recapHistory->end_day?->format('d/m/Y') ?? '-'],
            ['Last Sync', $recapHistory->last_synced_at?->format('d/m/Y H:i') ?? '-'],
            [],
            ['Ringkasan Snapshot'],
            ['Transaksi Kasir', (int) $recapHistory->total_transactions],
            ['Total Penjualan Kasir', (float) $recapHistory->total_amount],
            ['Total Food', (float) ($recapHistory->total_food ?? 0)],
            ['Total Alcohol', (float) ($recapHistory->total_alcohol ?? 0)],
            ['Total Beverage', (float) ($recapHistory->total_beverage ?? 0)],
            ['Total Cigarette', (float) ($recapHistory->total_cigarette ?? 0)],
            ['Total Breakage', (float) ($recapHistory->total_breakage ?? 0)],
            ['Total Room', (float) ($recapHistory->total_room ?? 0)],
            ['Total LD', (float) ($recapHistory->total_ld ?? 0)],
            ['Total Penjualan Rokok', (float) $recapHistory->total_penjualan_rokok],
            ['Total Pajak', (float) $recapHistory->total_tax],
            ['Total Service Charge', (float) $recapHistory->total_service_charge],
            ['Total Pembayaran Tunai', (float) $recapHistory->total_cash],
            ['Total Pembayaran Transfer', (float) $recapHistory->total_transfer],
            ['Total Pembayaran Debit', (float) $recapHistory->total_debit],
            ['Total Pembayaran Kredit', (float) $recapHistory->total_kredit],
            ['Total Pembayaran QRIS', (float) $recapHistory->total_qris],
            ['Item Keluar Kitchen', (int) $recapHistory->total_kitchen_items],
            ['Item Keluar Bar', (int) $recapHistory->total_bar_items],
        ];
    }

    /**
     * @param  array<int, array<int, string|int|float>>  $rows
     */
    private function downloadRows(array $rows, string $filename): BinaryFileResponse
    {
        $export = new class($rows) implements FromArray
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        };

        return Excel::download($export, $filename);
    }

    /**
     * @return array{service_charge: float, tax: float}
     */
    private function calculateOrderChargeTotals(Order $order, GeneralSetting $settings): array
    {
        $serviceChargeBase = 0.0;
        $taxBase = 0.0;
        $taxAndServiceBase = 0.0;

        $orderItems = $order->items
            ->where('status', '!=', 'cancelled')
            ->values();

        $itemsSubtotal = (float) $orderItems->sum(fn ($item) => (float) ($item->subtotal ?? 0));
        $orderNetTotal = max((float) ($order->total ?? 0), 0);
        $ratio = $itemsSubtotal > 0 ? $orderNetTotal / $itemsSubtotal : 0;

        foreach ($orderItems as $orderItem) {
            $itemNetSubtotal = (float) ($orderItem->subtotal ?? 0) * $ratio;
            $includeTax = (bool) ($orderItem->inventoryItem?->include_tax ?? true);
            $includeServiceCharge = (bool) ($orderItem->inventoryItem?->include_service_charge ?? true);

            if ($includeServiceCharge) {
                $serviceChargeBase += $itemNetSubtotal;
            }

            if ($includeTax) {
                $taxBase += $itemNetSubtotal;
            }

            if ($includeTax && $includeServiceCharge) {
                $taxAndServiceBase += $itemNetSubtotal;
            }
        }

        $serviceCharge = round($serviceChargeBase * (((float) $settings->service_charge_percentage) / 100), 2);
        $serviceChargeTaxableAmount = round($taxAndServiceBase * (((float) $settings->service_charge_percentage) / 100), 2);
        $tax = round(($taxBase + $serviceChargeTaxableAmount) * (((float) $settings->tax_percentage) / 100), 2);

        return [
            'service_charge' => $serviceCharge,
            'tax' => $tax,
        ];
    }

    private function formatPaymentMethod(?string $paymentMethod, ?string $paymentMode): string
    {
        if ($paymentMode === 'split') {
            return 'Split Bill';
        }

        return match (strtolower((string) $paymentMethod)) {
            'cash' => 'Tunai',
            'debit' => 'Debit',
            'kredit' => 'Kredit',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'credit-card' => 'Kredit',
            'debit-card' => 'Debit',
            default => filled($paymentMethod) ? strtoupper((string) $paymentMethod) : '-',
        };
    }

    private function normalizePaymentMethod(?string $paymentMethod): ?string
    {
        return match (strtolower(trim((string) $paymentMethod))) {
            'debit', 'debit-card' => 'debit',
            'kredit', 'credit-card' => 'kredit',
            'transfer' => 'transfer',
            'qris' => 'qris',
            default => null,
        };
    }

    /**
     * @param  array{cash: float, transfer: float, debit: float, kredit: float, qris: float}  $paymentMethodTotals
     */
    private function addPaymentMethodAmount(array &$paymentMethodTotals, ?string $method, float $amount): void
    {
        if ($method === null || ! array_key_exists($method, $paymentMethodTotals)) {
            return;
        }

        $paymentMethodTotals[$method] += $amount;
    }

    /**
     * @param  array{date?: string|null, start_datetime?: string|null, end_datetime?: string|null}  $validated
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(array $validated): array
    {
        if (! empty($validated['start_datetime']) && ! empty($validated['end_datetime'])) {
            return [
                Carbon::parse($validated['start_datetime'])->seconds(0),
                Carbon::parse($validated['end_datetime'])->seconds(59),
            ];
        }

        if (! empty($validated['date'])) {
            return $this->resolveEndDayWindow(Carbon::parse($validated['date'], 'Asia/Jakarta'));
        }

        return $this->resolveOperationalWindow();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveOperationalWindow(): array
    {
        $now = now('Asia/Jakarta');
        $anchor = $now->copy()->setTime(9, 0, 0);

        if ($now->lt($anchor)) {
            return [
                $anchor->copy()->subDay(),
                $anchor->copy()->subSecond(),
            ];
        }

        return [
            $anchor,
            $anchor->copy()->addDay()->subSecond(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveEndDayWindow(Carbon $endDay): array
    {
        $startAt = $endDay->copy()->timezone('Asia/Jakarta')->setTime(9, 0, 0);

        return [
            $startAt,
            $startAt->copy()->addDay()->subSecond(),
        ];
    }

    private function isSelectedEndDayClosed(Carbon $startAt): bool
    {
        $selectedEndDay = $startAt->copy()->timezone('Asia/Jakarta')->toDateString();

        return RecapHistory::query()
            ->whereDate('end_day', $selectedEndDay)
            ->exists();
    }
}
