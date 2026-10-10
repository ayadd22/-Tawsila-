<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;

class ProductService
{
    public function createProduct(Store $store, array $data): Product
    {
        return $store->products()->create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function toggleAvailability(Product $product): Product
    {
        $product->update(['is_available' => ! $product->is_available]);

        return $product;
    }
}
