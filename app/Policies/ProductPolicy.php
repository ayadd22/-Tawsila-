<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Scopes\ActiveStoreScope;
use App\Models\Store;
use App\Models\User;

class ProductPolicy
{
    private function storeOwnerId(Product $product): ?int
    {
        $store = $product->store()->withoutGlobalScope(ActiveStoreScope::class)->first();

        return $store?->owner_id;
    }

    public function create(User $user, Store $store): bool
    {
        return $user->role === UserRole::StoreManager
            && $user->id === $store->owner_id
            && $user->is_active;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->role === UserRole::StoreManager
            && $user->is_active
            && $user->id === $this->storeOwnerId($product);
    }

    public function toggleAvailability(User $user, Product $product): bool
    {
        return $user->role === UserRole::StoreManager
            && $user->is_active
            && $user->id === $this->storeOwnerId($product);
    }
}
