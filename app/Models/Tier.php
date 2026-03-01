<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'discount_percentage' => 'integer',
            'minimum_spent' => 'integer',
            'is_first_tier' => 'boolean',
        ];
    }

    public function getFormattedMinimumSpentAttribute(): string
    {
        return 'Rp '.number_format($this->minimum_spent, 0, ',', '.');
    }
}
