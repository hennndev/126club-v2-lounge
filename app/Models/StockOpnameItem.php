<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'system_stock' => 'integer',
            'physical_stock' => 'integer',
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getDifferenceAttribute(): int
    {
        if ($this->physical_stock === null) {
            return 0;
        }

        return $this->physical_stock - $this->system_stock;
    }
}
