<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $fillable = [
        'table_session_id',
        'order_id',
        'is_walk_in',
        'is_booking',
        'minimum_charge',
        'orders_total',
        'subtotal',
        'tax',
        'tax_percentage',
        'service_charge',
        'service_charge_percentage',
        'discount_amount',
        'grand_total',
        'paid_amount',
        'billing_status',
        'paid_at',
        'transaction_code',
        'payment_method',
        'payment_reference_number',
        'payment_mode',
        'split_cash_amount',
        'split_debit_amount',
        'split_non_cash_method',
        'split_non_cash_reference_number',
        'split_second_non_cash_amount',
        'split_second_non_cash_method',
        'split_second_non_cash_reference_number',
        'notes',
        'closing_notes',
        'accurate_so_number',
        'accurate_inv_number',
        'error_message',
    ];

    protected $casts = [
        'minimum_charge' => 'decimal:2',
        'orders_total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'service_charge_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'split_cash_amount' => 'decimal:2',
        'split_debit_amount' => 'decimal:2',
        'split_second_non_cash_amount' => 'decimal:2',
        'is_walk_in' => 'boolean',
        'is_booking' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function tableSession()
    {
        return $this->belongsTo(TableSession::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
