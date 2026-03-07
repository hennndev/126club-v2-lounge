<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\BomRecipe;
use App\Models\InventoryItem;
use App\Models\TableReservation;
use App\Models\TableSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WaiterController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('waiter.scanner');
    }

    public function scanner(): View
    {
        return view('waiter.scanner');
    }

    public function activeTables(): View
    {
        $sessions = TableSession::with(['table.area', 'customer.profile', 'billing'])
            ->where('status', 'active')
            ->orderByDesc('checked_in_at')
            ->get();

        $areas = Area::where('is_active', true)->orderBy('sort_order')->get();

        return view('waiter.active-tables', compact('sessions', 'areas'));
    }

    public function pos(): View
    {
        $bomProducts = BomRecipe::with('inventoryItem')
            ->whereHas('inventoryItem', fn ($q) => $q->whereIn('category_type', ['food', 'bar']))
            ->where('is_available', true)
            ->get()
            ->map(fn ($bom) => [
                'id' => 'bom_'.$bom->id,
                'bom_id' => $bom->id,
                'name' => $bom->inventoryItem->name ?? '',
                'category' => $bom->inventoryItem->category_type ?? 'food',
                'price' => (float) $bom->selling_price,
                'type' => 'bom',
            ]);

        $beverages = InventoryItem::where('category_type', 'beverage')
            ->where('is_active', true)
            ->get()
            ->map(fn ($item) => [
                'id' => 'inv_'.$item->id,
                'name' => $item->name,
                'category' => 'beverage',
                'price' => (float) $item->price,
                'type' => 'inventory',
            ]);

        $products = $bomProducts->merge($beverages)->sortBy('name')->values();

        $activeSessions = TableSession::with(['table.area', 'customer.profile'])
            ->where('status', 'active')
            ->orderByDesc('checked_in_at')
            ->get();

        $cart = session('pos_cart', []);
        $selectedCounter = session('pos_selected_counter');

        return view('waiter.pos', compact('products', 'activeSessions', 'cart', 'selectedCounter'));
    }

    public function notifications(): View
    {
        $waiter = auth()->user();

        // Personal notifications for this waiter (assigned bookings)
        $assignedNotifications = $waiter->unreadNotifications()
            ->where('type', \App\Notifications\WaiterAssignedNotification::class)
            ->latest()
            ->get();

        $pendingCheckIns = TableReservation::with(['table.area', 'customer.profile'])
            ->where('status', 'confirmed')
            ->orderByDesc('created_at')
            ->get();

        $recentCheckIns = TableSession::with(['table.area', 'customer.profile'])
            ->where('status', 'active')
            ->whereDate('checked_in_at', today())
            ->orderByDesc('checked_in_at')
            ->take(10)
            ->get();

        // Mark assigned notifications as read when viewing this page
        $waiter->unreadNotifications()
            ->where('type', \App\Notifications\WaiterAssignedNotification::class)
            ->update(['read_at' => now()]);

        return view('waiter.notifications', compact('pendingCheckIns', 'recentCheckIns', 'assignedNotifications'));
    }

    public function settings(): View
    {
        return view('waiter.settings');
    }
}
