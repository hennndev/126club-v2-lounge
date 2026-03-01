<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAuthCode extends Model
{
    protected $fillable = [
        'date',
        'code',
        'override_code',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    /**
     * The code to use for authentication — override takes priority.
     */
    public function getActiveCodeAttribute(): string
    {
        return $this->override_code ?? $this->code;
    }

    /**
     * Generate a cryptographically random 4-digit code.
     */
    public static function generateRandom(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get or create today's record, auto-generating a random code on first create.
     */
    public static function forDate(string $date): self
    {
        return static::firstOrCreate(
            ['date' => $date],
            [
                'code' => static::generateRandom(),
                'generated_at' => now(),
            ]
        );
    }
}
