<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Models\Product;
use App\Models\Scopes\ActiveStoreScope;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function indexByStore(Store $store): JsonResponse
    {
        $products = $store->products()->available()->get();

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        $store = $product->store()->withoutGlobalScope(ActiveStoreScope::class)->first();

        $user = request()->user();

        $isOwner = $user
            && $user->role === UserRole::StoreManager
            && $user->is_active
            && $store
            && $user->id === $store->owner_id;

        if (! $isOwner) {
            if (! $store || $store->is_suspended) {
                abort(404);
            }

            if (! $product->is_available) {
                abort(404);
            }
        }

        $product->setRelation('store', $store);

        return response()->json($product);
    }

    public function store(CreateProductRequest $request, Store $store): JsonResponse
    {
        $this->authorize('create', [Product::class, $store]);

        $product = $this->productService->createProduct($store, $request->validated());

        return response()->json($product, 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->productService->updateProduct($product, $request->validated());

        return response()->json($product);
    }

    public function toggleAvailability(Product $product): JsonResponse
    {
        $this->authorize('toggleAvailability', $product);

        $product = $this->productService->toggleAvailability($product);

        return response()->json($product);
    }
}
