<?php

namespace App\Http\Controllers;

use App\Models\BarOrderItem;
use App\Models\Billing;
use App\Models\Dashboard;
use App\Models\InventoryItem;
use App\Models\KitchenOrderItem;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Services\DashboardSyncService;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();

        // --- Revenue & Transactions (paid billings today) ---
        $todayBillings = Billing::whereDate('updated_at', $today)
            ->where('billing_status', 'paid')
            ->where(function ($query) {
                $query->where('is_booking', true)
                    ->orWhere('is_walk_in', true);
            });

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

        // --- Dashboard aggregate totals ---
        $dashboardAggregate = Dashboard::query()->find(1);
        $dashboardTotalTax = (float) ($dashboardAggregate?->total_tax ?? 0);
        $dashboardTotalServiceCharge = (float) ($dashboardAggregate?->total_service_charge ?? 0);
        $dashboardTotalTransfer = (float) ($dashboardAggregate?->total_transfer ?? 0);
        $dashboardTotalDebit = (float) ($dashboardAggregate?->total_debit ?? 0);
        $dashboardTotalKredit = (float) ($dashboardAggregate?->total_kredit ?? 0);
        $dashboardTotalQris = (float) ($dashboardAggregate?->total_qris ?? 0);

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
            'dashboardTotalTax',
            'dashboardTotalServiceCharge',
            'dashboardTotalTransfer',
            'dashboardTotalDebit',
            'dashboardTotalKredit',
            'dashboardTotalQris',
        ));
    }

    public function syncToday(DashboardSyncService $dashboardSyncService): RedirectResponse
    {
        $dashboardSyncService->sync();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Dashboard berhasil di-sync (hari ini).');
    }
}
