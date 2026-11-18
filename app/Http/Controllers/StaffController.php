<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusMail;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\BrevoMailer;
use App\Services\Money\PhpMoneyFormatter;
use App\Services\Money\SymbolStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function dashboard()
    {
        return $this->index();
    }

    public function index()
    {
        $products = Product::orderBy('category')->orderBy('name')->get();
        $orders = Order::with(['user', 'items.product'])->latest()->get();
        $pickupOrders = $orders->where('fulfillment_type', '!=', 'delivery')->values();
        $deliveryOrders = $orders->where('fulfillment_type', 'delivery')->values();

        $paidTotalMinor = Order::where('is_paid', true)
            ->where('status', '!=', 'cancelled')
            ->sum('total_minor');
        $lowStockProducts = Product::where('stock_quantity', '<=', 5)->get();
        $staffUsers = User::where('role', 'staff')->orderBy('name')->get();
        $activityLogs = ActivityLog::with('user')->latest()->take(25)->get();

        $formatter = new PhpMoneyFormatter(new SymbolStrategy());
        $defaults = [
            'hero_badge' => 'Authentic Japanese Goods • Project 8 Pickup & Delivery',
            'hero_image' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=1200&q=80',
            'hero_title' => 'Everyday Japanese Comforts, Reserved for Pickup or Delivery.',
            'hero_subtitle' => 'Browse curated snacks, matcha, drinks, and skincare online—reserve your basket for store pickup or same-day courier delivery.',
            'about_heading' => 'About Kohan Mart (湖畔マート)',
            'about_body_1' => 'Kohan Mart brings everyday Japanese neighborhood essentials closer to home in Project 8, Quezon City.',
            'about_body_2' => 'Reserve your favorites online, earn loyalty points on every purchase, and pick up at our store or have it delivered.',
            'contact_address' => '41 Insurance, Project 8, Quezon City',
            'contact_hours' => 'Daily • 9:00 AM – 8:00 PM',
            'contact_phone_email' => '+63 917 000 0000 • hello@kohanmart.ph',
        ];
        $settings = collect($defaults)->merge(SiteSetting::all()->pluck('value', 'key'));

        return view('staff.dashboard', compact(
            'products',
            'orders',
            'pickupOrders',
            'deliveryOrders',
            'paidTotalMinor',
            'lowStockProducts',
            'staffUsers',
            'activityLogs',
            'formatter',
            'settings'
        ));
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price_minor' => ['required', 'integer', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $product = Product::create($validated);

        $this->logActivity('Added Product', "Added {$product->name} ({$product->sku}) with {$product->stock_quantity} stock.");

        return back()
            ->with('active_tab', 'inventory')
            ->with('success', "Added {$product->name} to Kohan Mart catalog.");
    }

    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'price_minor' => ['required', 'integer', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product->update([
            'price_minor' => $validated['price_minor'],
            'stock_quantity' => $validated['stock_quantity'],
        ]);

        $this->logActivity('Updated Product', "Updated {$product->name} stock to {$product->stock_quantity} and price to {$product->price_minor} centavos.");

        return back()
            ->with('active_tab', 'inventory')
            ->with('success', "Updated {$product->name}.");
    }

    public function destroyProduct(Product $product)
    {
        $name = $product->name;
        $product->delete();

        $this->logActivity('Removed Product', "Removed {$name} from the catalog.");

        return back()
            ->with('active_tab', 'inventory')
            ->with('success', "Removed {$name} from catalog.");
    }

    public function updateOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,ready_for_pickup,out_for_delivery,completed,cancelled'],
            'cancellation_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:120'],
            'custom_reason' => ['nullable', 'required_if:cancellation_reason,Other', 'string', 'max:120'],
            'cancellation_explanation' => ['required_if:status,cancelled', 'nullable', 'string', 'min:5', 'max:500'],
        ], [
            'cancellation_reason.required_if' => 'Please select a reason for cancelling this order.',
            'custom_reason.required_if' => 'Please specify your custom cancellation reason.',
            'cancellation_explanation.required_if' => 'Please provide a brief explanation for cancelling this order.',
            'cancellation_explanation.min' => 'Please enter at least 5 characters for the cancellation explanation.',
        ]);

        $newStatus = $validated['status'];
        $finalReason = null;
        $finalExplanation = null;
        if ($newStatus === 'cancelled') {
            $finalReason = ($validated['cancellation_reason'] ?? '') === 'Other'
                ? trim((string) $validated['custom_reason'])
                : trim((string) $validated['cancellation_reason']);
            $finalExplanation = trim((string) $validated['cancellation_explanation']);
        }

        $previousStatus = $order->status;
        $stockDeductedStatuses = ['confirmed', 'ready_for_pickup', 'out_for_delivery', 'completed'];
        $statusNote = '';

        try {
            DB::transaction(function () use ($order, $previousStatus, $newStatus, $finalReason, $finalExplanation, $stockDeductedStatuses, &$statusNote) {
                $order->load(['items.product', 'user']);

                $wasStockDeducted = in_array($previousStatus, $stockDeductedStatuses);
                $shouldStockBeDeducted = in_array($newStatus, $stockDeductedStatuses);

                $refundedMinor = $order->refunded_minor;
                $deliveryFeeForfeited = $order->delivery_fee_forfeited;
                $stockReturned = $order->stock_returned;

                if ($previousStatus !== 'cancelled' && $newStatus === 'cancelled') {
                    if ($order->hasLeftStore()) {
                        $stockReturned = false;
                        $statusNote .= ' Stock held pending physical rider return to shelf.';
                    } elseif ($wasStockDeducted && !$shouldStockBeDeducted) {
                        foreach ($order->items as $item) {
                            if ($item->product) {
                                $item->product->increment('stock_quantity', $item->quantity);
                            }
                        }
                        $stockReturned = true;
                        $statusNote .= ' Inventory restored immediately.';
                    }

                    $courierAssignedOrEnRoute = $order->isDelivery()
                        && ($order->courier_status ?? 'order_placed') !== 'order_placed';

                    if ($courierAssignedOrEnRoute && $order->delivery_fee_minor > 0) {
                        $deliveryFeeForfeited = true;
                        $refundedMinor = max(0, $order->total_minor - $order->delivery_fee_minor);
                        $statusNote .= ' Delivery fee forfeited (₱' . number_format($order->delivery_fee_minor / 100, 2) . '); refunded ₱' . number_format($refundedMinor / 100, 2) . '.';
                    } else {
                        $deliveryFeeForfeited = false;
                        $refundedMinor = $order->total_minor;
                        $statusNote .= ' Full refund (₱' . number_format($refundedMinor / 100, 2) . ').';
                    }

                    if ($order->user) {
                        if ($order->points_used > 0) {
                            $order->user->increment('points_balance', $order->points_used);
                            PointTransaction::create([
                                'user_id' => $order->user->id,
                                'order_id' => $order->id,
                                'type' => 'refunded',
                                'points' => $order->points_used,
                                'description' => "Refunded {$order->points_used} pts from cancelled order {$order->order_number}",
                            ]);
                        }

                        if ($order->points_awarded && $order->points_earned > 0) {
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
                    }
                }

                $courierStatus = $order->courier_status;
                if ($order->isDelivery()) {
                    $courierStatus = match ($newStatus) {
                        'confirmed' => 'order_placed',
                        'ready_for_pickup' => 'driver_assigned',
                        'out_for_delivery' => 'on_the_way',
                        'completed' => 'delivered',
                        'cancelled' => 'cancelled',
                        default => $courierStatus,
                    };
                }

                $order->update([
                    'status' => $newStatus,
                    'cancelled_by' => $newStatus === 'cancelled' ? 'admin' : $order->cancelled_by,
                    'cancellation_reason' => $newStatus === 'cancelled' ? $finalReason : $order->cancellation_reason,
                    'cancellation_explanation' => $newStatus === 'cancelled' ? $finalExplanation : $order->cancellation_explanation,
                    'courier_status' => $courierStatus,
                    'is_paid' => true,
                    'paid_at' => $order->paid_at ?? now(),
                    'refunded_minor' => $refundedMinor,
                    'delivery_fee_forfeited' => $deliveryFeeForfeited,
                    'stock_returned' => $stockReturned,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()
                ->with('active_tab', 'orders')
                ->with('active_order_mode', $request->input('current_order_mode', $order->isDelivery() ? 'delivery' : 'pickup'))
                ->with('active_order_tab', $request->input('current_order_tab', 'all'))
                ->with('error', $e->getMessage());
        }

        if ($previousStatus !== $newStatus && in_array($newStatus, ['confirmed', 'ready_for_pickup', 'out_for_delivery', 'completed', 'cancelled'])) {
            $order->refresh()->load('user');
            if ($order->user?->email) {
                BrevoMailer::sendMailable(
                    $order->user->email,
                    $order->user->name,
                    new OrderStatusMail($order)
                );
            }
        }

        $displayLabel = $newStatus === 'confirmed' ? 'NEW ORDER' : strtoupper(str_replace('_', ' ', $newStatus));
        $reasonLog = $newStatus === 'cancelled' && $finalReason ? " Reason: {$finalReason} ({$finalExplanation})." : '';
        $this->logActivity('Updated Order', "Changed {$order->order_number} to {$displayLabel}.{$reasonLog}{$statusNote}");

        return back()
            ->with('active_tab', 'orders')
            ->with('active_order_mode', $request->input('current_order_mode', $order->isDelivery() ? 'delivery' : 'pickup'))
            ->with('active_order_tab', $request->input('current_order_tab', 'all'))
            ->with('success', "Order {$order->order_number} updated to {$displayLabel}.{$statusNote}");
    }

    public function confirmStockReturn(Request $request, Order $order)
    {
        if (!$order->isAwaitingStockReturn()) {
            return back()
                ->with('active_tab', 'orders')
                ->with('error', "Order {$order->order_number} has already had its inventory reconciled.");
        }

        DB::transaction(function () use ($order) {
            $order->load('items.product');

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }

            $order->update(['stock_returned' => true]);

            $this->logActivity(
                'Confirmed Shelf Return',
                "Confirmed physical return of {$order->order_number} from Lalamove rider and restored website stock."
            );
        });

        return back()
            ->with('active_tab', 'orders')
            ->with('active_order_tab', $request->input('current_order_tab', 'cancelled'))
            ->with('success', "Physical items from {$order->order_number} confirmed returned to shelf and website stock updated.");
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'role' => 'staff',
            'points_balance' => 0,
        ]);

        $this->logActivity('Created Staff', "Created staff/admin account for {$validated['name']} ({$validated['email']}).");

        return back()
            ->with('active_tab', 'staff')
            ->with('success', "Admin / Staff account ({$validated['email']}) created.");
    }

    public function updateCms(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->logActivity('Updated CMS', 'Updated storefront page settings.');

        return back()
            ->with('active_tab', 'cms')
            ->with('success', 'Storefront CMS content saved.');
    }

    private function logActivity(string $action, string $details): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'details' => $details,
        ]);
    }
}