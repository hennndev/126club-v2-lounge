<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarOrderItem extends Model
{
    protected $fillable = [
        'bar_order_id',
        'bom_recipe_id',
        'inventory_item_id',
        'quantity',
        'price',
        'is_completed',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_completed' => 'boolean',
    ];

    public function barOrder(): BelongsTo
    {
        return $this->belongsTo(BarOrder::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(BomRecipe::class, 'bom_recipe_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Resolve the display name regardless of whether this is a BOM or direct inventory item.
     */
    public function getItemNameAttribute(): string
    {
        return $this->recipe?->inventoryItem?->name
            ?? $this->inventoryItem?->name
            ?? 'Item';
    }
}
