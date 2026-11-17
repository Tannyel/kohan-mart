<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'staff@kohanmart.test'],
            [
                'name' => 'Kohan Staff',
                'role' => 'staff',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@kohanmart.test'],
            [
                'name' => 'Kenji Sato',
                'role' => 'customer',
                'password' => Hash::make('password'),
            ]
        );

        $products = [
            [
                'sku' => 'KM-FOOD-001',
                'name' => 'Ichiran Instant Hakata Ramen (5-Pack)',
                'category' => 'Foods',
                'description' => 'Authentic Fukuoka-style tonkotsu ramen with spicy red seasoning.',
                'price_minor' => 125000,
                'stock_quantity' => 15,
                'image_url' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'sku' => 'KM-FOOD-002',
                'name' => 'Meiji Apollo Strawberry Chocolate',
                'category' => 'Foods',
                'description' => 'Classic cone-shaped Japanese strawberry and milk chocolate treats.',
                'price_minor' => 9500,
                'stock_quantity' => 40,
                'image_url' => 'https://images.unsplash.com/photo-1582176604856-e824b4736522?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'sku' => 'KM-DRNK-001',
                'name' => 'Ito En Oi Ocha Unsweetened Green Tea (500ml)',
                'category' => 'Drinks',
                'description' => 'Brewed Japanese green tea with zero sugar and crisp aroma.',
                'price_minor' => 6500,
                'stock_quantity' => 50,
                'image_url' => 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'sku' => 'KM-DRNK-002',
                'name' => 'Suntory Boss Rainbow Mountain Blend Coffee',
                'category' => 'Drinks',
                'description' => 'Rich Japanese canned coffee crafted from Guatemalan beans.',
                'price_minor' => 7500,
                'stock_quantity' => 30,
                'image_url' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'sku' => 'KM-SKIN-001',
                'name' => 'Biore UV Aqua Rich Watery Essence SPF50+',
                'category' => 'Skincare',
                'description' => 'Lightweight, non-greasy Japanese sunscreen with hyaluronic acid.',
                'price_minor' => 54900,
                'stock_quantity' => 12,
                'image_url' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'sku' => 'KM-SKIN-002',
                'name' => 'Hada Labo Gokujyun Hydrating Lotion (170ml)',
                'category' => 'Skincare',
                'description' => 'Deeply moisturizing toner with 4 types of hyaluronic acid.',
                'price_minor' => 68500,
                'stock_quantity' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&q=80',
            ],
        ];

        foreach ($products as $item) {
            Product::updateOrCreate(['sku' => $item['sku']], $item);
        }
    }
}