<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WaiterPerformanceController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->get('period', 'today');
        $mode = $request->get('mode', 'individual');
        $waiterId = $request->get('waiter_id');

        $dateRange = $this->getDateRange($period);

        $waiters = User::whereHas('roles', fn ($q) => $q->where('name', 'Waiter/Server'))
            ->whereHas('internalUser', fn ($q) => $q->where('is_active', true))
            ->with(['internalUser.area'])
            ->get();

        $selectedWaiter = null;
        $stats = null;
        $topProducts = collect();
        $recentOrders = collect();
        $allWaitersStats = collect();
        $rank = null;

        if ($mode === 'individual') {
            $selectedWaiter = $waiters->firstWhere('id', $waiterId) ?? $waiters->first();

            if ($selectedWaiter) {
                $ordersQuery = DB::table('orders')
                    ->where('created_by', $selectedWaiter->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

                $totalSales = (clone $ordersQuery)->sum('total');
                $totalTransactions = (clone $ordersQuery)->count();
                $avgPerTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

                // Rank among all waiters
                $allSales = [];
                foreach ($waiters as $w) {
                    $allSales[$w->id] = DB::table('orders')
                        ->where('created_by', $w->id)
                        ->where('status', '!=', 'cancelled')
                        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                        ->sum('total');
                }
                arsort($allSales);
                $rank = array_search($selectedWaiter->id, array_keys($allSales)) + 1;

                $stats = compact('totalSales', 'totalTransactions', 'avgPerTransaction');

                // Top 5 products
                $topProducts = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.created_by', $selectedWaiter->id)
                    ->where('orders.status', '!=', 'cancelled')
                    ->where('order_items.status', '!=', 'cancelled')
                    ->whereBetween('orders.created_at', [$dateRange['start'], $dateRange['end']])
                    ->select('order_items.item_name', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.subtotal) as total_revenue'))
                    ->groupBy('order_items.item_name')
                    ->orderByDesc('total_qty')
                    ->limit(5)
                    ->get();

                // Recent 10 transactions
                $recentOrders = DB::table('orders')
                    ->join('table_sessions', 'orders.table_session_id', '=', 'table_sessions.id')
                    ->join('tables', 'table_sessions.table_id', '=', 'tables.id')
                    ->where('orders.created_by', $selectedWaiter->id)
                    ->where('orders.status', '!=', 'cancelled')
                    ->whereBetween('orders.created_at', [$dateRange['start'], $dateRange['end']])
                    ->select('orders.*', 'tables.table_number')
                    ->orderByDesc('orders.created_at')
                    ->limit(10)
                    ->get();
            }
        } else {
            // All waiters stats
            foreach ($waiters as $w) {
                $q = DB::table('orders')
                    ->where('created_by', $w->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

                $sales = (clone $q)->sum('total');
                $txCount = (clone $q)->count();

                $allWaitersStats->push((object) [
                    'user' => $w,
                    'totalSales' => $sales,
                    'totalTransactions' => $txCount,
                    'avgPerTransaction' => $txCount > 0 ? $sales / $txCount : 0,
                ]);
            }
            $allWaitersStats = $allWaitersStats->sortByDesc('totalSales')->values();
        }

        return view('waiter-performance.index', compact(
            'period', 'mode', 'waiters', 'selectedWaiter', 'stats',
            'topProducts', 'recentOrders', 'allWaitersStats', 'rank', 'dateRange'
        ));
    }

    private function getDateRange(string $period): array
    {
        return match ($period) {
            'week' => [
                'start' => now()->startOfWeek()->toDateTimeString(),
                'end' => now()->endOfWeek()->toDateTimeString(),
            ],
            'month' => [
                'start' => now()->startOfMonth()->toDateTimeString(),
                'end' => now()->endOfMonth()->toDateTimeString(),
            ],
            default => [
                'start' => now()->startOfDay()->toDateTimeString(),
                'end' => now()->endOfDay()->toDateTimeString(),
            ],
        };
    }
}
