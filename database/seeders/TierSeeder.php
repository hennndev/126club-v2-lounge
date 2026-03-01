<?php

namespace Database\Seeders;

use App\Models\Tier;
use Illuminate\Database\Seeder;

class TierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'level' => 1,
                'name' => 'Registered',
                'discount_percentage' => 0,
                'minimum_spent' => 0,
                'is_first_tier' => true,
                'color' => 'slate',
            ],
            [
                'level' => 2,
                'name' => 'Recognized',
                'discount_percentage' => 5,
                'minimum_spent' => 5000000,
                'is_first_tier' => false,
                'color' => 'blue',
            ],
            [
                'level' => 3,
                'name' => 'Untouchable',
                'discount_percentage' => 10,
                'minimum_spent' => 25000000,
                'is_first_tier' => false,
                'color' => 'amber',
            ],
        ];

        foreach ($tiers as $tier) {
            Tier::updateOrCreate(['level' => $tier['level']], $tier);
        }
    }
}
