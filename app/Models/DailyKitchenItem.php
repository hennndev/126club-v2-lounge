<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyKitchenItem extends Model
{
    protected $fillable = [
        'daily_kitchen_snapshot_id',
        'end_day',
        'inventory_item_id',
        'quantity',
    ];

    protected $casts = [
        'end_day' => 'date',
        'quantity' => 'integer',
    ];

    public function dailySnapshot(): BelongsTo
    {
        return $this->belongsTo(DailyKitchenSnapshot::class, 'daily_kitchen_snapshot_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
