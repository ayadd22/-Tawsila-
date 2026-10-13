<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    
    public function definition(): array
    {
        return [
            'customer_id'      => User::factory()->customer(),
            'store_id'         => Store::factory(),
            'driver_id'        => null,
            'status'           => OrderStatus::Pending,
            'delivery_address' => fake()->address(),
            'notes'            => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => OrderStatus::Accepted,
            'driver_id' => null,
        ]);
    }

   
    public function acceptedWithDriver(int $driverId): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => OrderStatus::Accepted,
            'driver_id' => $driverId,
        ]);
    }

   
    public function pickedUp(int $driverId): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => OrderStatus::PickedUp,
            'driver_id' => $driverId,
        ]);
    }

  
    public function delivered(int $driverId): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => OrderStatus::Delivered,
            'driver_id' => $driverId,
        ]);
    }

  
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => OrderStatus::Cancelled,
            'driver_id' => null,
        ]);
    }
}
