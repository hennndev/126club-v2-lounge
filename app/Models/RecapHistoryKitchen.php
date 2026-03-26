<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecapHistoryKitchen extends Model
{
    protected $table = 'recap_history_kitchen';

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

    public function endayItems(): HasMany
    {
        return $this->hasMany(EndayKitchenItem::class, 'recap_history_kitchen_id');
    }
}
