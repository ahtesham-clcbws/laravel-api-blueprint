<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products with categories.
     */
    public function index(): JsonResponse
    {
        $products = Product::with('category')->get();

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create([
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('name')),
            'price' => $request->input('price'),
            'description' => $request->input('description', 'No description provided.'),
            'category_id' => $request->input('category_id'),
        ]);

        $product->load('category');

        return response()->json([
            'message' => 'Product stored successfully',
            'data' => $product,
        ], 201);
    }
}
