<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rewards = [
            [
                'name' => 'Free House Cocktail',
                'category' => 'drink',
                'description' => 'Redeem 1 gratis house cocktail pilihan',
                'points_required' => 500,
                'stock' => 50,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Shot',
                'category' => 'drink',
                'description' => '1 shot premium spirit (Patron, Grey Goose)',
                'points_required' => 800,
                'stock' => 30,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Bottle Service 10%',
                'category' => 'voucher',
                'description' => 'Diskon 10% untuk bottle service',
                'points_required' => 1000,
                'stock' => 100,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'VIP Table Upgrade',
                'category' => 'vip',
                'description' => 'Upgrade ke VIP table untuk 1 malam',
                'points_required' => 1500,
                'stock' => 10,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Champagne Bottle',
                'category' => 'drink',
                'description' => '1 botol Moët & Chandon Brut',
                'points_required' => 2000,
                'stock' => 15,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'VIP Room Night',
                'category' => 'vip',
                'description' => 'Gratis VIP room untuk 4 orang',
                'points_required' => 3000,
                'stock' => 5,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Birthday Package',
                'category' => 'vip',
                'description' => 'Paket ulang tahun eksklusif dengan dekorasi',
                'points_required' => 5000,
                'stock' => 8,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Soft Drink Bundle',
                'category' => 'drink',
                'description' => 'Bundle 5 soft drink pilihan',
                'points_required' => 300,
                'stock' => 200,
                'redeemed_count' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($rewards as $reward) {
            \App\Models\Reward::create($reward);
        }
    }
}
