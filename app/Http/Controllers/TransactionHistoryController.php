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
            'items',
            'tableSession.table',
            'tableSession.reservation',
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

        $orders = $query->latest('ordered_at')->paginate(25)->withQueryString();

        $orders->getCollection()->transform(function (Order $order) {
            $hasKitchenItems = $order->items->contains(fn ($item) => $item->preparation_location === 'kitchen');
            $hasBarItems = $order->items->contains(fn ($item) => $item->preparation_location === 'bar');

            $order->setAttribute('print_types', [
                'resmi' => true,
                'kitchen' => $hasKitchenItems,
                'bar' => $hasBarItems,
            ]);

            $order->setAttribute('print_counts', [
                'resmi' => (int) ($order->receipt_print_count ?? 0),
                'kitchen' => (int) ($order->kitchen_print_count ?? 0),
                'bar' => (int) ($order->bar_print_count ?? 0),
            ]);

            return $order;
        });

        $totalOrders = Order::whereNotIn('status', ['cancelled'])->count();
        $todayOrders = Order::whereNotIn('status', ['cancelled'])
            ->whereDate('ordered_at', today())
            ->count();
        $todayRevenue = Order::whereNotIn('status', ['cancelled'])
            ->whereDate('ordered_at', today())
            ->sum('total');
        $totalRevenue = Order::whereNotIn('status', ['cancelled'])->sum('total');

        $activePrinters = Printer::active()->get(['location']);

        $printerLocations = $activePrinters
            ->pluck('location')
            ->filter()
            ->map(fn ($location) => strtolower(trim((string) $location)))
            ->unique()
            ->values()
            ->toArray();

        $hasAnyActivePrinter = $activePrinters->isNotEmpty();

        return view('transaction-history.index', compact(
            'orders',
            'totalOrders',
            'todayOrders',
            'todayRevenue',
            'totalRevenue',
            'printerLocations',
            'hasAnyActivePrinter',
        ));
    }

    public function print(Request $request, Order $order): JsonResponse
    {
        $type = $request->input('type', 'resmi');
        $isReprint = $request->boolean('is_reprint');

        try {
            $order->load(['items', 'tableSession.table', 'tableSession.customer', 'kitchenOrder.items.recipe.inventoryItem', 'kitchenOrder.table', 'barOrder.items.recipe.inventoryItem', 'barOrder.items.inventoryItem', 'barOrder.table']);

            if (! in_array($type, ['resmi', 'kitchen', 'bar'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis cetak tidak valid.',
                ], 422);
            }

            $hasKitchenItems = $order->items->contains(fn ($item) => $item->preparation_location === 'kitchen');
            $hasBarItems = $order->items->contains(fn ($item) => $item->preparation_location === 'bar');

            if ($type === 'kitchen' && ! $hasKitchenItems) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ini tidak memiliki item kitchen.',
                ], 422);
            }

            if ($type === 'bar' && ! $hasBarItems) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ini tidak memiliki item bar.',
                ], 422);
            }

            $counterColumn = match ($type) {
                'kitchen' => 'kitchen_print_count',
                'bar' => 'bar_print_count',
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
                default => 'cashier',
            };

            $printer = Printer::getByLocation($location);

            if (! $printer) {
                $printer = Printer::getDefault();
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
                } else {
                    $this->printerService->printReceipt($order, $printer);
                }
            } else {
                $this->printerService->printReceipt($order, $printer);
            }

            $order->increment($counterColumn);

            $typeLabel = match ($type) {
                'kitchen' => 'Kitchen',
                'bar' => 'Bar',
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
}
