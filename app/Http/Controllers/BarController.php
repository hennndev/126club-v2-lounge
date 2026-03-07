<?php

namespace App\Http\Controllers;

use App\Models\BarOrder;
use App\Models\BarOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');

        $query = BarOrder::with([
            'customer.user',
            'customer.profile',
            'table.area',
            'items.recipe.inventoryItem',
        ])->orderBy('created_at', 'desc');

        if ($status === 'proses') {
            $query->where('status', 'proses');
        } elseif ($status === 'selesai') {
            $query->where('status', 'selesai');
        } else {
            // default: exclude selesai
            $query->whereIn('status', ['baru', 'proses']);
        }

        $orders = $query->get();

        // Calculate stats
        $stats = [
            'total' => BarOrder::count(),
            'baru' => BarOrder::where('status', 'baru')->count(),
            'proses' => BarOrder::where('status', 'proses')->count(),
            'selesai' => BarOrder::where('status', 'selesai')->count(),
        ];

        return view('bar.index', compact('orders', 'stats'));
    }

    /**
     * Fetch orders as JSON for real-time updates.
     */
    public function fetchOrders(Request $request): JsonResponse
    {
        $status = $request->get('status');

        $query = BarOrder::with([
            'customer.user',
            'customer.profile',
            'table.area',
            'items.recipe.inventoryItem',
        ])->orderBy('created_at', 'desc');

        if ($status === 'proses') {
            $query->where('status', 'proses');
        } elseif ($status === 'selesai') {
            $query->where('status', 'selesai');
        } else {
            // default: exclude selesai
            $query->whereIn('status', ['baru', 'proses']);
        }

        $orders = $query->get()->map(function ($order) {
            return $this->formatOrder($order);
        });

        $stats = [
            'total' => BarOrder::count(),
            'baru' => BarOrder::where('status', 'baru')->count(),
            'proses' => BarOrder::where('status', 'proses')->count(),
            'selesai' => BarOrder::where('status', 'selesai')->count(),
        ];

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    public function toggleItem($itemId): JsonResponse
    {
        $item = BarOrderItem::with('barOrder')->findOrFail($itemId);
        $item->is_completed = ! $item->is_completed;
        $item->save();

        // Update order progress
        $item->barOrder->updateProgress();

        // Refresh the order to get updated data
        $order = BarOrder::with([
            'customer.user',
            'customer.profile',
            'table.area',
            'items.recipe.inventoryItem',
        ])->find($item->bar_order_id);

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

    public function completeAll($orderId): JsonResponse
    {
        $order = BarOrder::with('items')->findOrFail($orderId);

        // Mark all items as completed
        $order->items()->update(['is_completed' => true]);

        // Explicitly set progress and status
        $order->update([
            'progress' => 100,
            'status' => 'selesai',
        ]);

        // Refresh the order to get updated data
        $order = BarOrder::with([
            'customer.user',
            'customer.profile',
            'table.area',
            'items.recipe.inventoryItem',
        ])->find($orderId);

        return response()->json([
            'success' => true,
            'message' => 'All items marked as completed',
            'order' => $this->formatOrder($order),
        ]);
    }

    /**
     * Format order data for JSON response.
     */
    protected function formatOrder(BarOrder $order): array
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
