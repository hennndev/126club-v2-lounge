<?php

namespace App\Http\Controllers;

use App\Models\BarOrderItem;
use App\Models\Billing;
use App\Models\InventoryItem;
use App\Models\KitchenOrderItem;
use App\Models\Tabel;
use App\Models\TableReservation;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();

        // --- Revenue & Transactions (paid billings today) ---
        $todayBillings = Billing::whereDate('created_at', $today)
            ->where('billing_status', 'paid');

        $revenueToday = (clone $todayBillings)->sum('grand_total');
        $transactionsToday = (clone $todayBillings)->count();

        // Items sold today (bar + kitchen orders)
        $barItemsSold = BarOrderItem::whereHas(
            'barOrder',
            fn ($q) => $q->whereDate('created_at', $today)
        )->sum('quantity');

        $kitchenItemsSold = KitchenOrderItem::whereHas(
            'kitchenOrder',
            fn ($q) => $q->whereDate('created_at', $today)
        )->sum('quantity');

        $itemsSoldToday = $barItemsSold + $kitchenItemsSold;

        // --- Bookings ---
        $bookingPending = TableReservation::where('status', 'pending')->count();
        $bookingConfirmed = TableReservation::where('status', 'confirmed')->count();
        $bookingCompleted = TableReservation::where('status', 'completed')->whereDate('updated_at', $today)->count();

        // --- Tables ---
        $totalTables = Tabel::where('is_active', true)->count();
        $availableTables = Tabel::where('is_active', true)->where('status', 'available')->count();

        // --- Inventory ---
        $totalProducts = InventoryItem::count();
        $lowStockCount = InventoryItem::whereColumn('stock_quantity', '<=', 'threshold')->where('stock_quantity', '>', 0)->count();
        $outOfStockCount = InventoryItem::where('stock_quantity', 0)->count();

        return view('dashboard', compact(
            'revenueToday',
            'transactionsToday',
            'itemsSoldToday',
            'bookingPending',
            'bookingConfirmed',
            'bookingCompleted',
            'totalTables',
            'availableTables',
            'totalProducts',
            'lowStockCount',
            'outOfStockCount',
        ));
    }
}
