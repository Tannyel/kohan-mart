@extends('layouts.app')

@section('content')
@php
    $counts = [
        'all' => $orders->count(),
        'pending' => $orders->where('status', 'pending')->count(),
        'confirmed' => $orders->where('status', 'confirmed')->count(),
        'ready_for_pickup' => $orders->where('status', 'ready_for_pickup')->count(),
        'out_for_delivery' => $orders->where('status', 'out_for_delivery')->count(),
        'completed' => $orders->where('status', 'completed')->count(),
        'cancelled' => $orders->where('status', 'cancelled')->count(),
    ];

    $tabs = [
        'all' => 'All Orders',
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'ready_for_pickup' => 'Ready For Pickup',
        'out_for_delivery' => 'Out for Delivery',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-[#2E2A25]">My Orders & Deliveries</h1>
        <p class="text-sm text-[#6B5D50] mt-1">Track your store pickup reservations and Lalamove Express deliveries from Project 8, Quezon City.</p>
    </div>

    <!-- Customer Order Status Filter Bar -->
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($tabs as $key => $label)
            <button type="button" data-filter="{{ $key }}"
                    class="cust-order-tab px-4 py-2 rounded-xl text-xs font-semibold border transition flex items-center gap-2 {{ $key === 'all' ? 'bg-[#3F5A45] text-[#FAF7F2] border-[#3F5A45] shadow-sm' : 'bg-[#FAF7F2] text-[#5C4E42] border-[#D4C8B4] hover:border-[#3F5A45]' }}">
                <span>{{ $label }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $key === 'all' ? 'bg-white/20 text-white' : 'bg-[#EFE9DC] text-[#5C4E42]' }}">
                    {{ $counts[$key] }}
                </span>
            </button>
        @endforeach
    </div>

    <div class="space-y-5" id="customerOrdersList">
        @forelse($orders as $order)
            @php
                $isPointsRedemption = ($order->payment_method === 'points_redemption' || $order->payment_method === 'points' || $order->total_minor === 0);
                $badgeStyles = match($order->status) {
                    'pending' => 'bg-[#F5E9D6] text-[#8A5A2B] border border-[#E3CCA8]',
                    'confirmed' => 'bg-[#3F5A45]/10 text-[#3F5A45] border border-[#3F5A45]/20',
                    'ready_for_pickup', 'out_for_delivery' => 'bg-[#E6EFE8] text-[#2B4230] border border-[#B8D1BE]',
                    'completed' => 'bg-[#EFE9DC] text-[#4A3B31] border border-[#D4C8B4]',
                    'cancelled' => 'bg-[#F7E8E6] text-[#9E3B33] border border-[#DFB8B3]',
                    default => 'bg-[#3F5A45]/10 text-[#3F5A45]',
                };
            @endphp
            <div class="cust-order-card bg-white rounded-2xl border border-[#E2D8C3] p-6 shadow-sm" data-status="{{ $order->status }}">
                <!-- Top Row: Order Number, Fulfillment Pill & Status Badges -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-[#F0EAE0] mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xs uppercase tracking-wider text-[#8C7A6B]">Order</span>
                        <span class="font-mono font-bold text-base text-[#2E2A25]">{{ $order->order_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $order->isDelivery() ? 'bg-[#FDF4E7] text-[#7A4E1D] border-[#D9B382]' : 'bg-[#F3EDE0] text-[#4A3B31] border-[#D4C8B4]' }}">
                            {{ $order->isDelivery() ? '🛵 Lalamove Delivery' : '🏪 Store Pickup' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($order->status === 'cancelled')
                            @if($order->cancelled_by === 'customer')
                                <span class="px-3.5 py-1 rounded-full text-xs font-semibold uppercase bg-[#F7E8E6] text-[#9E3B33] border border-[#DFB8B3]">
                                    Cancelled by Customer
                                </span>
                            @elseif($order->cancelled_by === 'admin')
                                <span class="px-3.5 py-1 rounded-full text-xs font-semibold uppercase bg-[#EFE9DC] text-[#6B4E3D] border border-[#D4C8B4]">
                                    Cancelled by Store Admin
                                </span>
                            @else
                                <span class="px-3.5 py-1 rounded-full text-xs font-semibold uppercase {{ $badgeStyles }}">
                                    Cancelled
                                </span>
                            @endif
                        @else
                            <span class="px-3.5 py-1 rounded-full text-xs font-semibold uppercase {{ $badgeStyles }}">
                                {{ $order->status === 'pending' ? 'Pending Confirmation' : str_replace('_', ' ', $order->status) }}
                            </span>
                        @endif

                        @if($isPointsRedemption)
                            <span class="px-3.5 py-1 rounded-full text-xs font-bold bg-[#E6EFE8] text-[#2B4230] border border-[#B8D1BE]">
                                ⭐ Points Payment
                            </span>
                        @elseif($order->is_paid)
                            <span class="px-3.5 py-1 rounded-full text-xs font-semibold bg-[#E6EFE8] text-[#2B4230] border border-[#B8D1BE]">
                                ✓ Paid
                            </span>
                        @else
                            <span class="px-3.5 py-1 rounded-full text-xs font-semibold bg-[#F5E9D6] text-[#8A5A2B] border border-[#E3CCA8]">
                                Pay at Store
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Fulfillment Callout: Delivery Address OR Scheduled Pickup -->
                @if($order->isDelivery())
                    <div class="mb-4 p-3.5 rounded-xl bg-[#FDF4E7]/70 border border-[#E3CCA8] flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-start gap-2.5">
                            <span class="text-base mt-0.5">📍</span>
                            <div>
                                <span class="text-[11px] uppercase tracking-wider font-semibold text-[#7A4E1D] block">
                                    Lalamove Drop-Off Address {{ $order->delivery_distance_km ? '(' . number_format((float)$order->delivery_distance_km, 2) . ' km)' : '' }}
                                </span>
                                <span class="text-sm font-bold text-[#2E2A25] block">
                                    {{ $order->delivery_address }}
                                </span>
                                @if($order->courier_driver_name)
                                    <span class="text-xs text-[#5C4E42] block mt-0.5">
                                        Rider: <strong>{{ $order->courier_driver_name }}</strong> ({{ $order->courier_driver_plate }})
                                        @if($order->courier_driver_phone)
                                            • <a href="tel:{{ preg_replace('/\s+/', '', $order->courier_driver_phone) }}" class="text-[#3F5A45] font-semibold hover:underline">{{ $order->courier_driver_phone }}</a>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('delivery.track', $order) }}"
                           class="px-3.5 py-1.5 rounded-lg bg-[#3F5A45] text-[#FAF7F2] hover:bg-[#324837] text-xs font-semibold transition shadow-xs">
                            Track Live Delivery →
                        </a>
                    </div>
                @else
                    <div class="mb-4 p-3.5 rounded-xl bg-[#F3EDE0] border border-[#DCD0BA] flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">📅</span>
                            <div>
                                <span class="text-[11px] uppercase tracking-wider font-semibold text-[#6B4E3D] block">Scheduled Store Pickup</span>
                                <span class="text-sm font-bold text-[#2E2A25]">
                                    {{ $order->pickup_scheduled_at?->format('l, F d, Y \a\t h:i A') }}
                                </span>
                            </div>
                        </div>
                        <span class="text-xs text-[#5C4E42] font-medium">Project 8, Quezon City</span>
                    </div>
                @endif

                <!-- Cancelled Refund & Forfeiture Notice -->
                @if($order->status === 'cancelled' && $order->isDelivery())
                    <div class="mb-4 p-3 rounded-xl bg-[#F7E8E6] border border-[#DFB8B3] text-xs text-[#7A2720]">
                        @if($order->delivery_fee_forfeited)
                            <strong>Cancellation Refund Notice:</strong> Because a Lalamove rider had already been dispatched, the delivery fee ({{ $formatter->format($order->delivery_fee_minor) }}) was non-refundable. Item balance refunded: <strong>{{ $formatter->format($order->refunded_minor) }}</strong>.
                        @else
                            <strong>Cancellation Refund Notice:</strong> Cancelled prior to rider dispatch. Full refund processed: <strong>{{ $formatter->format($order->refunded_minor) }}</strong>.
                        @endif
                    </div>
                @endif

                <!-- Ordered Items -->
                <ul class="text-sm text-[#5C4E42] space-y-2 mb-4">
                    @foreach($order->items as $item)
                        <li class="flex justify-between">
                            <span>{{ $item->product->name ?? 'Catalog Item' }} <strong class="text-[#2E2A25]">× {{ $item->quantity }}</strong></span>
                            <span class="font-medium">{{ $formatter->format($item->unit_price_minor * $item->quantity) }}</span>
                        </li>
                    @endforeach
                    @if($order->isDelivery() && $order->delivery_fee_minor > 0)
                        <li class="flex justify-between text-xs text-[#7A4E1D] pt-1 border-t border-dashed border-[#E2D8C3]">
                            <span>Lalamove Express Delivery Fee</span>
                            <span class="font-semibold">{{ $formatter->format($order->delivery_fee_minor) }}</span>
                        </li>
                    @endif
                </ul>

                <!-- Loyalty Points Breakdown -->
                @if($order->points_used > 0 || $order->points_earned > 0)
                    <div class="mb-4 p-3 rounded-xl bg-[#FAF7F2] border border-[#E2D8C3] flex flex-wrap items-center justify-between gap-2 text-xs">
                        @if($order->points_used > 0)
                            <span class="text-[#3F5A45] font-semibold">
                                • Points Redeemed: -{{ number_format($order->points_used, 2) }} pts (-{{ $formatter->format($order->points_discount_minor) }})
                            </span>
                        @endif
                        @if($order->points_earned > 0)
                            <span class="text-[#6B4E3D] font-semibold">
                                ✨ Points Earned: +{{ number_format($order->points_earned, 2) }} pts {{ $order->points_awarded ? '(Credited)' : '(Upon payment)' }}
                            </span>
                        @endif
                    </div>
                @endif

                <!-- Bottom Row: Total, Track/Receipt & Cancel Action -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-[#F0EAE0]">
                    <span class="font-bold text-base text-[#6B4E3D]">Total: {{ $formatter->format($order->total_minor) }}</span>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($order->isDelivery())
                            <a href="{{ route('delivery.track', $order) }}"
                               class="px-3.5 py-1.5 rounded-lg bg-[#3F5A45] text-[#FAF7F2] hover:bg-[#324837] text-xs font-semibold transition shadow-sm">
                                Track Delivery
                            </a>
                        @endif

                        <a href="{{ route('orders.receipt', $order) }}"
                           class="px-3.5 py-1.5 rounded-lg bg-[#6B4E3D] text-[#FAF7F2] hover:bg-[#573E30] text-xs font-semibold transition shadow-sm">
                            View Official Receipt
                        </a>

                        @if($order->canCustomerCancel())
                            <form action="{{ route('orders.cancel', $order) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to cancel order {{ $order->order_number }}?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#F7E8E6] text-[#9E3B33] hover:bg-[#DFB8B3] text-xs font-semibold transition">
                                    Cancel Order
                                </button>
                            </form>
                        @elseif($order->isDelivery() && in_array($order->status, ['confirmed', 'out_for_delivery']))
                            <a href="tel:+639170000000"
                               class="px-3 py-1.5 rounded-lg bg-[#FDF4E7] text-[#7A4E1D] border border-[#D9B382] text-xs font-semibold hover:bg-[#F7E6CE] transition">
                                Rider Assigned • Call Shop to Modify
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-[#F3EDE0] rounded-2xl border border-[#E2D8C3] p-10 text-center text-sm text-[#5C4E42]">
                You have no orders yet.
            </div>
        @endforelse

        <div id="emptyCustomerTabState" class="hidden bg-[#F3EDE0] rounded-2xl border border-[#E2D8C3] p-10 text-center text-sm text-[#5C4E42]">
            No orders found under this filter.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterBtns = document.querySelectorAll('.cust-order-tab');
    const orderCards = document.querySelectorAll('.cust-order-card');
    const emptyState = document.getElementById('emptyCustomerTabState');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            let visibleCount = 0;

            filterBtns.forEach(b => {
                const isSelected = b === btn;
                const badge = b.querySelector('span:last-child');

                b.className = `cust-order-tab px-4 py-2 rounded-xl text-xs font-semibold border transition flex items-center gap-2 ${
                    isSelected
                        ? 'bg-[#3F5A45] text-[#FAF7F2] border-[#3F5A45] shadow-sm'
                        : 'bg-[#FAF7F2] text-[#5C4E42] border-[#D4C8B4] hover:border-[#3F5A45]'
                }`;

                if (badge) {
                    badge.className = `px-1.5 py-0.5 rounded-full text-[10px] ${
                        isSelected ? 'bg-white/20 text-white' : 'bg-[#EFE9DC] text-[#5C4E42]'
                    }`;
                }
            });

            orderCards.forEach(card => {
                const matches = filter === 'all' || card.dataset.status === filter;
                card.classList.toggle('hidden', !matches);
                if (matches) visibleCount++;
            });

            if (emptyState && orderCards.length > 0) {
                emptyState.classList.toggle('hidden', visibleCount > 0);
            }
        });
    });
});
</script>
@endsection