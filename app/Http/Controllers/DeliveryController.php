<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeliveryController extends Controller
{
    public const STAGES = [
        'order_placed' => [
            'step' => 1,
            'label' => 'Order Placed & Packing',
            'description' => 'Kohan Mart received your paid order and is preparing your Japanese items.',
        ],
        'driver_assigned' => [
            'step' => 2,
            'label' => 'Rider Assigned',
            'description' => 'Your Lalamove Express motorcycle rider is heading to Kohan Mart (Project 8).',
        ],
        'picked_up' => [
            'step' => 3,
            'label' => 'Picked Up at Store',
            'description' => 'Rider has collected and verified your sealed Kohan Mart bag at the counter.',
        ],
        'on_the_way' => [
            'step' => 4,
            'label' => 'On the Way to Address',
            'description' => 'Rider is currently in transit to your drop-off location.',
        ],
        'delivered' => [
            'step' => 5,
            'label' => 'Delivered & Received',
            'description' => 'Package delivered safely to recipient. Thank you for shopping at Kohan Mart!',
        ],
    ];

    public function quote(Request $request, DeliveryService $deliveryService)
    {
        $validated = $request->validate([
            'delivery_address' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        return response()->json($deliveryService->getQuote($validated['delivery_address']));
    }

    public function track(Order $order)
    {
        abort_unless($order->isDelivery(), 404);

        $order->load(['items.product', 'user']);
        $stages = self::STAGES;
        $currentKey = $order->courier_status ?: 'order_placed';
        $currentStage = $stages[$currentKey] ?? $stages['order_placed'];

        return view('delivery.track', compact('order', 'stages', 'currentKey', 'currentStage'));
    }

    public function statusJson(Order $order)
    {
        abort_unless($order->isDelivery(), 404);

        $stages = self::STAGES;
        $currentKey = $order->courier_status ?: 'order_placed';
        $currentStage = $stages[$currentKey] ?? $stages['order_placed'];

        return response()->json([
            'ok' => true,
            'order_status' => $order->status,
            'courier_status' => $currentKey,
            'step' => $currentStage['step'],
            'label' => $currentStage['label'],
            'description' => $currentStage['description'],
            'updated_at' => $order->updated_at?->format('h:i:s A'),
        ]);
    }

    public function advanceStage(Request $request, Order $order)
    {
        abort_unless($order->isDelivery(), 404);

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cancelled orders cannot progress in delivery.');
        }

        $keys = array_keys(self::STAGES);
        $currentKey = $order->courier_status ?: 'order_placed';

        if ($request->filled('target_stage') && in_array($request->input('target_stage'), $keys, true)) {
            $nextKey = $request->input('target_stage');
        } else {
            $idx = array_search($currentKey, $keys, true);
            $nextKey = $keys[min(count($keys) - 1, ($idx === false ? 0 : $idx + 1))];
        }

        $orderStatus = match ($nextKey) {
            'order_placed', 'driver_assigned' => 'confirmed',
            'picked_up', 'on_the_way' => 'out_for_delivery',
            'delivered' => 'completed',
            default => $order->status,
        };

        $previousOrderStatus = $order->status;

        $order->update([
            'courier_status' => $nextKey,
            'status' => $orderStatus,
        ]);

        if ($previousOrderStatus !== $orderStatus) {
            try {
                $order->refresh()->load('user');
                if ($order->user?->email) {
                    Mail::to($order->user->email)->send(new OrderStatusMail($order));
                }
            } catch (\Throwable $e) {
                Log::warning("Delivery status email failed for {$order->order_number}: " . $e->getMessage());
            }
        }

        $stageLabel = self::STAGES[$nextKey]['label'];

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'courier_status' => $nextKey,
                'step' => self::STAGES[$nextKey]['step'],
                'label' => $stageLabel,
                'description' => self::STAGES[$nextKey]['description'],
                'order_status' => $orderStatus,
            ]);
        }

        return back()
            ->with('active_tab', 'orders')
            ->with('active_order_mode', 'delivery')
            ->with('success', "Delivery {$order->order_number} updated to: {$stageLabel}.");
    }
}