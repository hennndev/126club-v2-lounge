<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Printer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralSettingController extends Controller
{
    public function index(): View
    {
        $settings = GeneralSetting::instance();
        $printers = Printer::active()->orderBy('name')->get();

        return view('settings.general-settings', compact('settings', 'printers'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tax_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'service_charge_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'can_choose_checker' => ['nullable', 'boolean'],
            'closed_billing_receipt_printer_id' => ['nullable', 'integer', 'exists:printers,id'],
            'walk_in_receipt_printer_id' => ['nullable', 'integer', 'exists:printers,id'],
            'end_day_receipt_printer_id' => ['nullable', 'integer', 'exists:printers,id'],
            'auth_code_target_email' => ['nullable', 'email'],
        ]);

        $validated['can_choose_checker'] = $request->boolean('can_choose_checker');

        GeneralSetting::instance()->update($validated);

        return redirect()->route('admin.settings.general.index')
            ->with('success', 'Pengaturan umum berhasil disimpan.');
    }
}
