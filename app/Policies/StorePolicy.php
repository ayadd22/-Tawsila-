<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::StoreManager && $user->is_active;
    }

    public function update(User $user, Store $store): bool
    {
        return $user->role === UserRole::StoreManager
            && $user->id === $store->owner_id
            && $user->is_active;
    }

    public function suspend(User $user): bool
    {
        return $user->role === UserRole::Admin && $user->is_active;
    }

    public function unsuspend(User $user): bool
    {
        return $user->role === UserRole::Admin && $user->is_active;
    }
}
