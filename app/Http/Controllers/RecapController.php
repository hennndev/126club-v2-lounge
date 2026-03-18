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
        $recapData = $this->buildRecapData($startAt, $endAt);

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
        ]);

        [$startAt, $endAt] = $this->resolveRange($validated);
        $recapData = $this->buildRecapData($startAt, $endAt);

        return view('recap.close-preview', array_merge($recapData, [
            'printedAt' => now(),
        ]));
    }

    public function printClosePreview(Request $request, PrinterService $printerService): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
        ]);

        [$startAt, $endAt] = $this->resolveRange($validated);
        $recapData = $this->buildRecapData($startAt, $endAt);

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

    private function buildRecapData(Carbon $startAt, Carbon $endAt): array
    {
        $orders = Order::query()
            ->with(['items.inventoryItem', 'tableSession.customer.profile', 'customer.user'])
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
                    'order_status' => (string) $order->status,
                    'billing_status' => (string) ($billing?->billing_status ?? ''),
                    'items' => $orderItems
                        ->map(fn ($item): array => [
                            'name' => (string) ($item->item_name ?? '-'),
                            'quantity' => (int) ($item->quantity ?? 0),
                            'price' => (float) ($item->price ?? 0),
                            'subtotal' => (float) ($item->subtotal ?? 0),
                            'tax_amount' => (float) ($item->tax_amount ?? 0),
                            'service_charge_amount' => (float) ($item->service_charge_amount ?? 0),
                        ])
                        ->values()
                        ->all(),
                    'total' => (float) $order->total,
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

        $dashboardAggregate = Dashboard::query()->find(1);
        $recapHistories = RecapHistory::query()
            ->latest('end_day')
            ->limit(10)
            ->get();

        $paymentMethodTotals = [
            'cash' => (float) ($dashboardAggregate?->total_cash ?? 0),
            'transfer' => (float) ($dashboardAggregate?->total_transfer ?? 0),
            'debit' => (float) ($dashboardAggregate?->total_debit ?? 0),
            'kredit' => (float) ($dashboardAggregate?->total_kredit ?? 0),
            'qris' => (float) ($dashboardAggregate?->total_qris ?? 0),
        ];

        $kitchenItems = KitchenOrderItem::query()
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
                    'item_name' => $item->inventoryItem?->name ?? 'Unknown Item',
                    'qty' => (int) $item->quantity,
                ];
            })
            ->sortBy(fn (array $event) => $event['timestamp'] ?? now())
            ->values();

        $barItems = BarOrderItem::query()
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
                    'item_name' => $item->inventoryItem?->name ?? 'Unknown Item',
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
            'cashierCount' => (int) ($dashboardAggregate?->total_transactions ?? 0),
            'cashierRevenue' => (float) ($dashboardAggregate?->total_amount ?? 0),
            'totalTax' => (float) ($dashboardAggregate?->total_tax ?? 0),
            'totalServiceCharge' => (float) ($dashboardAggregate?->total_service_charge ?? 0),
            'totalCash' => (float) ($dashboardAggregate?->total_cash ?? 0),
            'paymentMethodTotals' => $paymentMethodTotals,
            'kitchenItems' => $kitchenItems,
            'kitchenQtyTotal' => (int) $kitchenItems->sum('qty'),
            'barItems' => $barItems,
            'barQtyTotal' => (int) $barItems->sum('qty'),
            'dashboardPreview' => [
                'total_tax' => (float) ($dashboardAggregate?->total_tax ?? 0),
                'total_service_charge' => (float) ($dashboardAggregate?->total_service_charge ?? 0),
                'total_cash' => (float) ($dashboardAggregate?->total_cash ?? 0),
                'total_transfer' => (float) ($dashboardAggregate?->total_transfer ?? 0),
                'total_debit' => (float) ($dashboardAggregate?->total_debit ?? 0),
                'total_kredit' => (float) ($dashboardAggregate?->total_kredit ?? 0),
                'total_qris' => (float) ($dashboardAggregate?->total_qris ?? 0),
                'total_kitchen_items' => (int) ($dashboardAggregate?->total_kitchen_items ?? 0),
                'total_bar_items' => (int) ($dashboardAggregate?->total_bar_items ?? 0),
                'total_transactions' => (int) ($dashboardAggregate?->total_transactions ?? 0),
            ],
            'recapHistories' => $recapHistories,
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
     * @param  array{transfer: float, debit: float, kredit: float, qris: float}  $paymentMethodTotals
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
            $date = Carbon::parse($validated['date']);

            return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
        }

        return [now()->startOfDay(), now()->endOfDay()];
    }
}
