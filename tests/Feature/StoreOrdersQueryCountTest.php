<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreOrdersQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function createManager(): User
    {
        return User::factory()->storeManager()->create();
    }

    private function createDriver(): User
    {
        return User::factory()->driver()->create();
    }

    private function createCustomer(): User
    {
        return User::factory()->customer()->create();
    }

    private function createStoreWithOrders(User $manager, User $customer, ?User $driver, int $orderCount): Store
    {
        $store = Store::factory()->for($manager, 'owner')->create();

        $products = Product::factory()->for($store)->count(3)->create();

        for ($i = 0; $i < $orderCount; $i++) {
            $order = Order::factory()->create([
                'customer_id' => $customer->id,
                'store_id'    => $store->id,
                'status'      => OrderStatus::Accepted,
                'driver_id'   => $driver?->id,
            ]);

            OrderItem::factory()->create([
                'order_id'   => $order->id,
                'product_id' => $products->random()->id,
                'quantity'   => 1,
                'unit_price' => $products->first()->price,
            ]);
        }

        return $store;
    }

    private function measureQueryCount(User $manager): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/orders/store');

        DB::disableQueryLog();

        $queries = DB::getQueryLog();

        return [
            'response' => $response,
            'count'    => count($queries),
            'queries'  => $queries,
        ];
    }

    public function test_store_orders_query_count_with_two_orders(): void
    {
        $manager  = $this->createManager();
        $customer = $this->createCustomer();
        $driver   = $this->createDriver();

        $this->createStoreWithOrders($manager, $customer, $driver, 2);

        $result = $this->measureQueryCount($manager);

        $result['response']->assertOk();

        $queryList = json_encode(array_column($result['queries'], 'query'), JSON_PRETTY_PRINT);

        $this->assertSame(6, $result['count'],
            "Expected exactly 6 queries for storeOrders with 2 orders.\n"
            . "Actual count: {$result['count']}.\nQueries:\n{$queryList}"
        );
    }

    public function test_store_orders_query_count_with_six_orders(): void
    {
        $manager  = $this->createManager();
        $customer = $this->createCustomer();
        $driver   = $this->createDriver();

        $this->createStoreWithOrders($manager, $customer, $driver, 6);

        $result = $this->measureQueryCount($manager);

        $result['response']->assertOk();

        $queryList = json_encode(array_column($result['queries'], 'query'), JSON_PRETTY_PRINT);

        $this->assertSame(6, $result['count'],
            "Expected exactly 6 queries for storeOrders with 6 orders.\n"
            . "Actual count: {$result['count']}.\nQueries:\n{$queryList}"
        );
    }

    public function test_query_count_does_not_increase_with_more_orders(): void
    {
        $manager  = $this->createManager();
        $customer = $this->createCustomer();
        $driver   = $this->createDriver();

        $this->createStoreWithOrders($manager, $customer, $driver, 2);
        $smallResult = $this->measureQueryCount($manager);
        $smallResult['response']->assertOk();
        $smallCount = $smallResult['count'];

        $this->createStoreWithOrders($manager, $customer, $driver, 6);
        $largeResult = $this->measureQueryCount($manager);
        $largeResult['response']->assertOk();
        $largeCount = $largeResult['count'];

        $this->assertSame(
            $smallCount,
            $largeCount,
            sprintf(
                'Query count changed from %d (2 orders) to %d (8 orders). '
                . 'A different count indicates an N+1 problem. '
                . "Small dataset queries:\n%s\nLarge dataset queries:\n%s",
                $smallCount,
                $largeCount,
                json_encode(array_column($smallResult['queries'], 'query'), JSON_PRETTY_PRINT),
                json_encode(array_column($largeResult['queries'], 'query'), JSON_PRETTY_PRINT)
            )
        );
    }

    public function test_store_orders_returns_correct_structure(): void
    {
        $manager  = $this->createManager();
        $customer = $this->createCustomer();
        $driver   = $this->createDriver();

        $this->createStoreWithOrders($manager, $customer, $driver, 1);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/orders/store');

        $response->assertOk();

        $response->assertJsonStructure([
            '*' => [
                'id',
                'store_id',
                'status',
                'delivery_address',
                'customer' => ['id', 'name', 'email'],
                'order_items' => [
                    '*' => [
                        'id',
                        'product_id',
                        'quantity',
                        'unit_price',
                        'product' => ['id', 'name', 'price'],
                    ],
                ],
            ],
        ]);
    }

    public function test_manager_with_no_stores_gets_empty_result(): void
    {
        $manager = $this->createManager();

        $result = $this->measureQueryCount($manager);

        $result['response']->assertOk();
        $result['response']->assertExactJson([]);
    }

    public function test_store_orders_excludes_other_managers_orders(): void
    {
        $manager1  = $this->createManager();
        $manager2  = $this->createManager();
        $customer  = $this->createCustomer();

        $this->createStoreWithOrders($manager1, $customer, null, 2);
        $this->createStoreWithOrders($manager2, $customer, null, 3);

        $response = $this->actingAs($manager1, 'sanctum')
            ->getJson('/api/orders/store');

        $response->assertOk();

        $orders = $response->json();

        $this->assertCount(2, $orders,
            'Manager 1 should only see orders belonging to their own stores.'
        );
    }
}
