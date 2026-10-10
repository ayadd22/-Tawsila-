<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Scopes\ActiveStoreScope;
use App\Models\User;

class OrderPolicy
{
    private function storeOwnerId(Order $order): ?int
    {
        $store = $order->store()->withoutGlobalScope(ActiveStoreScope::class)->first();

        return $store?->owner_id;
    }

    public function view(User $user, Order $order): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            UserRole::Customer     => $user->id === $order->customer_id,
            UserRole::StoreManager => $user->id === $this->storeOwnerId($order),
            UserRole::Driver       => $user->id === $order->driver_id,
            UserRole::Admin        => true,
        };
    }

    public function accept(User $user, Order $order): bool
    {
        return $user->role === UserRole::StoreManager
            && $user->is_active
            && $user->id === $this->storeOwnerId($order);
    }

    public function assignDriver(User $user, Order $order): bool
    {
        return $user->role === UserRole::StoreManager
            && $user->is_active
            && $user->id === $this->storeOwnerId($order);
    }

    public function markPickedUp(User $user, Order $order): bool
    {
        return $user->role === UserRole::Driver
            && $user->id === $order->driver_id
            && $user->is_active;
    }

    public function markDelivered(User $user, Order $order): bool
    {
        return $user->role === UserRole::Driver
            && $user->id === $order->driver_id
            && $user->is_active;
    }

    public function cancel(User $user, Order $order): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            UserRole::Customer     => $user->id === $order->customer_id,
            UserRole::StoreManager => $user->id === $this->storeOwnerId($order),
            UserRole::Admin        => true,
            default                => false,
        };
    }
}
