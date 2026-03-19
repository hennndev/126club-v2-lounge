<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Printer;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    public function __construct(
        protected PrinterService $printerService
    ) {}

    public function index(Request $request)
    {
        $query = Order::with([
            'items.inventoryItem.printers',
            'tableSession.table',
            'tableSession.reservation',
            'tableSession.billing',
            'tableSession.customer.profile',
            'customer.user',
        ])->whereNotIn('status', ['cancelled']);

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
                    ],
                ];
            })
            ->toArray();

        $totalOrders = Order::whereNotIn('status', ['cancelled'])->count();
        $todayOrders = Order::whereNotIn('status', ['cancelled'])
            ->whereDate('ordered_at', today())
            ->count();
        $todayRevenue = Order::whereNotIn('status', ['cancelled'])
            ->whereDate('ordered_at', today())
            ->sum('total');
        $totalRevenue = Order::whereNotIn('status', ['cancelled'])->sum('total');

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

        return view('transaction-history.index', compact(
            'orders',
            'totalOrders',
            'todayOrders',
            'todayRevenue',
            'totalRevenue',
            'printerLocations',
            'hasAnyActivePrinter',
            'activePrinterOptions',
            'orderPrintPayloads',
            'orderDetailPayloads',
            'perPage',
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
                $this->printerService->printReceipt($order, $printer);
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
