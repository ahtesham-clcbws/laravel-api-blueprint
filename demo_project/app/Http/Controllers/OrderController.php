<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of all orders with relational users and items.
     */
    public function index(): JsonResponse
    {
        $orders = Order::with(['user', 'items.product'])->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * Store a newly created order and its relational items.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        // For testing, fallback to first user if not authenticated
        $userId = Auth::id() ?? User::first()?->id;

        if (!$userId) {
            // Create a default test user if none exists
            $user = User::create([
                'name' => 'Demo Customer',
                'email' => $request->input('customer_email'),
                'password' => bcrypt('password123'),
            ]);
            $userId = $user->id;
        }

        $order = DB::transaction(function () use ($request, $userId) {
            // Create the base order
            $order = Order::create([
                'user_id' => $userId,
                'status' => 'pending',
                'total_amount' => 0.00,
            ]);

            $totalAmount = 0;

            foreach ($request->input('items') as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $price = (float) $product->price;
                $quantity = (int) $itemData['quantity'];
                $itemTotal = $price * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);

                $totalAmount += $itemTotal;
            }

            // Update order with total calculated amount
            $order->update([
                'total_amount' => $totalAmount,
            ]);

            return $order;
        });

        // Load relational data for API output representation
        $order->load(['user', 'items.product']);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }
}
