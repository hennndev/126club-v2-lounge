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
        ])->whereNotIn('status', ['cancelled']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->search.'%')
                    ->orWhereHas('tableSession.customer', function ($q2) use ($request) {
                        $q2->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $orders = $query->latest('ordered_at')->paginate(25)->withQueryString();

        $totalOrders = Order::whereNotIn('status', ['cancelled'])->count();
        $todayOrders = Order::whereNotIn('status', ['cancelled'])
            ->whereDate('ordered_at', today())
            ->count();
        $todayRevenue = Order::whereNotIn('status', ['cancelled'])
            ->whereDate('ordered_at', today())
            ->sum('total');
        $totalRevenue = Order::whereNotIn('status', ['cancelled'])->sum('total');

        $printerLocations = Printer::active()
            ->get(['location'])
            ->pluck('location')
            ->unique()
            ->values()
            ->toArray();

        return view('transaction-history.index', compact(
            'orders',
            'totalOrders',
            'todayOrders',
            'todayRevenue',
            'totalRevenue',
            'printerLocations'
        ));
    }

    public function print(Request $request, Order $order): JsonResponse
    {
        $type = $request->input('type', 'resmi');

        try {
            $order->load(['items', 'tableSession.table', 'tableSession.customer']);

            $location = match ($type) {
                'kitchen' => 'kitchen',
                'bar' => 'bar',
                default => 'cashier',
            };

            $printer = Printer::getByLocation($location);

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

            $this->printerService->printReceipt($order, $printer);

            $typeLabel = match ($type) {
                'kitchen' => 'Kitchen',
                'bar' => 'Bar',
                'checker' => 'Checker Meja',
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
