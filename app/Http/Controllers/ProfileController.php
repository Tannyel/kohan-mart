<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Services\DeliveryService;
use App\Services\Money\PhpMoneyFormatter;
use App\Services\Money\SymbolStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $transactions = $user->pointTransactions()->with('order')->get();
        $formatter = new PhpMoneyFormatter(new SymbolStrategy());

        return view('profile.index', compact('user', 'transactions', 'formatter'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'birthday' => ['nullable', 'date', 'before:today'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'birthday' => $validated['birthday'] ?? null,
        ]);

        return back()->with('success', 'Your profile details have been updated.');
    }

    public function rewards(Request $request)
    {
        $user = $request->user();

        $query = Product::where('stock_quantity', '>', 0);

        if ($request->filled('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        $products = $query->orderBy('price_minor')->get();
        $categories = ['All', 'Foods', 'Drinks', 'Skincare', 'Other'];

        return view('profile.rewards', compact('user', 'products', 'categories'));
    }

    public function redeemProduct(Request $request, Product $product, DeliveryService $deliveryService)
    {
        $validated = $request->validate([
            'fulfillment_type' => ['required', 'in:pickup,delivery'],
            'pickup_scheduled_at' => ['required_if:fulfillment_type,pickup', 'nullable', 'date', 'after:now'],
            'delivery_address' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'min:5', 'max:255'],
            'delivery_recipient_phone' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'min:7', 'max:30'],
        ], [
            'pickup_scheduled_at.required_if' => 'Please select your scheduled pickup date and time.',
            'delivery_address.required_if' => 'Please enter your delivery address.',
            'delivery_recipient_phone.required_if' => 'Please enter a mobile number for the delivery rider.',
        ]);

        $user = $request->user();
        $requiredPoints = round($product->price_minor / 100, 2);

        if ($product->stock_quantity < 1) {
            return back()->with('error', 'Sorry, this item just went out of stock.');
        }

        if ($user->points_balance < $requiredPoints) {
            return back()->with('error', "You need {$requiredPoints} pts to redeem {$product->name}.");
        }

        $isDelivery = $validated['fulfillment_type'] === 'delivery';
        $quote = $isDelivery ? $deliveryService->getQuote($validated['delivery_address']) : null;

        if ($isDelivery && !$quote) {
            return back()->with('error', 'Unable to fetch Lalamove delivery quote for this address.');
        }

        $orderNumber = 'KM-REW-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        $acquiredAt = now();
        $paidAtMicro = $acquiredAt->format('Y-m-d H:i:s.u');

        $deliveryFeeMinor = $isDelivery ? ($quote['fee_minor'] ?? 0) : 0;
        $courierData = $isDelivery ? $deliveryService->dispatchCourier($orderNumber) : [];

        $order = DB::transaction(function () use ($user, $product, $requiredPoints, $validated, $isDelivery, $quote, $deliveryFeeMinor, $orderNumber, $acquiredAt, $paidAtMicro, $courierData) {
            
            $product->decrement('stock_quantity', 1);
            $user->decrement('points_balance', $requiredPoints);

            $order = Order::create([
                'user_id' => $user->id,
                'idempotency_key' => (string) Str::uuid(),
                'order_number' => $orderNumber,
                'status' => 'confirmed',
                'fulfillment_type' => $validated['fulfillment_type'],
                'payment_method' => 'points_redemption',
                'is_paid' => true,
                'paid_at' => $acquiredAt,
                'paid_at_micro' => $paidAtMicro,
                'pickup_scheduled_at' => $isDelivery ? null : $validated['pickup_scheduled_at'],
                'delivery_address' => $isDelivery ? ($quote['normalized_address'] ?? $validated['delivery_address']) : null,
                'delivery_recipient_phone' => $isDelivery ? $validated['delivery_recipient_phone'] : null,
                'delivery_lat' => $isDelivery ? ($quote['lat'] ?? null) : null,
                'delivery_lng' => $isDelivery ? ($quote['lng'] ?? null) : null,
                'delivery_distance_km' => $isDelivery ? ($quote['distance_km'] ?? null) : null,
                'courier_order_id' => $courierData['courier_order_id'] ?? null,
                'courier_status' => $courierData['courier_status'] ?? null,
                'courier_driver_name' => $courierData['courier_driver_name'] ?? null,
                'courier_driver_plate' => $courierData['courier_driver_plate'] ?? null,
                'courier_driver_phone' => $courierData['courier_driver_phone'] ?? null,
                'courier_share_link' => $courierData['courier_share_link'] ?? null,
                'subtotal_minor' => 0, // Item itself is free with points
                'points_used' => $requiredPoints,
                'points_discount_minor' => $product->price_minor,
                'delivery_fee_minor' => $deliveryFeeMinor,
                'total_minor' => $deliveryFeeMinor, // Only delivery fee is payable if delivery is chosen
                'points_earned' => 0,
                'points_awarded' => true,
                'stock_returned' => true,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price_minor' => 0,
                'line_total_minor' => 0,
            ]);

            PointTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => 'redeemed',
                'points' => -$requiredPoints,
                'description' => "Redeemed {$product->name} for free ({$order->order_number})",
            ]);

            return $order;
        });

        $successMsg = $isDelivery
            ? "Successfully redeemed {$product->name} for free using points! Lalamove delivery booked ({$order->order_number})."
            : "Successfully redeemed {$product->name} for free! Your store pickup reservation ({$order->order_number}) is confirmed.";

        return redirect()->route('orders.receipt', $order)->with('success', $successMsg);
    }
}