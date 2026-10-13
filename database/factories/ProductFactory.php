<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;


class ProductFactory extends Factory
{
   
    public function definition(): array
    {
        return [
            'store_id'     => Store::factory(),
            'name'         => fake()->words(fake()->numberBetween(1, 3), true),
            'description'  => fake()->sentence(),
            'price'        => fake()->randomFloat(2, 5, 200),
            'is_available' => true,
        ];
    }

    
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
