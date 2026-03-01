<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ClubOperatingHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubHoursController extends Controller
{
    public function index(): View
    {
        $hours = ClubOperatingHour::orderBy('day_of_week')->get()->keyBy('day_of_week');

        return view('settings.club-hours', compact('hours'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hours' => ['required', 'array'],
            'hours.*.day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
            'hours.*.is_open' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['hours'] as $data) {
            ClubOperatingHour::updateOrCreate(
                ['day_of_week' => $data['day_of_week']],
                [
                    'open_time' => $data['open_time'] ?? null,
                    'close_time' => $data['close_time'] ?? null,
                    'is_open' => isset($data['is_open']) ? (bool) $data['is_open'] : false,
                ],
            );
        }

        return redirect()->route('admin.settings.club-hours.index')
            ->with('success', 'Jam operasional klub berhasil disimpan.');
    }
}
