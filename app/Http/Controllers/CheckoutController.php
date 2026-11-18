<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusMail;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Services\BrevoMailer;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function store(Request $request, DeliveryService $deliveryService)
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Your basket is empty.',
                ], 422);
            }
            return redirect()->route('cart.index')->with('error', 'Your basket is empty.');
        }

        $cartProducts = Product::whereIn('id', array_keys($cart))->get();
        $hasPerishables = $cartProducts->contains(function ($p) {
            return in_array(strtolower(trim((string) $p->category)), ['foods', 'drinks'], true);
        });
        $maxPickupAt = $hasPerishables ? now()->addDay() : now()->addDays(7);
        $limitLabel = $hasPerishables
            ? '1 day (24 hours) for orders containing Foods or Drinks'
            : '1 week (7 days)';

        $validated = $request->validate([
            'fulfillment_type' => ['required', 'in:pickup,delivery'],
            'pickup_scheduled_at' => [
                'required_if:fulfillment_type,pickup',
                'nullable',
                'date',
                'after:now',
                'before_or_equal:' . $maxPickupAt->format('Y-m-d H:i:s'),
            ],
            'delivery_address' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'min:5', 'max:255'],
            'delivery_recipient_phone' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'min:7', 'max:30'],
            'points_to_use' => ['nullable', 'numeric', 'min:0'],
        ], [
            'pickup_scheduled_at.required_if' => 'Please select your scheduled pickup date and time.',
            'pickup_scheduled_at.before_or_equal' => "Store pickup must be scheduled within {$limitLabel}.",
            'delivery_address.required_if' => 'Please enter your delivery address.',
            'delivery_recipient_phone.required_if' => 'Please enter a mobile number for the delivery rider.',
        ]);

        $isDelivery = $validated['fulfillment_type'] === 'delivery';
        $quote = $isDelivery ? $deliveryService->getQuote($validated['delivery_address']) : null;

        /** @var \App\Models\User $user */
        $user = Auth::user();

        ksort($cart);

        try {
            $result = DB::transaction(function () use ($cart, $validated, $user, $isDelivery, $quote, $deliveryService) {
                $subtotalMinor = 0;
                $resolvedItems = [];

                foreach ($cart as $productId => $qty) {
                    $product = Product::where('id', $productId)->lockForUpdate()->first();

                    if (!$product) {
                        throw new \RuntimeException('One of the items in your basket is no longer available.');
                    }

                    if ($product->stock_quantity < $qty) {
                        $msg = $product->stock_quantity === 0
                            ? "Sold out! Another customer just purchased the last {$product->name} a moment before you."
                            : "Insufficient stock for {$product->name} ({$product->stock_quantity} remaining).";
                        throw new \RuntimeException($msg);
                    }

                    $lineTotalMinor = $product->price_minor * $qty;
                    $subtotalMinor += $lineTotalMinor;

                    $resolvedItems[] = [
                        'product' => $product,
                        'quantity' => (int) $qty,
                        'unit_price_minor' => $product->price_minor,
                        'line_total_minor' => $lineTotalMinor,
                    ];
                }

                $acquiredAt = now();
                $paidAtMicro = $acquiredAt->format('Y-m-d H:i:s.u');
                $orderNumber = 'KM-' . strtoupper(Str::random(8));

                $deliveryFeeMinor = $isDelivery ? ($quote['fee_minor'] ?? 0) : 0;
                $grossMinor = $subtotalMinor + $deliveryFeeMinor;
                $grossPesos = round($grossMinor / 100, 2);

                $requestedPoints = (float) ($validated['points_to_use'] ?? 0);
                $pointsToUse = min($requestedPoints, (float) $user->points_balance, $grossPesos);
                $discountMinor = (int) round($pointsToUse * 100);
                $finalTotalMinor = max(0, $grossMinor - $discountMinor);
                $finalTotalPesos = round($finalTotalMinor / 100, 2);

                $earnRate = $user->isBirthdayMonth() ? 0.20 : 0.10;
                $pointsEarned = round($finalTotalPesos * $earnRate, 2);

                $courierData = $isDelivery ? $deliveryService->dispatchCourier($orderNumber) : [];

                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => $orderNumber,
                    'status' => 'confirmed',
                    'fulfillment_type' => $validated['fulfillment_type'],
                    'payment_method' => 'pay_online',
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
                    'subtotal_minor' => $subtotalMinor,
                    'points_used' => $pointsToUse,
                    'points_discount_minor' => $discountMinor,
                    'delivery_fee_minor' => $deliveryFeeMinor,
                    'total_minor' => $finalTotalMinor,
                    'refunded_minor' => 0,
                    'delivery_fee_forfeited' => false,
                    'stock_returned' => true,
                    'points_earned' => $pointsEarned,
                    'points_awarded' => $pointsEarned > 0,
                ]);

                foreach ($resolvedItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'unit_price_minor' => $item['unit_price_minor'],
                        'line_total_minor' => $item['line_total_minor'],
                    ]);

                    $item['product']->decrement('stock_quantity', $item['quantity']);
                }

                if ($pointsToUse > 0) {
                    $user->decrement('points_balance', $pointsToUse);
                    PointTransaction::create([
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'type' => 'redeemed',
                        'points' => -$pointsToUse,
                        'description' => "Redeemed on online order {$order->order_number}",
                    ]);
                }

                if ($pointsEarned > 0) {
                    $user->increment('points_balance', $pointsEarned);
                    PointTransaction::create([
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'type' => 'earned',
                        'points' => $pointsEarned,
                        'description' => "Earned from paid online order {$order->order_number}",
                    ]);
                }

                $modeLabel = $isDelivery ? 'Delivery' : 'Store Pickup';
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => "Atomic Checkout ({$modeLabel})",
                    'details' => "Order {$order->order_number} ({$modeLabel}) locked stock at {$paidAtMicro}.",
                ]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'conflict' => true,
                    'server_time_micro' => now()->format('Y-m-d H:i:s.u'),
                    'message' => $e->getMessage(),
                ], 409);
            }

            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        session()->forget('cart');

        $result->load('user');
        if ($result->user?->email) {
            BrevoMailer::sendMailable(
                $result->user->email,
                $result->user->name,
                new OrderStatusMail($result)
            );
        }

        $redirectUrl = route('orders.receipt', $result);
        $successMessage = $result->isDelivery()
            ? "Payment confirmed! Order {$result->order_number} is confirmed and booked for Lalamove Express delivery."
            : "Payment confirmed at {$result->paid_at_micro}! Order {$result->order_number} is confirmed for store pickup.";

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'order_number' => $result->order_number,
                'paid_at_micro' => $result->paid_at_micro,
                'message' => $successMessage,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('success', $successMessage);
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$order->canCustomerCancel()) {
            return back()->with(
                'error',
                'A Lalamove rider has already been assigned to this delivery. Please call Kohan Mart (+63 917 000 0000) to request an in-transit cancellation.'
            );
        }

        $reasonData = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:120'],
            'custom_reason' => ['nullable', 'required_if:cancellation_reason,Other', 'string', 'max:120'],
            'cancellation_explanation' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancellation_reason.required' => 'Please select a reason for cancelling this order.',
            'custom_reason.required_if' => 'Please specify your custom cancellation reason.',
            'cancellation_explanation.required' => 'Please provide a brief explanation for your cancellation.',
            'cancellation_explanation.min' => 'Please provide at least 5 characters explaining your cancellation.',
        ]);

        $finalReason = $reasonData['cancellation_reason'] === 'Other'
            ? trim($reasonData['custom_reason'])
            : $reasonData['cancellation_reason'];
        $finalExplanation = trim($reasonData['cancellation_explanation']);

        DB::transaction(function () use ($order, $finalReason, $finalExplanation) {
            $order->load(['items.product', 'user']);

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }

            if ($order->points_used > 0 && $order->user) {
                $order->user->increment('points_balance', $order->points_used);
                PointTransaction::create([
                    'user_id' => $order->user->id,
                    'order_id' => $order->id,
                    'type' => 'refunded',
                    'points' => $order->points_used,
                    'description' => "Refunded {$order->points_used} pts from cancelled order {$order->order_number}",
                ]);
            }

            if ($order->points_awarded && $order->points_earned > 0 && $order->user) {
                $deduct = min((float) $order->user->points_balance, (float) $order->points_earned);
                if ($deduct > 0) {
                    $order->user->decrement('points_balance', $deduct);
                    PointTransaction::create([
                        'user_id' => $order->user->id,
                        'order_id' => $order->id,
                        'type' => 'redeemed',
                        'points' => -$deduct,
                        'description' => "Reversed points from cancelled order {$order->order_number}",
                    ]);
                }
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_by' => 'customer',
                'cancellation_reason' => $finalReason,
                'cancellation_explanation' => $finalExplanation,
                'courier_status' => $order->isDelivery() ? 'cancelled' : null,
                'refunded_minor' => $order->total_minor,
                'delivery_fee_forfeited' => false,
                'stock_returned' => true,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Customer Cancelled Order',
                'details' => "Customer cancelled {$order->order_number} ({$finalReason}: {$finalExplanation}). Full refund (₱" . number_format($order->total_minor / 100, 2) . ") & stock restored.",
            ]);
        });

        $order->refresh()->load('user');
        if ($order->user?->email) {
            BrevoMailer::sendMailable(
                $order->user->email,
                $order->user->name,
                new OrderStatusMail($order)
            );
        }

        return back()->with('success', "Order {$order->order_number} has been cancelled and a full refund of ₱" . number_format($order->total_minor / 100, 2) . " has been initiated.");
    }

    public function receipt(Order $order)
    {
        if ($order->user_id !== Auth::id() && !(Auth::user()->isStaff() || (Auth::user()->is_admin ?? false))) {
            abort(403);
        }

        $order->load(['items.product', 'user']);

        $formatter = new class {
            public function format($minorAmount)
            {
                return '₱' . number_format(($minorAmount ?? 0) / 100, 2);
            }
        };

        if (view()->exists('store.receipt')) {
            return view('store.receipt', compact('order', 'formatter'));
        }

        if (view()->exists('orders.receipt')) {
            return view('orders.receipt', compact('order', 'formatter'));
        }

        return redirect()->route('store.orders');
    }
}