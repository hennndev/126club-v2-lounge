<?php

namespace App\Http\Controllers;

use App\Models\BarOrderItem;
use App\Models\Billing;
use App\Models\Dashboard;
use App\Models\InventoryItem;
use App\Models\KitchenOrderItem;
use App\Models\RecapHistory;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Services\DashboardSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        [$windowStart, $windowEnd] = $this->resolveOperationalWindow();
        $lastCloseAt = RecapHistory::query()->latest('created_at')->value('created_at');

        // --- Revenue & Transactions (paid billings today) ---
        $todayBillings = Billing::query()
            ->where('billing_status', 'paid')
            ->where(function ($query) {
                $query->where('is_booking', true)
                    ->orWhere('is_walk_in', true);
            })
            ->where('updated_at', '>=', $windowStart)
            ->where('updated_at', '<', $windowEnd)
            ->when($lastCloseAt, fn ($query) => $query->where('updated_at', '>', $lastCloseAt));

        $revenueToday = (clone $todayBillings)->sum('grand_total');
        $transactionsToday = (clone $todayBillings)->count();

        // Items sold today (bar + kitchen orders)
        $barItemsSold = BarOrderItem::whereHas(
            'barOrder',
            fn ($q) => $q->where('created_at', '>=', $windowStart)
                ->where('created_at', '<', $windowEnd)
                ->when($lastCloseAt, fn ($innerQuery) => $innerQuery->where('created_at', '>', $lastCloseAt))
        )->sum('quantity');

        $kitchenItemsSold = KitchenOrderItem::whereHas(
            'kitchenOrder',
            fn ($q) => $q->where('created_at', '>=', $windowStart)
                ->where('created_at', '<', $windowEnd)
                ->when($lastCloseAt, fn ($innerQuery) => $innerQuery->where('created_at', '>', $lastCloseAt))
        )->sum('quantity');

        $itemsSoldToday = $barItemsSold + $kitchenItemsSold;

        // --- Bookings ---
        $bookingPending = TableReservation::where('status', 'pending')->count();
        $bookingConfirmed = TableReservation::where('status', 'confirmed')->count();
        $bookingCompleted = TableReservation::where('status', 'completed')
            ->where('updated_at', '>=', $windowStart)
            ->where('updated_at', '<', $windowEnd)
            ->count();

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
        $dashboardTotalCash = (float) ($dashboardAggregate?->total_cash ?? 0);
        $dashboardTotalTransfer = (float) ($dashboardAggregate?->total_transfer ?? 0);
        $dashboardTotalDebit = (float) ($dashboardAggregate?->total_debit ?? 0);
        $dashboardTotalKredit = (float) ($dashboardAggregate?->total_kredit ?? 0);
        $dashboardTotalQris = (float) ($dashboardAggregate?->total_qris ?? 0);
        $dashboardTotalKitchenItems = (int) ($dashboardAggregate?->total_kitchen_items ?? 0);
        $dashboardTotalBarItems = (int) ($dashboardAggregate?->total_bar_items ?? 0);

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
            'dashboardTotalCash',
            'dashboardTotalTransfer',
            'dashboardTotalDebit',
            'dashboardTotalKredit',
            'dashboardTotalQris',
            'dashboardTotalKitchenItems',
            'dashboardTotalBarItems',
        ));
    }

    public function syncToday(DashboardSyncService $dashboardSyncService): RedirectResponse
    {
        $dashboardSyncService->sync();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Dashboard berhasil di-sync (hari ini).');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveOperationalWindow(): array
    {
        $now = now('Asia/Jakarta');
        $anchor = $now->copy()->setTime(9, 0, 0);

        if ($now->lt($anchor)) {
            return [
                $anchor->copy()->subDay(),
                $anchor,
            ];
        }

        return [
            $anchor,
            $anchor->copy()->addDay(),
        ];
    }
}
