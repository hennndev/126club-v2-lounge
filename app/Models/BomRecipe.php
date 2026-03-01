<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomRecipe extends Model
{
    protected $guarded = [];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function items()
    {
        return $this->hasMany(BomRecipeItem::class);
    }

    public function calculateTotalCost()
    {
        return $this->items->sum('cost');
    }

    public function getGrossProfitAttribute()
    {
        return $this->selling_price - $this->total_cost;
    }

    public function getProfitMarginAttribute()
    {
        if ($this->selling_price == 0) {
            return 0;
        }

        return ($this->gross_profit / $this->selling_price) * 100;
    }
}
