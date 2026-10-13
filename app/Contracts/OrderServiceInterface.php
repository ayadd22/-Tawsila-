<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;

interface OrderServiceInterface
{
    public function placeOrder(User $customer, Store $store, string $deliveryAddress, array $items, ?string $notes = null): Order;

    public function acceptOrder(Order $order): Order;

    public function assignDriver(Order $order, User $driver): Order;

    public function markPickedUp(Order $order): Order;

    public function markDelivered(Order $order): Order;

    public function cancelOrder(Order $order): Order;
}
