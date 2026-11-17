<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockCheckController extends Controller
{
    /**
     * Real-time stock validation for the active customer's session basket.
     */
    public function validateCart(Request $request): JsonResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return response()->json([
                'ok' => false,
                'server_time_micro' => now()->format('Y-m-d H:i:s.u'),
                'message' => 'Your pickup basket is empty.',
                'conflicts' => [],
                'items' => [],
            ], 422);
        }

        $products = Product::whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $conflicts = [];
        $items = [];

        foreach ($cart as $productId => $requestedQty) {
            $product = $products->get($productId);

            if (!$product) {
                $conflicts[] = [
                    'product_id' => (int) $productId,
                    'name' => 'Removed Catalog Item',
                    'requested' => (int) $requestedQty,
                    'available' => 0,
                    'reason' => 'This item is no longer available in the catalog.',
                ];
                continue;
            }

            $available = (int) $product->stock_quantity;
            $requested = (int) $requestedQty;

            $items[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'available' => $available,
                'requested' => $requested,
                'in_stock' => $available >= $requested,
            ];

            if ($available < $requested) {
                $conflicts[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'requested' => $requested,
                    'available' => $available,
                    'reason' => $available === 0
                        ? "Sold out! Another shopper just claimed the last {$product->name}."
                        : "Only {$available} left for {$product->name} (you requested {$requested}).",
                ];
            }
        }

        $isOk = empty($conflicts);

        return response()->json([
            'ok' => $isOk,
            'server_time_micro' => now()->format('Y-m-d H:i:s.u'),
            'message' => $isOk
                ? 'All basket items are available and ready for atomic reservation.'
                : $conflicts[0]['reason'],
            'conflicts' => $conflicts,
            'items' => $items,
        ], $isOk ? 200 : 409);
    }
}