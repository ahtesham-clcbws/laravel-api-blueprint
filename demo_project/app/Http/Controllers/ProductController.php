<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Product stored successfully',
            'data' => $request->validated(),
        ], 201);
    }
}
