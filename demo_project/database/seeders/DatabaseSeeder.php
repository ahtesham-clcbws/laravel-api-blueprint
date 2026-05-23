<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create default user
        $user = User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
        ]);

        // 2. Create categories
        $electronics = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $apparel = Category::create([
            'name' => 'Apparel',
            'slug' => 'apparel',
        ]);

        // 3. Create products
        $iphone = Product::create([
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'price' => 999.99,
            'description' => 'The latest premium Apple smartphone with titanium framing.',
            'category_id' => $electronics->id,
        ]);

        $headphones = Product::create([
            'name' => 'Sony WH-1000XM5',
            'slug' => 'sony-wh-1000xm5',
            'price' => 349.99,
            'description' => 'Industry leading noise cancelling wireless headphones.',
            'category_id' => $electronics->id,
        ]);

        $shirt = Product::create([
            'name' => 'Premium Cotton T-Shirt',
            'slug' => 'premium-cotton-t-shirt',
            'price' => 29.99,
            'description' => 'Ultra soft combed cotton comfort t-shirt.',
            'category_id' => $apparel->id,
        ]);

        // 4. Create a test order
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total_amount' => 1379.97,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $iphone->id,
            'quantity' => 1,
            'price' => 999.99,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $headphones->id,
            'quantity' => 1,
            'price' => 349.99,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $shirt->id,
            'quantity' => 1,
            'price' => 29.99,
        ]);
    }
}
