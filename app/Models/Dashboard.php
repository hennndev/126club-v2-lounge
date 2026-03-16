<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dashboard extends Model
{
    protected $table = 'dashboard';

    protected $fillable = [
        'total_amount',
        'total_tax',
        'total_service_charge',
        'total_cash',
        'total_transfer',
        'total_debit',
        'total_kredit',
        'total_qris',
        'total_transactions',
        'last_synced_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_service_charge' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_transfer' => 'decimal:2',
        'total_debit' => 'decimal:2',
        'total_kredit' => 'decimal:2',
        'total_qris' => 'decimal:2',
        'total_transactions' => 'integer',
        'last_synced_at' => 'datetime',
    ];
}
