<?php

namespace App\Http\Controllers;

use App\Models\CustomerKeep;
use App\Models\CustomerUser;
use Illuminate\Http\Request;

class CustomerKeepController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $tab = $request->get('tab', 'all');

        $query = CustomerKeep::with(['customerUser.user', 'customerUser.profile'])
            ->latest();

        if ($tab === 'active') {
            $query->where('status', 'active');
        } elseif ($tab === 'used') {
            $query->where('status', 'used');
        }

        $keeps = $query->get();

        $totalActive = CustomerKeep::where('status', 'active')->count();
        $totalUsed = CustomerKeep::where('status', 'used')->count();
        $totalItems = CustomerKeep::count();

        $customers = CustomerUser::with(['user', 'profile'])->get();

        // Determine today's session type
        $dayOfWeek = now()->dayOfWeek; // 0=Sun, 1=Mon, ..., 6=Sat
        $todayType = ($dayOfWeek >= 1 && $dayOfWeek <= 4) ? 'weekday' : 'weekend_event';
        $todayLabel = $todayType === 'weekday' ? 'Weekday' : 'Weekend/Event';

        return view('customer-keep.index', compact(
            'keeps',
            'totalActive',
            'totalUsed',
            'totalItems',
            'customers',
            'tab',
            'todayType',
            'todayLabel'
        ));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'customer_user_id' => 'required|exists:customer_users,id',
            'item_name' => 'required|string|max:255',
            'type' => 'required|in:weekday,weekend_event',
            'quantity' => 'required|numeric|min:0.1',
            'unit' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'active';
        $validated['stored_at'] = now();

        CustomerKeep::create($validated);

        return redirect()->route('admin.customer-keep.index')->with('success', 'Item keep berhasil ditambahkan!');
    }

    public function update(Request $request, CustomerKeep $customerKeep): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'customer_user_id' => 'required|exists:customer_users,id',
            'item_name' => 'required|string|max:255',
            'type' => 'required|in:weekday,weekend_event',
            'quantity' => 'required|numeric|min:0.1',
            'unit' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $customerKeep->update($validated);

        return redirect()->route('admin.customer-keep.index')->with('success', 'Item keep berhasil diupdate!');
    }

    public function markUsed(CustomerKeep $customerKeep): \Illuminate\Http\RedirectResponse
    {
        $customerKeep->update([
            'status' => 'used',
            'opened_at' => now(),
        ]);

        return redirect()->route('admin.customer-keep.index')->with('success', 'Item keep ditandai sudah digunakan!');
    }

    public function destroy(CustomerKeep $customerKeep): \Illuminate\Http\RedirectResponse
    {
        $customerKeep->delete();

        return redirect()->route('admin.customer-keep.index')->with('success', 'Item keep berhasil dihapus!');
    }
}
