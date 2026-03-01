<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionCheckerController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');

        $query = Order::with([
            'items',
            'tableSession.table',
            'tableSession.customer.profile',
        ])->whereNotIn('status', ['cancelled']);

        if ($tab === 'proses') {
            $query->whereIn('status', ['pending', 'preparing', 'ready']);
        } elseif ($tab === 'selesai') {
            $query->where('status', 'completed');
        }

        $orders = $query->latest('ordered_at')->get();

        $totalOrders = Order::whereNotIn('status', ['cancelled'])->count();
        $baruOrders = Order::where('status', 'pending')->count();
        $prosesOrders = Order::whereIn('status', ['preparing', 'ready'])->count();
        $selesaiOrders = Order::where('status', 'completed')->count();

        return view('transaction-checker.index', compact(
            'orders',
            'tab',
            'totalOrders',
            'baruOrders',
            'prosesOrders',
            'selesaiOrders'
        ));
    }

    public function checkItem(OrderItem $item): JsonResponse
    {
        $item->update([
            'status' => 'served',
            'served_at' => now(),
        ]);

        $item->order->updateStatus();

        $order = Order::with('items')->find($item->order_id);
        $servedCount = $order->items->where('status', 'served')->count();
        $totalCount = $order->items->where('status', '!=', 'cancelled')->count();

        return response()->json([
            'success' => true,
            'message' => 'Item ditandai sebagai selesai.',
            'order_status' => $order->status,
            'served_count' => $servedCount,
            'total_count' => $totalCount,
        ]);
    }

    public function checkAll(Order $order): JsonResponse
    {
        $order->items()
            ->whereNotIn('status', ['cancelled', 'served'])
            ->update([
                'status' => 'served',
                'served_at' => now(),
            ]);

        $order->updateStatus();

        return response()->json([
            'success' => true,
            'message' => 'Semua item ditandai sebagai selesai.',
            'order_status' => $order->fresh()->status,
        ]);
    }
}
