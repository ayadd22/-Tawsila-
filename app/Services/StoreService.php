<?php

namespace App\Services;

use App\Models\Scopes\ActiveStoreScope;
use App\Models\Store;
use App\Models\User;

class StoreService
{
    public function createStore(User $owner, array $data): Store
    {
        return $owner->stores()->create($data);
    }

    public function updateStore(Store $store, array $data): Store
    {
        $store->update($data);

        return $store;
    }

    public function suspendStore(Store $store): Store
    {
        $store->update(['is_suspended' => true]);

        return $store;
    }

    public function unsuspendStore(Store $store): Store
    {
        $store->update(['is_suspended' => false]);

        return $store;
    }

    public function getAllStores(): \Illuminate\Database\Eloquent\Collection
    {
        return Store::withoutGlobalScope(ActiveStoreScope::class)->get();
    }

    public function getActiveStores(): \Illuminate\Database\Eloquent\Collection
    {
        return Store::all();
    }
}
