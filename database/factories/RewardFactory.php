<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reward>
 */
class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['drink', 'voucher', 'vip'];
        $category = $this->faker->randomElement($categories);

        return [
            'name' => $this->faker->words(3, true),
            'category' => $category,
            'description' => $this->faker->sentence(),
            'points_required' => $this->faker->randomElement([500, 800, 1000, 1500, 2000, 3000]),
            'stock' => $this->faker->numberBetween(10, 100),
            'redeemed_count' => $this->faker->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
