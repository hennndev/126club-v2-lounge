<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerKeep extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'stored_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function customerUser()
    {
        return $this->belongsTo(CustomerUser::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'weekday' => 'Weekday',
            'weekend_event' => 'Weekend/Event',
            default => $this->type,
        };
    }

    public function getIsActiveTodayAttribute(): bool
    {
        if ($this->type === 'weekend_event') {
            return true;
        }

        // Weekday: Mon-Thu only (1-4)
        $dayOfWeek = now()->dayOfWeek; // 0=Sun, 1=Mon, ..., 6=Sat

        return $dayOfWeek >= 1 && $dayOfWeek <= 4;
    }
}
