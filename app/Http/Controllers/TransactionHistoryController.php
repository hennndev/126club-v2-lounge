<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\CustomerUser;
use App\Models\Order;
use App\Models\Printer;
use App\Models\TableReservation;
use App\Services\AccurateService;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionHistoryController extends Controller
{
    public function __construct(
        protected PrinterService $printerService,
        protected AccurateService $accurateService
    ) {}

    public function index(Request $request)
    {
        $transactionMode = $request->get('transaction_mode') === 'walk_in' ? 'walk_in' : 'all';
        $dateFrom = $request->filled('date_from') ? $request->date('date_from')->toDateString() : null;
        $dateTo = $request->filled('date_to') ? $request->date('date_to')->toDateString() : null;

        $query = Order::with([
            'items.inventoryItem.printers',
            'tableSession.table',
            'tableSession.reservation',
            'tableSession.billing',
            'tableSession.customer.profile',
            'customer.user.profile',
            'billing',
            'customer.user',
        ])->whereNotIn('status', ['cancelled']);

        if ($transactionMode === 'walk_in') {
            $query->whereNull('table_session_id');
        }

        if ($dateFrom) {
            $query->whereDate('ordered_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('ordered_at', '<=', $dateTo);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->search.'%')
                    ->orWhereHas('tableSession.customer', function ($q2) use ($request) {
                        $q2->where('name', 'like', '%'.$request->search.'%');
                    })
                    ->orWhereHas('customer.user', function ($q2) use ($request) {
                        $q2->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50, 100]) ? (int) $request->get('per_page') : 25;
        $orders = $query->latest('ordered_at')->paginate($perPage)->withQueryString();

        $orders->getCollection()->transform(function (Order $order) {
            $assignedPrinterTypes = $this->resolveOrderAssignedPrinterTypes($order);
            $hasKitchenItems = $assignedPrinterTypes->contains('kitchen');
            $hasBarItems = $assignedPrinterTypes->contains('bar');
            $hasCheckerItems = $assignedPrinterTypes->contains('checker');

            $order->setAttribute('print_types', [
                'resmi' => true,
                'kitchen' => $hasKitchenItems,
                'bar' => $hasBarItems,
                'checker' => $hasCheckerItems,
            ]);

            $order->setAttribute('print_counts', [
                'resmi' => (int) ($order->receipt_print_count ?? 0),
                'kitchen' => (int) ($order->kitchen_print_count ?? 0),
                'bar' => (int) ($order->bar_print_count ?? 0),
                'checker' => (int) ($order->checker_print_count ?? 0),
            ]);

            return $order;
        });

        $orderPrintPayloads = $orders->getCollection()
            ->mapWithKeys(function (Order $order) {
                // Use order_number directly (already has prefix)
                $displayId = $order->order_number;
                $customerName = $order->tableSession?->customer?->name ?? $order->customer?->user?->name;

                return [
                    $order->id => [
                        'id' => $order->id,
                        'displayId' => $displayId,
                        'total' => 'Rp '.number_format((float) $order->total, 0, ',', '.'),
                        'customer' => $customerName ?? 'Walk-in',
                        'time' => $order->ordered_at?->format('H:i') ?? '—',
                        'printTypes' => $order->print_types,
                        'printCounts' => $order->print_counts,
                    ],
                ];
            })
            ->toArray();

        $orderDetailPayloads = $orders->getCollection()
            ->mapWithKeys(function (Order $order) {
                // Use order_number directly (already has prefix)
                $displayId = $order->order_number;
                $customerName = $order->tableSession?->customer?->name ?? $order->customer?->user?->name;
                $tableName = $order->tableSession?->table?->table_number;
                $areaName = $order->tableSession?->table?->area?->name;
                $taxTotal = $order->items->sum(fn ($item) => (float) $item->tax_amount);
                $serviceChargeTotal = $order->items->sum(fn ($item) => (float) $item->service_charge_amount);
                $billing = $order->billing ?? $order->tableSession?->billing;
                $paymentModeLabel = strtoupper((string) ($billing?->payment_mode ?? 'normal'));
                $paymentMethodDisplay = $paymentModeLabel === 'SPLIT'
                    ? 'SPLIT'
                    : strtoupper((string) ($billing?->payment_method ?? 'cash'));

                return [
                    $order->id => [
                        'id' => $order->id,
                        'displayId' => $displayId,
                        'customer' => $customerName ?? 'Walk-in',
                        'time' => $order->ordered_at?->format('d M Y H:i') ?? '—',
                        'table' => $tableName ? trim(($areaName ? $areaName.' ' : '').$tableName) : 'Walk-in',
                        'total' => 'Rp '.number_format((float) $order->total, 0, ',', '.'),
                        'items' => $order->items->map(fn ($item) => [
                            'name' => $item->item_name,
                            'qty' => (int) $item->quantity,
                            'subtotal' => 'Rp '.number_format((float) $item->subtotal, 0, ',', '.'),
                        ])->values(),
                        'taxTotal' => $taxTotal,
                        'taxTotalFormatted' => 'Rp '.number_format($taxTotal, 0, ',', '.'),
                        'serviceChargeTotal' => $serviceChargeTotal,
                        'serviceChargeTotalFormatted' => 'Rp '.number_format($serviceChargeTotal, 0, ',', '.'),
                        'billing' => $billing ? [
                            'id' => (int) $billing->id,
                            'billingStatus' => (string) ($billing->billing_status ?? '-'),
                            'paymentMode' => (string) ($billing->payment_mode ?? 'normal'),
                            'paymentMethod' => (string) ($billing->payment_method ?? 'cash'),
                            'paymentMethodDisplay' => $paymentMethodDisplay,
                            'paymentReferenceNumber' => (string) ($billing->payment_reference_number ?? ''),
                            'splitCashAmount' => (float) ($billing->split_cash_amount ?? 0),
                            'splitNonCashAmount' => (float) ($billing->split_debit_amount ?? 0),
                            'splitNonCashMethod' => (string) ($billing->split_non_cash_method ?? ''),
                            'splitNonCashReferenceNumber' => (string) ($billing->split_non_cash_reference_number ?? ''),
                            'splitSecondNonCashAmount' => (float) ($billing->split_second_non_cash_amount ?? 0),
                            'splitSecondNonCashMethod' => (string) ($billing->split_second_non_cash_method ?? ''),
                            'splitSecondNonCashReferenceNumber' => (string) ($billing->split_second_non_cash_reference_number ?? ''),
                            'grandTotal' => (float) ($billing->grand_total ?? $order->total),
                            'grandTotalFormatted' => 'Rp '.number_format((float) ($billing->grand_total ?? $order->total), 0, ',', '.'),
                            'paidAmount' => (float) ($billing->paid_amount ?? $order->total),
                            'paidAmountFormatted' => 'Rp '.number_format((float) ($billing->paid_amount ?? $order->total), 0, ',', '.'),
                            'transactionCode' => (string) ($billing->transaction_code ?? '-'),
                            'accurateSoNumber' => (string) ($billing->accurate_so_number ?? $order->accurate_so_number ?? ''),
                            'accurateInvNumber' => (string) ($billing->accurate_inv_number ?? $order->accurate_inv_number ?? ''),
                            'errorMessage' => (string) ($billing->error_message ?? ''),
                            'updatePaymentUrl' => route('admin.transaction-history.update-payment', $order),
                        ] : null,
                    ],
                ];
            })
            ->toArray();

        $statsQuery = Order::query()->whereNotIn('status', ['cancelled']);

        if ($transactionMode === 'walk_in') {
            $statsQuery->whereNull('table_session_id');
        }

        if ($dateFrom) {
            $statsQuery->whereDate('ordered_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $statsQuery->whereDate('ordered_at', '<=', $dateTo);
        }

        $totalOrders = (clone $statsQuery)->count();
        $todayOrders = (clone $statsQuery)
            ->whereDate('ordered_at', today())
            ->count();
        $todayRevenue = (clone $statsQuery)
            ->whereDate('ordered_at', today())
            ->sum('total');
        $totalRevenue = (clone $statsQuery)->sum('total');
        $averageOrderTotal = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $todayBookingDownPayment = (float) TableReservation::query()
            ->whereDate('reservation_date', today())
            ->whereNotIn('status', ['cancelled', 'rejected', 'force_closed'])
            ->sum('down_payment_amount');

        $activePrinters = Printer::active()->get(['id', 'name', 'location', 'printer_type']);

        $printerLocations = $activePrinters
            ->pluck('location')
            ->filter()
            ->map(fn ($location) => strtolower(trim((string) $location)))
            ->unique()
            ->values()
            ->toArray();

        $hasAnyActivePrinter = $activePrinters->isNotEmpty();

        $activePrinterOptions = $activePrinters
            ->map(fn (Printer $printer): array => [
                'id' => (int) $printer->id,
                'name' => (string) $printer->name,
                'location' => (string) ($printer->location ?? '-'),
                'printer_type' => (string) ($printer->printer_type ?? '-'),
            ])
            ->values()
            ->toArray();

        $viewName = $transactionMode === 'walk_in' ? 'transaction-history.walk-in.index' : 'transaction-history.index';

        return view($viewName, compact(
            'orders',
            'totalOrders',
            'todayOrders',
            'todayRevenue',
            'todayBookingDownPayment',
            'totalRevenue',
            'printerLocations',
            'hasAnyActivePrinter',
            'activePrinterOptions',
            'orderPrintPayloads',
            'orderDetailPayloads',
            'perPage',
            'transactionMode',
            'averageOrderTotal',
        ));
    }

    public function print(Request $request, Order $order): JsonResponse
    {
        $type = $request->input('type', 'resmi');
        $isReprint = $request->boolean('is_reprint');
        $selectedPrinterId = (int) $request->input('printer_id', 0);

        try {
            $order->load([
                'items.inventoryItem.printers',
                'tableSession.table',
                'tableSession.customer',
                'kitchenOrder.items.inventoryItem',
                'kitchenOrder.table',
                'barOrder.items.inventoryItem',
                'barOrder.table',
            ]);

            if (! in_array($type, ['resmi', 'kitchen', 'bar', 'checker'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis cetak tidak valid.',
                ], 422);
            }

            $counterColumn = match ($type) {
                'kitchen' => 'kitchen_print_count',
                'bar' => 'bar_print_count',
                'checker' => 'checker_print_count',
                default => 'receipt_print_count',
            };

            $currentCount = (int) ($order->{$counterColumn} ?? 0);

            if ($currentCount > 0 && ! $isReprint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dokumen ini sudah pernah dicetak. Silakan otorisasi kode harian untuk cetak ulang.',
                ], 422);
            }

            $location = match ($type) {
                'kitchen' => 'kitchen',
                'bar' => 'bar',
                'checker' => 'checker',
                default => 'cashier',
            };

            if ($selectedPrinterId > 0) {
                $printer = Printer::active()->find($selectedPrinterId);
            } else {
                if ($type === 'resmi') {
                    $printer = Printer::getForService('cashier');
                } else {
                    $printer = Printer::getByLocation($location);

                    if (! $printer) {
                        $printer = Printer::getDefault();
                    }
                }
            }

            if (! $printer) {
                $locationLabel = match ($location) {
                    'kitchen' => 'Kitchen',
                    'bar' => 'Bar',
                    default => 'Kasir',
                };

                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada printer aktif untuk lokasi {$locationLabel}.",
                ], 400);
            }

            if ($type === 'kitchen') {
                if ($order->kitchenOrder) {
                    $this->printerService->printKitchenTicket($order->kitchenOrder, $printer);
                } else {
                    $this->printerService->printReceipt($order, $printer);
                }
            } elseif ($type === 'bar') {
                if ($order->barOrder) {
                    $this->printerService->printBarTicket($order->barOrder, $printer);
                } elseif ($order->kitchenOrder) {
                    $this->printerService->printBarTicket($order->kitchenOrder, $printer);
                } else {
                    $this->printerService->printReceipt($order, $printer);
                }
            } elseif ($type === 'checker') {
                $printed = false;

                if ($order->kitchenOrder) {
                    $this->printerService->printCheckerTicket($order->kitchenOrder, $printer);
                    $printed = true;
                }

                if ($order->barOrder) {
                    $this->printerService->printCheckerTicket($order->barOrder, $printer);
                    $printed = true;
                }

                if (! $printed) {
                    $this->printerService->printReceipt($order, $printer);
                }
            } else {
                $billing = Billing::query()
                    ->where('order_id', $order->id)
                    ->latest('id')
                    ->first();

                if ($billing && ! $order->table_session_id && (bool) $billing->is_walk_in) {
                    $this->printerService->printWalkInBillingReceipt($order, $billing, $printer);
                } else {
                    $this->printerService->printReceipt($order, $printer);
                }
            }

            $order->increment($counterColumn);

            $typeLabel = match ($type) {
                'kitchen' => 'Kitchen',
                'bar' => 'Bar',
                'checker' => 'Checker',
                default => 'Struk Resmi',
            };

            return response()->json([
                'success' => true,
                'message' => "Cetak {$typeLabel} berhasil dikirim ke printer.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencetak: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updatePayment(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'payment_mode' => 'required|in:normal,split',
            'payment_method' => 'required_if:payment_mode,normal|nullable|in:cash,kredit,debit,qris,transfer',
            'payment_reference_number' => 'nullable|string|max:100',
            'split_cash_amount' => 'nullable|numeric|min:0',
            'split_non_cash_amount' => 'nullable|numeric|min:0',
            'split_non_cash_method' => 'nullable|in:debit,kredit,qris,transfer,ewallet,lainnya',
            'split_non_cash_reference_number' => 'nullable|string|max:100',
            'split_second_non_cash_amount' => 'nullable|numeric|min:0',
            'split_second_non_cash_method' => 'nullable|in:debit,kredit,qris,transfer,ewallet,lainnya',
            'split_second_non_cash_reference_number' => 'nullable|string|max:100',
        ]);

        $billing = $order->billing()->first() ?? $order->tableSession?->billing;

        if (! $billing) {
            return response()->json([
                'success' => false,
                'message' => 'Billing tidak ditemukan.',
            ], 404);
        }

        if ($billing->billing_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment hanya bisa diedit untuk billing yang sudah dibayar.',
            ], 422);
        }

        $paymentMode = (string) $validated['payment_mode'];
        $paymentMethod = null;
        $paymentReferenceNumber = null;
        $splitCashAmount = null;
        $splitDebitAmount = null;
        $splitNonCashMethod = null;
        $splitNonCashReferenceNumber = null;
        $splitSecondNonCashAmount = null;
        $splitSecondNonCashMethod = null;
        $splitSecondNonCashReferenceNumber = null;

        if ($paymentMode === 'normal') {
            $paymentMethod = (string) $validated['payment_method'];
            $paymentReferenceNumber = $paymentMethod === 'cash'
                ? null
                : ((string) ($validated['payment_reference_number'] ?? ''));

            if ($paymentMethod !== 'cash' && blank($paymentReferenceNumber)) {
                throw ValidationException::withMessages([
                    'payment_reference_number' => 'Nomor referensi pembayaran non-cash wajib diisi.',
                ]);
            }
        }

        if ($paymentMode === 'split') {
            $splitCashAmount = (float) ($validated['split_cash_amount'] ?? 0);
            $splitDebitAmount = (float) ($validated['split_non_cash_amount'] ?? 0);
            $splitNonCashMethod = $validated['split_non_cash_method'] ?? null;
            $splitNonCashReferenceNumber = $validated['split_non_cash_reference_number'] ?? null;
            $splitSecondNonCashAmount = (float) ($validated['split_second_non_cash_amount'] ?? 0);
            $splitSecondNonCashMethod = $validated['split_second_non_cash_method'] ?? null;
            $splitSecondNonCashReferenceNumber = $validated['split_second_non_cash_reference_number'] ?? null;

            $requiresReferenceNumber = static function (?string $method): bool {
                $normalizedMethod = strtolower(trim((string) $method));

                return $normalizedMethod !== '' && ! in_array($normalizedMethod, ['cash', 'tunai'], true);
            };

            $grandTotal = round((float) $billing->grand_total, 2);
            $splitTotal = round($splitCashAmount + $splitDebitAmount + $splitSecondNonCashAmount, 2);

            $activeNonCashCount = collect([
                ['amount' => $splitDebitAmount, 'method' => $splitNonCashMethod, 'reference' => $splitNonCashReferenceNumber],
                ['amount' => $splitSecondNonCashAmount, 'method' => $splitSecondNonCashMethod, 'reference' => $splitSecondNonCashReferenceNumber],
            ])->filter(fn (array $entry): bool => (float) $entry['amount'] > 0)->count();

            $hasCash = $splitCashAmount > 0;

            if (! $hasCash && $activeNonCashCount < 2) {
                throw ValidationException::withMessages([
                    'split_total' => 'Untuk split non-cash + non-cash, isi dua nominal non-cash lebih dari 0.',
                ]);
            }

            if ($hasCash && $activeNonCashCount < 1) {
                throw ValidationException::withMessages([
                    'split_total' => 'Untuk split cash + non-cash, minimal satu nominal non-cash harus lebih dari 0.',
                ]);
            }

            if ($activeNonCashCount === 0) {
                throw ValidationException::withMessages([
                    'split_total' => 'Split bill memerlukan minimal satu pembayaran non-cash.',
                ]);
            }

            if (abs($splitTotal - $grandTotal) > 0.01) {
                throw ValidationException::withMessages([
                    'split_total' => 'Total pembayaran split harus sama dengan grand total.',
                ]);
            }

            if ($splitDebitAmount > 0 && blank($splitNonCashMethod)) {
                throw ValidationException::withMessages([
                    'split_non_cash_method' => 'Metode non-cash pertama untuk split bill wajib dipilih.',
                ]);
            }

            if ($splitDebitAmount > 0 && $requiresReferenceNumber($splitNonCashMethod) && blank($splitNonCashReferenceNumber)) {
                throw ValidationException::withMessages([
                    'split_non_cash_reference_number' => 'Nomor referensi non-cash pertama untuk split bill wajib diisi.',
                ]);
            }

            if ($splitSecondNonCashAmount > 0 && blank($splitSecondNonCashMethod)) {
                throw ValidationException::withMessages([
                    'split_second_non_cash_method' => 'Metode non-cash kedua untuk split bill wajib dipilih.',
                ]);
            }

            if ($splitSecondNonCashAmount > 0 && $requiresReferenceNumber($splitSecondNonCashMethod) && blank($splitSecondNonCashReferenceNumber)) {
                throw ValidationException::withMessages([
                    'split_second_non_cash_reference_number' => 'Nomor referensi non-cash kedua untuk split bill wajib diisi.',
                ]);
            }
        }

        $billing->update([
            'payment_mode' => $paymentMode,
            'payment_method' => $paymentMethod,
            'payment_reference_number' => $paymentReferenceNumber,
            'split_cash_amount' => $splitCashAmount,
            'split_debit_amount' => $splitDebitAmount,
            'split_non_cash_method' => $splitNonCashMethod,
            'split_non_cash_reference_number' => $splitNonCashReferenceNumber,
            'split_second_non_cash_amount' => $splitSecondNonCashAmount,
            'split_second_non_cash_method' => $splitSecondNonCashMethod,
            'split_second_non_cash_reference_number' => $splitSecondNonCashReferenceNumber,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil diperbarui.',
        ]);
    }

    public function reSyncAccurate(Order $order)
    {
        $order->loadMissing([
            'items.inventoryItem',
            'customer.user.profile',
            'billing',
            'tableSession.billing',
        ]);

        $billing = $order->billing ?? $order->tableSession?->billing;

        if (! $billing) {
            return back()->with('error', 'Billing tidak ditemukan untuk transaksi ini.');
        }

        if (($billing->accurate_so_number || $order->accurate_so_number) && ($billing->accurate_inv_number || $order->accurate_inv_number)) {
            return back()->with('success', 'SO dan Invoice Accurate sudah tersedia.');
        }

        $this->pushOrderToAccurate($order, $billing);

        $billing->refresh();
        $order->refresh();

        $soNumber = $billing->accurate_so_number ?: $order->accurate_so_number;
        $invNumber = $billing->accurate_inv_number ?: $order->accurate_inv_number;

        if (! $soNumber || ! $invNumber) {
            return back()->with('error', $billing->error_message ?: 'Re-sync ke Accurate gagal. Silakan coba lagi.');
        }

        return back()->with('success', 'Re-sync Accurate berhasil.');
    }

    protected function pushOrderToAccurate(Order $order, $billing): void
    {
        try {
            $order->loadMissing([
                'items.inventoryItem',
                'customer.user.profile',
            ]);

            $customerUser = $order->customer;

            if (! $customerUser) {
                $billing->update([
                    'error_message' => 'Customer transaksi tidak ditemukan untuk sinkronisasi Accurate.',
                ]);

                return;
            }

            $customerNo = $this->ensureAccurateCustomer($customerUser);

            if (! $customerNo) {
                $billing->update([
                    'error_message' => 'Customer Accurate tidak ditemukan untuk transaksi ini.',
                ]);

                return;
            }

            $transDate = now()->format('d/m/Y');
            $warehouseName = config('accurate.stock_warehouse_name');
            $taxAmount = (float) ($billing->tax ?? 0);
            $serviceChargeAmount = (float) ($billing->service_charge ?? 0);

            $detailItem = $order->items
                ->groupBy('inventory_item_id')
                ->map(function ($group) use ($warehouseName) {
                    $first = $group->first();

                    return [
                        'itemNo' => $first->inventoryItem?->code ?? $first->item_code,
                        'quantity' => $group->sum('quantity'),
                        'unitPrice' => (float) $first->price,
                        'discountPercent' => 0,
                        'warehouseName' => $warehouseName,
                    ];
                })
                ->values()
                ->toArray();

            if (empty($detailItem)) {
                $billing->update([
                    'error_message' => 'Item order tidak ditemukan untuk dikirim ke Accurate.',
                ]);

                return;
            }

            $soBasePayload = [
                'customerNo' => $customerNo,
                'transDate' => $transDate,
                'memo' => 'Walk-in POS — '.$order->order_number,
                'detailItem' => $detailItem,
            ];

            if ($serviceChargeAmount > 0) {
                $soBasePayload['detailItem'][] = [
                    'itemNo' => 'SERVICE-CHARGE',
                    'quantity' => 1,
                    'unitPrice' => $serviceChargeAmount,
                ];
            }

            if ($taxAmount > 0) {
                $soBasePayload['detailExpense'][] = [
                    'accountNo' => '210201',
                    'expenseAmount' => $taxAmount,
                    'expenseName' => 'PB 1',
                ];
            }

            $soNumber = null;
            $maxAttempts = 3;
            $soPrefix = 'ROOM-WALKIN-'.now()->format('Ymd').'-';

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $randomNumber = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                $soNumberWithPrefix = $soPrefix.$randomNumber;

                try {
                    $this->accurateService->saveSalesOrder(
                        array_merge($soBasePayload, ['number' => $soNumberWithPrefix])
                    );
                    $soNumber = $soNumberWithPrefix;
                    break;
                } catch (\Exception $e) {
                    $isDuplicate = str_contains($e->getMessage(), 'Sudah ada data');

                    if (! $isDuplicate || $attempt === $maxAttempts) {
                        throw $e;
                    }
                }
            }

            $invPayload = [
                'customerNo' => $customerNo,
                'transDate' => $transDate,
                'memo' => 'Walk-in POS — '.$order->order_number,
                'number' => $soNumber,
                'detailItem' => array_map(
                    fn (array $item): array => array_merge($item, ['salesOrderNumber' => $soNumber]),
                    $detailItem
                ),
            ];

            if ($taxAmount > 0) {
                $invPayload['detailExpense'][] = [
                    'accountNo' => '210201',
                    'expenseAmount' => $taxAmount,
                    'expenseName' => 'PB 1',
                ];
            }

            if ($serviceChargeAmount > 0) {
                $invPayload['detailItem'][] = [
                    'itemNo' => 'SERVICE-CHARGE',
                    'unitPrice' => $serviceChargeAmount,
                    'salesOrderNumber' => $soNumber,
                ];
            }

            $invResult = $this->accurateService->saveSalesInvoice($invPayload);
            $invNumber = $invResult['r']['number'] ?? $invResult['d']['number'] ?? $soNumber;

            $order->update([
                'accurate_so_number' => $soNumber,
                'accurate_inv_number' => $invNumber,
            ]);

            $billing->update([
                'accurate_so_number' => $soNumber,
                'accurate_inv_number' => $invNumber,
                'error_message' => null,
            ]);
        } catch (\Exception $e) {
            $billing->update([
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Accurate Billing Sync: FAILED', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function ensureAccurateCustomer(CustomerUser $customerUser): ?string
    {
        $customerUser->loadMissing(['user', 'profile']);

        if ($customerUser->customer_code) {
            return $customerUser->customer_code;
        }

        $user = $customerUser->user;

        if (! $user) {
            return null;
        }

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        $response = $this->accurateService->saveCustomer($payload);
        $accurateId = $response['r']['id'] ?? $response['d']['id'] ?? null;
        $customerNo = $response['r']['customerNo'] ?? $response['d']['customerNo'] ?? null;

        if (! $customerNo) {
            throw new \RuntimeException('Accurate customer number was not returned.');
        }

        $customerUser->update([
            'accurate_id' => $accurateId,
            'customer_code' => $customerNo,
        ]);

        return $customerNo;
    }

    protected function resolveOrderAssignedPrinterTypes(Order $order): \Illuminate\Support\Collection
    {
        return $order->items
            ->flatMap(function ($item) {
                return $item->inventoryItem?->printers
                    ?->filter(fn (Printer $printer): bool => $printer->is_active)
                    ->map(function (Printer $printer): ?string {
                        $type = strtolower(trim((string) $printer->printer_type));

                        if (in_array($type, ['kitchen', 'bar', 'cashier', 'checker'], true)) {
                            return $type;
                        }

                        $location = strtolower(trim((string) $printer->location));

                        return in_array($location, ['kitchen', 'bar', 'cashier', 'checker'], true) ? $location : null;
                    })
                    ->filter()
                    ->values()
                    ?? collect();
            })
            ->unique()
            ->values();
    }
}
