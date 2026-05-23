<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Order created successfully',
            'data' => $request->validated(),
        ], 201);
    }
}
