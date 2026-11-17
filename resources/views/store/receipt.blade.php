@extends('layouts.app')

@section('content')

@php
    if (!isset($formatter)) {
        $formatter = new class {
            public function format($minorAmount) {
                return '₱' . number_format(($minorAmount ?? 0) / 100, 2);
            }
        };
    }
    $isPointsRedemption = ($order->payment_method === 'points_redemption' || $order->payment_method === 'points' || $order->total_minor === 0);
@endphp

<div class="max-w-2xl mx-auto px-4 py-10">
    <!-- Top Action Buttons (Hidden when printing) -->
    <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-6">
        <a href="{{ route('store.orders') }}" class="text-xs font-semibold text-[#6B4E3D] hover:underline">
            ← Back to My Orders
        </a>
        <div class="flex items-center gap-2.5">
            @if($order->isDelivery())
                <a href="{{ route('delivery.track', $order) }}"
                   class="px-4 py-2.5 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition shadow-sm">
                    🛵 Track Live Delivery
                </a>
            @endif
            <button type="button" onclick="window.print()"
                    class="px-5 py-2.5 rounded-xl bg-[#6B4E3D] text-[#FAF7F2] text-xs font-semibold hover:bg-[#573E30] transition shadow-sm">
                🖨 Print / Save Official Receipt
            </button>
        </div>
    </div>

    <!-- Official Receipt Card -->
    <div class="bg-white rounded-3xl border border-[#E2D8C3] p-8 sm:p-10 shadow-sm space-y-6">
        <!-- Store Header -->
        <div class="text-center border-b border-dashed border-[#D4C8B4] pb-6">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-[#3F5A45] text-[#FAF7F2] flex items-center justify-center font-serif-jp font-bold text-xl mb-2">
                湖畔
            </div>
            <h1 class="font-serif-jp text-2xl font-bold text-[#2E2A25]">Kohan Mart (湖畔マート)</h1>
            <p class="text-xs text-[#6B5D50] mt-1">41 Insurance, Project 8, Quezon City, Metro Manila</p>
            <p class="text-[11px] text-[#7A6B5D]">
                +63 (02) 8555-0194 • OFFICIAL {{ $order->isDelivery() ? 'ORDER & DELIVERY' : 'RESERVATION & PAYMENT' }} RECEIPT
            </p>
        </div>

        <!-- Order & Payment Metadata -->
        <div class="grid grid-cols-2 gap-4 text-xs bg-[#FAF7F2] p-4 rounded-2xl border border-[#E2D8C3]">
            <div>
                <span class="text-[10px] uppercase tracking-wider text-[#7A6B5D] block">Receipt / Order No.</span>
                <strong class="text-[#2E2A25] font-mono text-sm">{{ $order->order_number }}</strong>
                <span class="block text-[11px] font-semibold text-[#6B4E3D] mt-0.5">
                    {{ $order->isDelivery() ? '🛵 Lalamove Express' : '🏪 Store Pickup' }}
                </span>
            </div>
            <div class="text-right">
                <span class="text-[10px] uppercase tracking-wider text-[#7A6B5D] block">Payment Status</span>
                @if($isPointsRedemption)
                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#E6EFE8] text-[#2B4230] font-bold">
                        ⭐ Points Payment
                    </span>
                @elseif($order->is_paid)
                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#E6EFE8] text-[#2B4230] font-bold">
                        ✓ Paid
                    </span>
                @else
                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#FDF4E7] text-[#7A4E1D] font-bold">
                        Unpaid • Pay at Store
                    </span>
                @endif
            </div>

            <div>
                <span class="text-[10px] uppercase tracking-wider text-[#7A6B5D] block">Customer Name</span>
                <strong class="text-[#2E2A25]">{{ $order->user->name }}</strong>
                <span class="block text-[11px] text-[#6B5D50]">{{ $order->user->email }}</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] uppercase tracking-wider text-[#7A6B5D] block">Date Ordered</span>
                <strong class="text-[#2E2A25]">{{ $order->created_at->format('M d, Y • h:i A') }}</strong>
                @if($order->paid_at)
                    <span class="block text-[11px] text-[#3F5A45] font-semibold mt-0.5">
                        Date Paid: {{ $order->paid_at->format('M d, Y • h:i A') }}
                    </span>
                @endif
            </div>

            @if($order->isDelivery())
                <div class="col-span-2 pt-2.5 border-t border-[#E2D8C3] space-y-1.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-[11px] font-semibold text-[#6B4E3D]">Lalamove Express Drop-Off:</span>
                        @if($order->courier_order_id)
                            <span class="font-mono text-[11px] font-bold text-[#7A4E1D]">
                                Waybill #{{ $order->courier_order_id }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs font-bold text-[#2E2A25]">{{ $order->delivery_address }}</p>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#5C4E42]">
                        <span>
                            @if($order->delivery_recipient_phone)
                                Recipient Phone: <strong>{{ $order->delivery_recipient_phone }}</strong>
                            @endif
                            @if($order->delivery_distance_km)
                                • Distance: <strong>{{ number_format((float) $order->delivery_distance_km, 2) }} km</strong>
                            @endif
                        </span>
                        @if($order->courier_driver_name)
                            <span>
                                Rider: <strong>{{ $order->courier_driver_name }}</strong> ({{ $order->courier_driver_plate }})
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <div class="col-span-2 pt-2 border-t border-[#E2D8C3] flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-[#6B4E3D]">Scheduled Store Pickup:</span>
                    <strong class="text-xs text-[#2E2A25]">
                        {{ $order->pickup_scheduled_at?->format('l, M d, Y — h:i A') ?? 'Standard Store Hours' }}
                    </strong>
                </div>
            @endif
        </div>

        <!-- Itemized Breakdown -->
        <div>
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#E2D8C3] text-[10px] uppercase tracking-wider text-[#7A6B5D]">
                        <th class="py-2">Item</th>
                        <th class="py-2 text-center">Qty</th>
                        <th class="py-2 text-right">Unit Price</th>
                        <th class="py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F0EAE0]">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-3 font-medium text-[#2E2A25]">
                                {{ $item->product->name ?? 'Catalog Item' }}
                            </td>
                            <td class="py-3 text-center text-[#5C4E42]">{{ $item->quantity }}</td>
                            <td class="py-3 text-right text-[#5C4E42]">
                                {{ $formatter->format($item->unit_price_minor) }}
                            </td>
                            <td class="py-3 text-right font-semibold text-[#2E2A25]">
                                {{ $formatter->format($item->unit_price_minor * $item->quantity) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Points Summary -->
        <div class="border-t border-dashed border-[#D4C8B4] pt-4 space-y-2 text-xs">
            @php
                $subtotal = $order->subtotal_minor > 0 ? $order->subtotal_minor : $order->total_minor;
            @endphp
            <div class="flex justify-between text-[#5C4E42]">
                <span>Subtotal</span>
                <span>{{ $formatter->format($subtotal) }}</span>
            </div>

            @if($order->isDelivery())
                <div class="flex justify-between text-[#7A4E1D] font-semibold">
                    <span>Lalamove Express Delivery Fee</span>
                    <span>{{ $formatter->format($order->delivery_fee_minor) }}</span>
                </div>
            @endif

            @if($order->points_used > 0)
                <div class="flex justify-between text-[#3F5A45] font-semibold">
                    <span>Loyalty Points Redeemed ({{ number_format($order->points_used, 2) }} pts)</span>
                    <span>-{{ $formatter->format($order->points_discount_minor) }}</span>
                </div>
            @endif

            <div class="flex justify-between text-base font-bold text-[#2E2A25] pt-2 border-t border-[#E2D8C3]">
                <span>Total {{ $order->is_paid ? 'Paid' : 'Payable at Pickup' }}</span>
                <span class="text-[#6B4E3D]">{{ $formatter->format($order->total_minor) }}</span>
            </div>

            @if($order->points_earned > 0)
                <div class="mt-3 p-3 rounded-xl bg-[#E6EFE8] text-[#2B4230] text-[11px] flex items-center justify-between">
                    <span>Loyalty Points Earned on this Order:</span>
                    <strong>+{{ number_format($order->points_earned, 2) }} pts {{ $order->points_awarded ? '(Credited)' : '(Credited upon payment)' }}</strong>
                </div>
            @endif
        </div>

        <!-- Receipt Footer -->
        <div class="text-center text-[11px] text-[#7A6B5D] pt-4 border-t border-[#F0EAE0]">
            @if($order->isDelivery())
                Arigatou gozaimasu! Track your Lalamove rider live anytime using reference <strong>{{ $order->order_number }}</strong>.
            @else
                Arigatou gozaimasu! Present this receipt number (<strong>{{ $order->order_number }}</strong>) at the counter upon pickup.
            @endif
        </div>
    </div>
</div>
@endsection