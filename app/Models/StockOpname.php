<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'adjusted_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function getCountedItemsCountAttribute(): int
    {
        return $this->items->whereNotNull('physical_stock')->count();
    }

    public function getDiscrepantItemsCountAttribute(): int
    {
        return $this->items->whereNotNull('physical_stock')->filter(fn ($i) => $i->difference !== 0)->count();
    }

    public function getTotalDiscrepancyAttribute(): int
    {
        return $this->items->whereNotNull('physical_stock')->sum(fn ($i) => abs($i->difference));
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
