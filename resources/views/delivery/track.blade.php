@extends('layouts.app')

@section('content')
@php
    $isCancelled = $order->status === 'cancelled';
    $currentStep = $currentStage['step'] ?? 1;
@endphp

<div class="max-w-4xl mx-auto px-4 py-10 space-y-6">
    <!-- Top Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-xs uppercase tracking-widest text-[#6B4E3D] font-semibold">
                Lalamove Express ShareLink • 湖畔マート
            </span>
            <h1 class="text-3xl font-bold text-[#2E2A25] mt-1">
                Live Courier Delivery Tracking
            </h1>
        </div>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('store.orders') }}" class="text-xs font-semibold text-[#6B4E3D] hover:underline">
                    ← My Orders
                </a>
                @if(auth()->id() === $order->user_id || auth()->user()->is_staff ?? false)
                    <a href="{{ route('orders.receipt', $order) }}"
                       class="px-4 py-2 rounded-xl bg-[#6B4E3D] text-[#FAF7F2] text-xs font-semibold hover:bg-[#573E30] transition shadow-sm">
                        Official Receipt
                    </a>
                @endif
            @endauth
        </div>
    </div>

    <!-- Main Tracking Card -->
    <div class="bg-white rounded-3xl border border-[#E2D8C3] p-6 sm:p-8 shadow-sm space-y-6">
        <!-- Order & Courier Reference Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 border-b border-[#F0EAE0]">
            <div>
                <span class="text-[11px] uppercase tracking-wider text-[#7A6B5D] block">Order & Waybill Reference</span>
                <div class="flex flex-wrap items-center gap-2.5 mt-0.5">
                    <span class="font-mono font-bold text-xl text-[#2E2A25]">{{ $order->order_number }}</span>
                    @if($order->courier_order_id)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-[#FDF4E7] text-[#7A4E1D] border border-[#D9B382]">
                            Lalamove #{{ $order->courier_order_id }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span id="liveStageBadge"
                      class="px-4 py-1.5 rounded-full text-xs font-bold uppercase border {{ $isCancelled ? 'bg-[#F7E8E6] text-[#9E3B33] border-[#DFB8B3]' : 'bg-[#E6EFE8] text-[#2B4230] border-[#B8D1BE]' }}">
                    {{ $isCancelled ? 'Cancelled' : $currentStage['label'] }}
                </span>
            </div>
        </div>

        <!-- Cancelled Alert OR 5-Stage Courier Progress Stepper -->
        @if($isCancelled)
            <div class="rounded-2xl bg-[#F7E8E6] border-2 border-[#DFB8B3] p-5 flex items-start gap-3.5">
                <span class="w-8 h-8 rounded-full bg-[#9E3B33] text-white flex items-center justify-center text-sm font-bold shrink-0">!</span>
                <div class="space-y-1">
                    <h2 class="font-bold text-sm text-[#7A2720]">Delivery Order Cancelled</h2>
                    <p class="text-xs text-[#7A2720]/90 leading-relaxed">
                        @if($order->delivery_fee_forfeited)
                            This delivery was cancelled after a Lalamove rider was dispatched. The delivery fee (₱{{ number_format($order->delivery_fee_minor / 100, 2) }}) was forfeited, and ₱{{ number_format($order->refunded_minor / 100, 2) }} has been refunded.
                        @else
                            This order was cancelled prior to rider dispatch. A full refund of ₱{{ number_format($order->refunded_minor / 100, 2) }} has been processed and stock returned to inventory.
                        @endif
                    </p>
                </div>
            </div>
        @else
            <!-- Active Stage Callout -->
            <div class="bg-[#FAF7F2] rounded-2xl border border-[#E2D8C3] p-5 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider font-bold text-[#3F5A45]">
                            Current Stage (<span id="liveStepNumber">{{ $currentStep }}</span> of {{ count($stages) }})
                        </span>
                        <h2 id="liveStageLabel" class="text-lg font-bold text-[#2E2A25] mt-0.5">
                            {{ $currentStage['label'] }}
                        </h2>
                        <p id="liveStageDescription" class="text-xs text-[#5C4E42] mt-1 leading-relaxed">
                            {{ $currentStage['description'] }}
                        </p>
                    </div>
                    <span class="text-[11px] text-[#7A6B5D]">
                        Last sync: <strong id="liveUpdatedAt">{{ $order->updated_at?->format('h:i:s A') }}</strong>
                    </span>
                </div>

                <!-- 5-Step Visual Timeline -->
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 pt-2 border-t border-[#E2D8C3]">
                    @foreach($stages as $stageKey => $stageMeta)
                        @php
                            $stepNum = $stageMeta['step'];
                            $isDone = $stepNum < $currentStep || ($currentKey === 'delivered' && $stepNum === $currentStep);
                            $isActive = $stepNum === $currentStep && $currentKey !== 'delivered';
                        @endphp
                        <div data-stage-card="{{ $stepNum }}" class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-2 p-2 rounded-xl transition">
                            <div data-stage-dot="{{ $stepNum }}"
                                 class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition
                                 {{ $isDone
                                     ? 'bg-[#3F5A45] text-[#FAF7F2]'
                                     : ($isActive
                                         ? 'bg-[#6B4E3D] text-[#FAF7F2] ring-4 ring-[#6B4E3D]/20 animate-pulse'
                                         : 'bg-white text-[#8C7A6B] border-2 border-[#D4C8B4]') }}">
                                {{ $isDone ? '✓' : $stepNum }}
                            </div>
                            <span data-stage-title="{{ $stepNum }}"
                                  class="text-[11px] font-bold leading-tight {{ ($isDone || $isActive) ? 'text-[#2E2A25]' : 'text-[#8C7A6B]' }}">
                                {{ $stageMeta['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Simulator Advance Button (for demo/testing when not yet delivered) -->
                @if($currentKey !== 'delivered')
                    <div class="pt-3 border-t border-[#E2D8C3] flex flex-wrap items-center justify-between gap-3">
                        <span class="text-[11px] text-[#6B5D50]">
                            ShareLink Simulator: Advance courier milestone to test live updates.
                        </span>
                        <form action="{{ route('delivery.advance', $order) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition shadow-xs">
                                Simulate Next Courier Step →
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- 🗺️ Mock Google Maps Live Telemetry Radar Component -->
    <x-mock-delivery-map :order="$order" />

    <!-- Assigned Rider & Drop-off Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Assigned Lalamove Rider Card -->
        <div class="bg-[#E6EFE8]/60 rounded-2xl border border-[#B8D1BE] p-4 space-y-2">
            <span class="text-[11px] uppercase tracking-wider font-bold text-[#2B4230] block">
                Assigned Courier & Rider
            </span>

            <div class="text-xs space-y-1.5 text-[#2E2A25]">
                <div class="flex justify-between">
                    <span class="text-[#5C4E42]">Service:</span>
                    <strong>Lalamove Express (Motorcycle)</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#5C4E42]">Rider Name:</span>
                    <strong>{{ $order->courier_driver_name ?? 'Assigning Rider...' }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#5C4E42]">Plate Number:</span>
                    <span class="font-mono font-bold bg-white px-2 py-0.5 rounded border border-[#B8D1BE]">
                        {{ $order->courier_driver_plate ?? '—' }}
                    </span>
                </div>
                @if($order->delivery_distance_km)
                    <div class="flex justify-between">
                        <span class="text-[#5C4E42]">Route Distance:</span>
                        <strong>{{ number_format((float) $order->delivery_distance_km, 2) }} km from Project 8</strong>
                    </div>
                @endif
            </div>

            @if(!$isCancelled)
                <div class="flex flex-wrap gap-2 pt-2 border-t border-[#B8D1BE]/60">
                    @if($order->courier_driver_phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $order->courier_driver_phone) }}"
                           class="px-3 py-1.5 rounded-lg bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition">
                            📞 Call Rider ({{ $order->courier_driver_phone }})
                        </a>
                        <a href="sms:{{ preg_replace('/\s+/', '', $order->courier_driver_phone) }}"
                           class="px-3 py-1.5 rounded-lg bg-white border border-[#D4C8B4] text-[#2E2A25] text-xs font-semibold hover:bg-[#EFE9DC] transition">
                            💬 SMS Rider
                        </a>
                    @endif
                    <a href="tel:+639170000000"
                       class="px-3 py-1.5 rounded-lg bg-[#FDF4E7] border border-[#D9B382] text-[#7A4E1D] text-xs font-semibold hover:bg-[#F7E6CE] transition">
                        🏪 Call Shop
                    </a>
                </div>
            @endif
        </div>

        <!-- Drop-off Destination Card -->
        <div class="bg-[#F3EDE0]/70 rounded-2xl border border-[#E2D8C3] p-4 space-y-2">
            <span class="text-[11px] uppercase tracking-wider font-bold text-[#6B4E3D] block">
                Drop-Off Destination
            </span>
            <p class="text-sm font-bold text-[#2E2A25]">
                {{ $order->user->name ?? 'Kohan Mart Customer' }}
            </p>
            <p class="text-xs text-[#4A3B31] leading-relaxed">
                {{ $order->delivery_address }}
            </p>
            @if($order->delivery_recipient_phone)
                <p class="text-xs text-[#5C4E42] font-medium">
                    📞 {{ $order->delivery_recipient_phone }}
                </p>
            @endif
            @if($order->delivery_lat && $order->delivery_lng)
                <p class="text-[10px] font-mono text-[#7A6B5D]">
                    GPS: {{ $order->delivery_lat }}, {{ $order->delivery_lng }}
                </p>
            @endif
        </div>
    </div>

    <!-- Order Items Summary -->
    <div class="bg-white rounded-3xl border border-[#E2D8C3] p-6 shadow-sm space-y-4">
        <h2 class="text-xs uppercase tracking-wider font-bold text-[#6B4E3D]">Package Contents</h2>
        <ul class="divide-y divide-[#F0EAE0] text-sm">
            @foreach($order->items as $item)
                <li class="py-2 flex items-center justify-between">
                    <span>
                        <strong class="text-[#2E2A25]">{{ $item->quantity }}×</strong>
                        {{ $item->product->name ?? 'Catalog Item' }}
                    </span>
                    <span class="font-medium text-[#5C4E42]">
                        ₱{{ number_format(($item->unit_price_minor * $item->quantity) / 100, 2) }}
                    </span>
                </li>
            @endforeach
        </ul>

        <div class="mt-3 pt-3 border-t border-dashed border-[#D4C8B4] space-y-1 text-xs">
            <div class="flex justify-between text-[#5C4E42]">
                <span>Items Subtotal</span>
                <span>₱{{ number_format($order->subtotal_minor / 100, 2) }}</span>
            </div>
            <div class="flex justify-between text-[#5C4E42]">
                <span>Lalamove Delivery Fee</span>
                <span>₱{{ number_format($order->delivery_fee_minor / 100, 2) }}</span>
            </div>
            @if($order->points_used > 0)
                <div class="flex justify-between text-[#3F5A45] font-semibold">
                    <span>Points Redeemed (-{{ number_format($order->points_used, 2) }} pts)</span>
                    <span>-₱{{ number_format($order->points_discount_minor / 100, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-base font-bold text-[#2E2A25] pt-2 border-t border-[#E2D8C3]">
                <span>Total Paid</span>
                <span class="text-[#6B4E3D]">₱{{ number_format($order->total_minor / 100, 2) }}</span>
            </div>
        </div>
    </div>
</div>

@if(!$isCancelled && $currentKey !== 'delivered')
<script id="deliveryTrackerScript" data-status-url="{{ route('api.delivery.status', $order) }}">
    const statusEndpoint = document.getElementById('deliveryTrackerScript').dataset.statusUrl;

    setInterval(async () => {
        if (document.hidden) return;
        try {
            const res = await fetch(statusEndpoint, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.ok) return;

            document.getElementById('liveStageBadge').textContent = data.label;
            document.getElementById('liveStepNumber').textContent = data.step;
            document.getElementById('liveStageLabel').textContent = data.label;
            document.getElementById('liveStageDescription').textContent = data.description;
            if (data.updated_at) {
                document.getElementById('liveUpdatedAt').textContent = data.updated_at;
            }

            if (data.courier_status === 'delivered') {
                window.location.reload();
            }
        } catch (e) {
            // Silent retry
        }
    }, 8000);
</script>
@endif
@endsection