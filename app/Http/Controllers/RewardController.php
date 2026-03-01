<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $rewards = Reward::orderBy('points_required')->get();

        $totalRewards = $rewards->count();
        $totalStock = $rewards->sum('stock');
        $totalPointsValue = $rewards->sum(fn ($r) => $r->points_required * $r->stock);
        $totalRedeemed = $rewards->sum('redeemed_count');

        return view('rewards.index', compact(
            'rewards',
            'totalRewards',
            'totalStock',
            'totalPointsValue',
            'totalRedeemed'
        ));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:drink,voucher,vip',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
        ]);

        Reward::create($validated);

        return redirect()->route('admin.rewards.index')->with('success', 'Reward berhasil ditambahkan!');
    }

    public function update(Request $request, Reward $reward): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:drink,voucher,vip',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
        ]);

        $reward->update($validated);

        return redirect()->route('admin.rewards.index')->with('success', 'Reward berhasil diupdate!');
    }

    public function destroy(Reward $reward): \Illuminate\Http\RedirectResponse
    {
        $reward->delete();

        return redirect()->route('admin.rewards.index')->with('success', 'Reward berhasil dihapus!');
    }
}
