<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class StoreFactory extends Factory
{
   
    public function definition(): array
    {
        return [
            'owner_id'     => User::factory()->storeManager(),
            'name'         => fake()->company(),
            'description'  => fake()->sentence(),
            'address'      => fake()->streetAddress(),
            'phone'        => fake()->numerify('+9665########'),
            'is_suspended' => false,
        ];
    }

   
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_suspended' => true,
        ]);
    }
}
