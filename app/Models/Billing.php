<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $fillable = [
        'table_session_id',
        'minimum_charge',
        'orders_total',
        'subtotal',
        'tax',
        'tax_percentage',
        'discount_amount',
        'grand_total',
        'paid_amount',
        'billing_status',
        'transaction_code',
        'payment_method',
        'notes',
        'closing_notes',
    ];

    protected $casts = [
        'minimum_charge' => 'decimal:2',
        'orders_total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function tableSession()
    {
        return $this->belongsTo(TableSession::class);
    }
}
