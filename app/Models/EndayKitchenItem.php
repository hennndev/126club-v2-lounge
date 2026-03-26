<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndayKitchenItem extends Model
{
    protected $table = 'enday_kitchen_items';

    protected $fillable = [
        'recap_history_kitchen_id',
        'end_day',
        'inventory_item_id',
        'quantity',
    ];

    protected $casts = [
        'end_day' => 'date',
        'quantity' => 'integer',
    ];

    public function recapHistory(): BelongsTo
    {
        return $this->belongsTo(RecapHistoryKitchen::class, 'recap_history_kitchen_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
