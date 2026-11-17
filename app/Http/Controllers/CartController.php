<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Money\PhpMoneyFormatter;
use App\Services\Money\SymbolStrategy;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        $items = [];
        $cleanedCart = [];

        foreach ($cart as $productId => $qty) {
            if ($products->has($productId) && $qty > 0) {
                $items[] = [
                    'product' => $products->get($productId),
                    'quantity' => (int) $qty,
                ];
                $cleanedCart[$productId] = (int) $qty;
            }
        }

        if ($cleanedCart !== $cart) {
            session(['cart' => $cleanedCart]);
        }

        $formatter = new PhpMoneyFormatter(new SymbolStrategy());

        return view('store.cart', compact('items', 'formatter'));
    }

    public function add(Request $request, Product $product)
    {
        $qtyToAdd = max(1, (int) $request->input('quantity', 1));
        $cart = session('cart', []);
        $currentQty = (int) ($cart[$product->id] ?? 0);
        $newQty = $currentQty + $qtyToAdd;

        if ($product->stock_quantity <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => "{$product->name} is currently out of stock.",
                    'cart_count' => array_sum($cart),
                ], 409);
            }
            return back()->with('error', "{$product->name} is currently out of stock.");
        }

        if ($newQty > $product->stock_quantity) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => "Only {$product->stock_quantity} unit(s) of {$product->name} available in store.",
                    'cart_count' => array_sum($cart),
                ], 409);
            }
            return back()->with('error', "Only {$product->stock_quantity} unit(s) of {$product->name} available in store.");
        }

        $cart[$product->id] = $newQty;
        session(['cart' => $cart]);
        $cartCount = array_sum($cart);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => "Added {$product->name} to your basket.",
                'cart_count' => $cartCount,
                'item_qty' => $newQty,
            ]);
        }

        return back()->with('success', "Added {$product->name} to your basket.");
    }

    public function update(Request $request, Product $product)
    {
        $cart = session('cart', []);
        $currentQty = (int) ($cart[$product->id] ?? 0);

        if ($request->has('delta')) {
            $newQty = $currentQty + (int) $request->input('delta');
        } else {
            $newQty = (int) $request->input('quantity', $currentQty);
        }

        $removed = false;

        if ($newQty <= 0) {
            unset($cart[$product->id]);
            $newQty = 0;
            $removed = true;
        } elseif ($newQty > $product->stock_quantity) {
            $message = $product->stock_quantity === 0
                ? "{$product->name} is now out of stock."
                : "Maximum available stock for {$product->name} is {$product->stock_quantity}.";

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'cart_count' => array_sum($cart),
                    'item_qty' => $currentQty,
                    'available' => (int) $product->stock_quantity,
                ], 409);
            }

            return back()->with('error', $message);
        } else {
            $cart[$product->id] = $newQty;
        }

        session(['cart' => $cart]);

        // Recalculate fresh subtotal
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $subtotalMinor = 0;
        foreach ($cart as $pid => $q) {
            if ($products->has($pid)) {
                $subtotalMinor += $products->get($pid)->price_minor * $q;
            }
        }

        $cartCount = array_sum($cart);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'removed' => $removed,
                'product_id' => $product->id,
                'item_qty' => $newQty,
                'available' => (int) $product->stock_quantity,
                'cart_count' => $cartCount,
                'subtotal_minor' => $subtotalMinor,
            ]);
        }

        return back();
    }

    public function remove(Request $request, Product $product)
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'cart_count' => array_sum($cart),
            ]);
        }

        return back()->with('success', "Removed {$product->name} from your basket.");
    }
}