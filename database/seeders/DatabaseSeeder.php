<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

   
    public function run(): void
    {
 
        User::factory()->admin()->create([
            'name'  => 'Admin User',
            'email' => 'admin@tawsila.test',
            'phone' => '+966500000001',
        ]);

        $testCustomer = User::factory()->customer()->create([
            'name'  => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+966500000002',
        ]);

       

        
        $customers = User::factory()->customer()->count(20)->create();
        $customers->push($testCustomer); 

       
        $drivers = User::factory()->driver()->count(10)->create();

       
        $productsByStore = [];

        User::factory()->storeManager()->count(5)->create()->each(function (User $manager) use (&$productsByStore) {
            Store::factory()->for($manager, 'owner')->count(2)->create()->each(function (Store $store) use (&$productsByStore) {
                $products = Product::factory()->for($store)->count(15)->create();
                $productsByStore[$store->id] = $products;
            });
        });

       
        $storeIds = array_keys($productsByStore);

     

        $createItems = function (Order $order) use ($productsByStore): void {
            $pool = $productsByStore[$order->store_id];
            
            $count    = min(fake()->numberBetween(1, 4), $pool->count());
            $selected = $pool->random($count);

            foreach ($selected as $product) {
                OrderItem::factory()->create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => fake()->numberBetween(1, 5),
                    'unit_price' => $product->price,   // price snapshot
                ]);
            }
        };

        
        $customerIds = $customers->pluck('id')->toArray();
        $driverIds   = $drivers->pluck('id')->toArray();

        $this->command->getOutput()->writeln('  Seeding pending orders…');
        for ($i = 0; $i < 1200; $i++) {
            $storeId = fake()->randomElement($storeIds);
            $order   = Order::factory()->create([
                'customer_id' => fake()->randomElement($customerIds),
                'store_id'    => $storeId,
                'status'      => OrderStatus::Pending,
                'driver_id'   => null,
            ]);
            $createItems($order);
        }

        
        $this->command->getOutput()->writeln('  Seeding accepted orders (no driver)…');
        for ($i = 0; $i < 300; $i++) {
            $storeId = fake()->randomElement($storeIds);
            $order   = Order::factory()->create([
                'customer_id' => fake()->randomElement($customerIds),
                'store_id'    => $storeId,
                'status'      => OrderStatus::Accepted,
                'driver_id'   => null,
            ]);
            $createItems($order);
        }

        $this->command->getOutput()->writeln('  Seeding accepted orders (with driver)…');
        for ($i = 0; $i < 450; $i++) {
            $storeId = fake()->randomElement($storeIds);
            $order   = Order::factory()->create([
                'customer_id' => fake()->randomElement($customerIds),
                'store_id'    => $storeId,
                'status'      => OrderStatus::Accepted,
                'driver_id'   => fake()->randomElement($driverIds),
            ]);
            $createItems($order);
        }

        
        $this->command->getOutput()->writeln('  Seeding picked-up orders…');
        for ($i = 0; $i < 450; $i++) {
            $storeId = fake()->randomElement($storeIds);
            $order   = Order::factory()->create([
                'customer_id' => fake()->randomElement($customerIds),
                'store_id'    => $storeId,
                'status'      => OrderStatus::PickedUp,
                'driver_id'   => fake()->randomElement($driverIds),
            ]);
            $createItems($order);
        }

       
        $this->command->getOutput()->writeln('  Seeding delivered orders…');
        for ($i = 0; $i < 450; $i++) {
            $storeId = fake()->randomElement($storeIds);
            $order   = Order::factory()->create([
                'customer_id' => fake()->randomElement($customerIds),
                'store_id'    => $storeId,
                'status'      => OrderStatus::Delivered,
                'driver_id'   => fake()->randomElement($driverIds),
            ]);
            $createItems($order);
        }

        $this->command->getOutput()->writeln('  Seeding cancelled orders…');
        for ($i = 0; $i < 150; $i++) {
            $storeId = fake()->randomElement($storeIds);
            $order   = Order::factory()->create([
                'customer_id' => fake()->randomElement($customerIds),
                'store_id'    => $storeId,
                'status'      => OrderStatus::Cancelled,
                'driver_id'   => null,
            ]);
            $createItems($order);
        }

        $this->command->getOutput()->writeln('  Done.');
    }
}
