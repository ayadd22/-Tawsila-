<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function placeOrder(User $customer, Store $store, string $deliveryAddress, array $items, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($customer, $store, $deliveryAddress, $items, $notes) {
            if ($customer->role !== UserRole::Customer || ! $customer->is_active) {
                throw ValidationException::withMessages([
                    'customer' => 'Only active customers can place orders.',
                ]);
            }

            if ($store->is_suspended) {
                throw ValidationException::withMessages([
                    'store' => 'Cannot place an order for a suspended store.',
                ]);
            }

            $order = Order::create([
                'customer_id'      => $customer->id,
                'store_id'         => $store->id,
                'status'           => OrderStatus::Pending,
                'delivery_address' => $deliveryAddress,
                'notes'            => $notes,
            ]);

            foreach ($items as $item) {
                $product = $store->products()->findOrFail($item['product_id']);

                if (! $product->is_available) {
                    throw ValidationException::withMessages([
                        'items' => "Product [{$product->name}] is not available.",
                    ]);
                }

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                ]);
            }

            return $order;
        });
    }

    public function acceptOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $fresh = Order::lockForUpdate()->findOrFail($order->id);

            $this->requireStatus($fresh, OrderStatus::Pending);

            $fresh->update(['status' => OrderStatus::Accepted]);

            return $fresh;
        });
    }

    public function assignDriver(Order $order, User $driver): Order
    {
        if ($driver->role !== UserRole::Driver || ! $driver->is_active) {
            throw ValidationException::withMessages([
                'driver' => 'The assigned user must be an active driver.',
            ]);
        }

        return DB::transaction(function () use ($order, $driver) {
            $fresh = Order::lockForUpdate()->findOrFail($order->id);

            $this->requireStatus($fresh, OrderStatus::Accepted);

            if ($fresh->driver_id !== null) {
                throw ValidationException::withMessages([
                    'driver' => 'This order already has an assigned driver.',
                ]);
            }

            $fresh->update(['driver_id' => $driver->id]);

            return $fresh;
        });
    }

    public function markPickedUp(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $fresh = Order::lockForUpdate()->findOrFail($order->id);

            $this->requireStatus($fresh, OrderStatus::Accepted);

            if ($fresh->driver_id === null) {
                throw ValidationException::withMessages([
                    'driver' => 'An order cannot be picked up without an assigned driver.',
                ]);
            }

            $fresh->update(['status' => OrderStatus::PickedUp]);

            return $fresh;
        });
    }

    public function markDelivered(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $fresh = Order::lockForUpdate()->findOrFail($order->id);

            $this->requireStatus($fresh, OrderStatus::PickedUp);

            $fresh->update(['status' => OrderStatus::Delivered]);

            return $fresh;
        });
    }

    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $fresh = Order::lockForUpdate()->findOrFail($order->id);

            if (! in_array($fresh->status, [OrderStatus::Pending, OrderStatus::Accepted])) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending or accepted orders can be cancelled.',
                ]);
            }

            $fresh->update(['status' => OrderStatus::Cancelled]);

            return $fresh;
        });
    }

    private function requireStatus(Order $order, OrderStatus $required): void
    {
        if ($order->status !== $required) {
            throw ValidationException::withMessages([
                'status' => "Expected order status [{$required->value}], got [{$order->status->value}].",
            ]);
        }
    }
}
