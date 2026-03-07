<?php

namespace App\Http\Controllers;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index(Request $request)
    {
        $query = KitchenOrder::with(['customer.user', 'customer.profile', 'table.area', 'items.recipe.inventoryItem']);

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['baru', 'proses', 'selesai'])) {
            $query->where('status', $request->status);
        } else {
            // default: exclude selesai
            $query->whereIn('status', ['baru', 'proses']);
        }

        $orders = $query->latest()->get();

        // Calculate stats
        $totalOrders = KitchenOrder::count();
        $baruOrders = KitchenOrder::where('status', 'baru')->count();
        $prosesOrders = KitchenOrder::where('status', 'proses')->count();
        $selesaiOrders = KitchenOrder::where('status', 'selesai')->count();

        return view('kitchen.index', compact('orders', 'totalOrders', 'baruOrders', 'prosesOrders', 'selesaiOrders'));
    }

    /**
     * Fetch orders as JSON for real-time updates.
     */
    public function fetchOrders(Request $request): JsonResponse
    {
        $query = KitchenOrder::with([
            'customer.user',
            'customer.profile',
            'table.area',
            'items.recipe.inventoryItem',
        ])->latest();

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['baru', 'proses', 'selesai'])) {
            $query->where('status', $request->status);
        } else {
            // default: exclude selesai
            $query->whereIn('status', ['baru', 'proses']);
        }

        $orders = $query->get()->map(function ($order) {
            return $this->formatOrder($order);
        });

        $stats = [
            'total' => KitchenOrder::count(),
            'baru' => KitchenOrder::where('status', 'baru')->count(),
            'proses' => KitchenOrder::where('status', 'proses')->count(),
            'selesai' => KitchenOrder::where('status', 'selesai')->count(),
        ];

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    public function toggleItem(KitchenOrderItem $item): JsonResponse
    {
        $item->update(['is_completed' => ! $item->is_completed]);
        $item->kitchenOrder->updateProgress();

        // Refresh the order to get updated data
        $order = KitchenOrder::with([
            'customer.user',
            'customer.profile',
            'table.area',
            'items.recipe.inventoryItem',
        ])->find($item->kitchen_order_id);

        return response()->json([
            'success' => true,
            'message' => 'Item status updated',
            'item' => [
                'id' => $item->id,
                'is_completed' => $item->is_completed,
            ],
            'order' => $this->formatOrder($order),
        ]);
    }

    public function completeAll(KitchenOrder $order): JsonResponse
    {
        $order->items()->update(['is_completed' => true]);
        $order->update([
            'progress' => 100,
            'status' => 'selesai',
        ]);

        // Refresh the order to get updated data
        $order = KitchenOrder::with([
            'customer.user',
            'customer.profile',
            'table.area',
            'items.recipe.inventoryItem',
        ])->find($order->id);

        return response()->json([
            'success' => true,
            'message' => 'Semua item telah diselesaikan!',
            'order' => $this->formatOrder($order),
        ]);
    }

    /**
     * Format order data for JSON response.
     */
    protected function formatOrder(KitchenOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'progress' => $order->progress,
            'created_at' => $order->created_at->format('d M Y H:i'),
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->user?->name ?? 'N/A',
                'phone' => $order->customer->profile?->phone ?? null,
            ] : null,
            'table' => $order->table ? [
                'id' => $order->table->id,
                'table_number' => $order->table->table_number,
                'area' => $order->table->area ? [
                    'id' => $order->table->area->id,
                    'name' => $order->table->area->name,
                ] : null,
            ] : null,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'recipe_id' => $item->bom_recipe_id,
                    'recipe_name' => $item->recipe?->inventoryItem?->name ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'is_completed' => $item->is_completed,
                ];
            }),
        ];
    }
}
