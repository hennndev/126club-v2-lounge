<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecapHistory extends Model
{
    protected $table = 'recap_history';

    protected $fillable = [
        'end_day',
        'total_amount',
        'total_penjualan_rokok',
        'total_tax',
        'total_service_charge',
        'total_cash',
        'total_transfer',
        'total_debit',
        'total_kredit',
        'total_qris',
        'total_kitchen_items',
        'total_bar_items',
        'total_transactions',
        'last_synced_at',
    ];

    protected $casts = [
        'end_day' => 'date',
        'total_amount' => 'decimal:2',
        'total_penjualan_rokok' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_service_charge' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_transfer' => 'decimal:2',
        'total_debit' => 'decimal:2',
        'total_kredit' => 'decimal:2',
        'total_qris' => 'decimal:2',
        'total_kitchen_items' => 'integer',
        'total_bar_items' => 'integer',
        'total_transactions' => 'integer',
        'last_synced_at' => 'datetime',
    ];
}
