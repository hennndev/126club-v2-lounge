<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyKitchenSnapshot extends Model
{
    protected $fillable = [
        'end_day',
        'total_items',
        'last_synced_at',
    ];

    protected $casts = [
        'end_day' => 'date',
        'total_items' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function dailyItems(): HasMany
    {
        return $this->hasMany(DailyKitchenItem::class, 'daily_kitchen_snapshot_id');
    }
}
