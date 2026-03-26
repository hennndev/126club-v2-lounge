<?php

use App\Models\GeneralSetting;
use App\Models\Printer;

use function Pest\Laravel\actingAs;

test('general settings can save receipt printer assignments', function () {
    $admin = adminUser();

    $closedBillingPrinter = Printer::create([
        'name' => 'Closed Billing Printer',
        'location' => 'cashier',
        'printer_type' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    $walkInPrinter = Printer::create([
        'name' => 'Walk-in Printer',
        'location' => 'cashier',
        'printer_type' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    $endDayPrinter = Printer::create([
        'name' => 'End Day Printer',
        'location' => 'cashier',
        'printer_type' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    actingAs($admin)
        ->put(route('admin.settings.general.update'), [
            'tax_percentage' => 10,
            'service_charge_percentage' => 5,
            'can_choose_checker' => true,
            'closed_billing_receipt_printer_id' => $closedBillingPrinter->id,
            'walk_in_receipt_printer_id' => $walkInPrinter->id,
            'end_day_receipt_printer_id' => $endDayPrinter->id,
            'auth_code_target_email' => 'approval@company.test',
        ])
        ->assertRedirect(route('admin.settings.general.index'));

    $settings = GeneralSetting::instance();

    expect((int) $settings->closed_billing_receipt_printer_id)->toBe((int) $closedBillingPrinter->id)
        ->and((int) $settings->walk_in_receipt_printer_id)->toBe((int) $walkInPrinter->id)
        ->and((int) $settings->end_day_receipt_printer_id)->toBe((int) $endDayPrinter->id)
        ->and((string) $settings->auth_code_target_email)->toBe('approval@company.test')
        ->and((int) $settings->tax_percentage)->toBe(10)
        ->and((int) $settings->service_charge_percentage)->toBe(5)
        ->and((bool) $settings->can_choose_checker)->toBeTrue();
});

test('general settings rejects invalid auth code target email format', function () {
    $admin = adminUser();

    actingAs($admin)
        ->from(route('admin.settings.general.index'))
        ->put(route('admin.settings.general.update'), [
            'tax_percentage' => 10,
            'service_charge_percentage' => 5,
            'can_choose_checker' => true,
            'auth_code_target_email' => 'invalid-email',
        ])
        ->assertRedirect(route('admin.settings.general.index'))
        ->assertSessionHasErrors(['auth_code_target_email']);
});
