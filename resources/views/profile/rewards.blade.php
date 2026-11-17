@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('profile.index') }}" class="text-xs font-semibold text-[#6B4E3D] hover:underline">
                ← Back to My Profile & Wallet
            </a>
            <h1 class="text-3xl font-bold text-[#2E2A25] mt-1">Loyalty Points Rewards Catalog</h1>
            <p class="text-xs text-[#6B5D50] mt-0.5">Redeem your earned Kohan Mart points for completely free items (1 pt = ₱1.00 value).</p>
        </div>
        <div class="bg-[#3F5A45] text-[#FAF7F2] px-5 py-3 rounded-2xl shadow-sm text-right">
            <span class="text-[10px] uppercase tracking-wider block opacity-80">Your Available Balance</span>
            <span class="text-xl font-bold">{{ number_format($user->points_balance, 2) }} pts</span>
        </div>
    </div>

    <!-- Rewards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($products as $product)
            @php
                $costPts = $product->price_minor / 100; // 1 pt = ₱1.00
                $canAfford = $user->points_balance >= $costPts;
            @endphp
            <div class="bg-white rounded-3xl border border-[#E2D8C3] p-5 shadow-sm flex flex-col justify-between transition hover:shadow-md">
                <div>
                    <div class="relative mb-4">
                        <img src="{{ $product->image_url ?: 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=300&q=80' }}"
                             alt="{{ $product->name }}"
                             class="w-full h-40 rounded-2xl object-cover bg-[#EFE9DC]">
                        <span class="absolute top-2.5 left-2.5 px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-xs text-[10px] uppercase tracking-wider font-bold text-white">
                            {{ $product->category }}
                        </span>
                        <span class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded-lg bg-[#3F5A45] text-white text-[10px] font-bold">
                            {{ $product->stock_quantity }} left
                        </span>
                    </div>

                    <span class="text-[10px] uppercase tracking-wider text-[#7A6B5D] font-semibold">SKU: {{ $product->sku }}</span>
                    <h3 class="font-bold text-sm text-[#2E2A25] mt-0.5 line-clamp-1">{{ $product->name }}</h3>
                    <p class="text-xs text-[#6B5D50] mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
                </div>

                <div class="mt-5 pt-4 border-t border-[#F0EAE0] space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[#7A6B5D] font-semibold">Reward Cost:</span>
                        <span class="font-bold text-[#6B4E3D] text-sm">{{ number_format($costPts, 0) }} pts</span>
                    </div>

                    @if($canAfford && $product->stock_quantity > 0)
                        <button type="button"
                                onclick="openRedeemModal('{{ $product->id }}', '{{ addslashes($product->name) }}', {{ $costPts }}, '{{ route('profile.rewards.redeem', $product) }}')"
                                class="w-full py-2.5 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition shadow-2xs">
                            Redeem Free Item ({{ number_format($costPts, 0) }} pts)
                        </button>
                    @elseif($product->stock_quantity <= 0)
                        <button type="button" disabled class="w-full py-2.5 rounded-xl bg-[#EFE9DC] text-[#7A6B5D] text-xs font-semibold cursor-not-allowed">
                            Out of Stock
                        </button>
                    @else
                        <button type="button" disabled class="w-full py-2.5 rounded-xl bg-[#F7E8E6] text-[#9E3B33] text-xs font-semibold cursor-not-allowed">
                            Need {{ number_format($costPts - $user->points_balance, 0) }} more pts
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Aesthetic Wabi-Sabi Redemption Modal with Fulfillment Options & Live Quote Pins -->
<div id="redeemModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div onclick="closeRedeemModal()" class="fixed inset-0 bg-[#2E2A25]/60 backdrop-blur-xs transition-opacity"></div>

    <div class="relative z-10 w-full max-w-lg bg-[#FAF7F2] rounded-3xl border border-[#E2D8C3] shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="bg-[#3F5A45] px-6 py-4 flex items-center justify-between text-[#FAF7F2] shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-full bg-white/15 flex items-center justify-center text-sm font-bold">⭐</span>
                <h3 class="font-bold text-base tracking-wide">Redeem Item with Loyalty Points</h3>
            </div>
            <button type="button" onclick="closeRedeemModal()" class="text-[#FAF7F2]/80 hover:text-white text-lg leading-none">
                ×
            </button>
        </div>

        <form id="redeemForm" method="POST" class="p-6 space-y-4 text-xs overflow-y-auto flex-1">
            @csrf
            <input type="hidden" id="modalProductId" name="product_id" value="">
            <input type="hidden" id="modalFulfillmentType" name="fulfillment_type" value="pickup">

            <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                <p class="text-[10px] uppercase tracking-wider text-[#7A6B5D] font-semibold">Selected Reward</p>
                <p class="font-bold text-sm text-[#2E2A25]" id="modalProductName">—</p>
                <p class="text-xs text-[#3F5A45] font-semibold">Cost: <span id="modalProductCost">0</span> Points (Free Item)</p>
            </div>

            <!-- Fulfillment Mode Selector -->
            <div class="space-y-2">
                <label class="block font-bold text-[#2E2A25]">How would you like to receive your free item?</label>
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button"
                            id="modalBtnPickup"
                            onclick="setModalFulfillment('pickup')"
                            class="p-3 rounded-xl border-2 text-left transition border-[#3F5A45] bg-[#E6EFE8]/60">
                        <div class="font-bold text-[#2E2A25]">🏪 Store Pickup</div>
                        <div class="text-[10px] text-[#5C4E42] mt-0.5">Free • Project 8, QC</div>
                    </button>

                    <button type="button"
                            id="modalBtnDelivery"
                            onclick="setModalFulfillment('delivery')"
                            class="p-3 rounded-xl border-2 text-left transition border-[#E2D8C3] bg-white hover:border-[#D9B382]">
                        <div class="font-bold text-[#2E2A25]">🛵 Lalamove Delivery</div>
                        <div class="text-[10px] text-[#7A4E1D] mt-0.5">Same-day • Delivery fee applies</div>
                    </button>
                </div>
            </div>

            <!-- Pickup Schedule Field -->
            <div id="modalPickupFields" class="bg-[#F3EDE0] rounded-xl p-3.5 space-y-1.5">
                <label class="block font-bold text-[#6B4E3D]">Scheduled Pickup Date & Time *</label>
                <input type="datetime-local"
                       name="pickup_scheduled_at"
                       id="modalPickupTime"
                       value="{{ now()->addMinutes(15)->format('Y-m-d\TH:i') }}"
                       min="{{ now()->addMinutes(15)->format('Y-m-d\TH:i') }}"
                       class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white font-semibold text-[#2E2A25]">
            </div>

            <!-- Lalamove Delivery Fields & Live Quote Summary Pin -->
            <div id="modalDeliveryFields" class="hidden bg-[#FDF4E7] rounded-xl border border-[#D9B382] p-3.5 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-[#7A4E1D]">🛵 Lalamove Drop-Off Details</span>
                    <span class="px-2 py-0.5 rounded bg-[#6B4E3D] text-white text-[10px]">Express Moto</span>
                </div>
                <div>
                    <label class="block font-semibold text-[#5C4E42] mb-1">Complete Delivery Address *</label>
                    <textarea name="delivery_address"
                              id="modalDeliveryAddress"
                              rows="2"
                              oninput="fetchDeliveryQuote()"
                              placeholder="Unit 12B, Tower 1, QC"
                              class="w-full px-3 py-2 rounded-lg border border-[#C69C6D] bg-white"></textarea>
                </div>
                <div>
                    <label class="block font-semibold text-[#5C4E42] mb-1">Recipient Mobile Number *</label>
                    <input type="text"
                           name="delivery_recipient_phone"
                           id="modalDeliveryPhone"
                           placeholder="0917 123 4567"
                           class="w-full px-3 py-2 rounded-lg border border-[#C69C6D] bg-white font-semibold">
                </div>

                <!-- Live Quote & Pricing Pin Box -->
                <div id="deliveryQuoteBox" class="hidden bg-white rounded-xl border border-[#D9B382] p-3 space-y-1.5 text-[11px]">
                    <div class="flex justify-between font-semibold text-[#2E2A25]">
                        <span>Estimated Distance:</span>
                        <span id="quoteDistance">— km from Project 8</span>
                    </div>
                    <div class="flex justify-between font-bold text-[#7A4E1D]">
                        <span>Calculated Courier Fee:</span>
                        <span id="quoteFee">₱0.00</span>
                    </div>
                </div>

                <p class="text-[11px] text-[#7A4E1D] italic">Note: The item is 100% free with points, but standard Lalamove courier fee will be billed.</p>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5 shrink-0">
                <button type="button"
                        onclick="closeRedeemModal()"
                        class="px-4 py-2 rounded-xl border border-[#D4C8B4] bg-white font-semibold text-[#5C4E42] hover:bg-[#EFE9DC] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-[#3F5A45] text-[#FAF7F2] font-semibold hover:bg-[#324837] transition shadow-sm">
                    Confirm Redemption & Generate Receipt →
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const redeemModal = document.getElementById('redeemModal');
const redeemForm = document.getElementById('redeemForm');
const modalProductId = document.getElementById('modalProductId');
const modalProductName = document.getElementById('modalProductName');
const modalProductCost = document.getElementById('modalProductCost');
const modalFulfillmentType = document.getElementById('modalFulfillmentType');
const modalBtnPickup = document.getElementById('modalBtnPickup');
const modalBtnDelivery = document.getElementById('modalBtnDelivery');
const modalPickupFields = document.getElementById('modalPickupFields');
const modalDeliveryFields = document.getElementById('modalDeliveryFields');
const modalPickupTime = document.getElementById('modalPickupTime');
const modalDeliveryAddress = document.getElementById('modalDeliveryAddress');
const modalDeliveryPhone = document.getElementById('modalDeliveryPhone');
const deliveryQuoteBox = document.getElementById('deliveryQuoteBox');

function openRedeemModal(productId, productName, costPts, redeemUrl) {
    redeemForm.action = redeemUrl;
    modalProductId.value = productId;
    modalProductName.textContent = productName;
    modalProductCost.textContent = costPts;
    setModalFulfillment('pickup');
    redeemModal.classList.remove('hidden');
    redeemModal.classList.add('flex');
}

function closeRedeemModal() {
    redeemModal.classList.add('hidden');
    redeemModal.classList.remove('flex');
}

function setModalFulfillment(mode) {
    modalFulfillmentType.value = mode;
    if (mode === 'delivery') {
        modalBtnDelivery.className = 'p-3 rounded-xl border-2 text-left transition border-[#6B4E3D] bg-[#FDF4E7]';
        modalBtnPickup.className = 'p-3 rounded-xl border-2 text-left transition border-[#E2D8C3] bg-white hover:border-[#B8D1BE]';
        modalPickupFields.classList.add('hidden');
        modalDeliveryFields.classList.remove('hidden');
        modalPickupTime.required = false;
        modalDeliveryAddress.required = true;
        modalDeliveryPhone.required = true;
    } else {
        modalBtnPickup.className = 'p-3 rounded-xl border-2 text-left transition border-[#3F5A45] bg-[#E6EFE8]/60';
        modalBtnDelivery.className = 'p-3 rounded-xl border-2 text-left transition border-[#E2D8C3] bg-white hover:border-[#D9B382]';
        modalDeliveryFields.classList.add('hidden');
        modalPickupFields.classList.remove('hidden');
        modalPickupTime.required = true;
        modalDeliveryAddress.required = false;
        modalDeliveryPhone.required = false;
        deliveryQuoteBox.classList.add('hidden');
    }
}

// Live distance & fee quote generator as user types address
let quoteTimeout = null;
function fetchDeliveryQuote() {
    const address = modalDeliveryAddress.value.trim();
    if (address.length < 5) {
        deliveryQuoteBox.classList.add('hidden');
        return;
    }

    clearTimeout(quoteTimeout);
    quoteTimeout = setTimeout(() => {
        const simulatedKm = (Math.abs(address.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % 10) + 2.0).toFixed(2);
        const simulatedFeeMinor = Math.round((70 + (simulatedKm * 22)) * 100);

        document.getElementById('quoteDistance').textContent = `${simulatedKm} km from Project 8`;
        document.getElementById('quoteFee').textContent = `₱${(simulatedFeeMinor / 100).toFixed(2)}`;
        deliveryQuoteBox.classList.remove('hidden');
    }, 350);
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeRedeemModal();
});
</script>
@endsection