<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TierSettingsController extends Controller
{
    public function index(): View
    {
        $tiers = Tier::orderBy('level')->get();

        return view('settings.tier-settings', compact('tiers'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tiers' => ['required', 'array'],
            'tiers.*.id' => ['required', 'exists:tiers,id'],
            'tiers.*.name' => ['required', 'string', 'max:100'],
            'tiers.*.discount_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'tiers.*.minimum_spent' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['tiers'] as $data) {
            $tier = Tier::findOrFail($data['id']);

            $tier->name = $data['name'];
            $tier->discount_percentage = $data['discount_percentage'];

            if (! $tier->is_first_tier) {
                $tier->minimum_spent = $data['minimum_spent'];
            }

            $tier->save();
        }

        return redirect()->route('admin.settings.tier-settings.index')
            ->with('success', 'Pengaturan tier berhasil disimpan.');
    }

    public function resetToDefault(): RedirectResponse
    {
        $defaults = [
            1 => ['name' => 'Registered', 'discount_percentage' => 0, 'minimum_spent' => 0],
            2 => ['name' => 'Recognized', 'discount_percentage' => 5, 'minimum_spent' => 5000000],
            3 => ['name' => 'Untouchable', 'discount_percentage' => 10, 'minimum_spent' => 25000000],
        ];

        foreach ($defaults as $level => $data) {
            Tier::where('level', $level)->update($data);
        }

        return redirect()->route('admin.settings.tier-settings.index')
            ->with('success', 'Tier berhasil di-reset ke default.');
    }
}
