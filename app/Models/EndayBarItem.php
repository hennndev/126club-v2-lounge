<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndayBarItem extends Model
{
    protected $table = 'enday_bar_items';

    protected $fillable = [
        'recap_history_bar_id',
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
        return $this->belongsTo(RecapHistoryBar::class, 'recap_history_bar_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
