<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateStoreRequest;
use App\Http\Requests\Api\UpdateStoreRequest;
use App\Models\Scopes\ActiveStoreScope;
use App\Models\Store;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(private StoreService $storeService) {}

    public function index(): JsonResponse
    {
        $stores = Store::all();

        return response()->json($stores);
    }

    public function show(Store $store): JsonResponse
    {
        $store->load(['products' => fn ($q) => $q->where('is_available', true)]);

        return response()->json($store);
    }

    public function store(CreateStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Store::class);

        $store = $this->storeService->createStore(
            $request->user(),
            $request->validated()
        );

        return response()->json($store, 201);
    }

    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $store = $this->storeService->updateStore($store, $request->validated());

        return response()->json($store);
    }

    public function suspend(Request $request, int $id): JsonResponse
    {
        $this->authorize('suspend', Store::class);

        $store = Store::withoutGlobalScope(ActiveStoreScope::class)->findOrFail($id);

        $store = $this->storeService->suspendStore($store);

        return response()->json($store);
    }

    public function unsuspend(Request $request, int $id): JsonResponse
    {
        $this->authorize('unsuspend', Store::class);

        $store = Store::withoutGlobalScope(ActiveStoreScope::class)->findOrFail($id);

        $store = $this->storeService->unsuspendStore($store);

        return response()->json($store);
    }
}
