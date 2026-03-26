<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable = [
        'tax_percentage',
        'service_charge_percentage',
        'can_choose_checker',
        'closed_billing_receipt_printer_id',
        'walk_in_receipt_printer_id',
        'end_day_receipt_printer_id',
        'end_day_kitchen_printer_id',
        'end_day_bar_printer_id',
        'auth_code_target_email',
    ];

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'integer',
            'service_charge_percentage' => 'integer',
            'can_choose_checker' => 'boolean',
            'closed_billing_receipt_printer_id' => 'integer',
            'walk_in_receipt_printer_id' => 'integer',
            'end_day_receipt_printer_id' => 'integer',
            'end_day_kitchen_printer_id' => 'integer',
            'end_day_bar_printer_id' => 'integer',
        ];
    }

    /**
     * Always return the single settings row, creating it if missing.
     */
    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'tax_percentage' => 0,
            'service_charge_percentage' => 0,
            'can_choose_checker' => false,
            'closed_billing_receipt_printer_id' => null,
            'walk_in_receipt_printer_id' => null,
            'end_day_receipt_printer_id' => null,
            'end_day_kitchen_printer_id' => null,
            'end_day_bar_printer_id' => null,
        ]);
    }
}
