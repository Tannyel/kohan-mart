@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <span class="text-xs uppercase tracking-widest text-[#6B4E3D] font-semibold">Your Basket • 湖畔マート</span>
            <h1 class="text-3xl font-bold text-[#2E2A25] mt-1">Review Items & Checkout</h1>
        </div>
        <div class="flex items-center gap-4">
            <span id="liveStockBadge"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#E6EFE8] border border-[#B8D1BE] text-[11px] font-semibold text-[#2B4230]">
                <span id="liveStockDot" class="w-2 h-2 rounded-full bg-[#3F5A45] animate-pulse"></span>
                <span id="liveStockText">Live Stock Verified</span>
            </span>
            <a href="{{ route('store.index') }}" class="text-xs font-semibold text-[#3F5A45] hover:underline">
                ← Continue Browsing Catalog
            </a>
        </div>
    </div>

    <!-- Server-rendered session/validation errors (auto-dismisses in 4s) -->
    @if(session('error') || $errors->any())
        <div id="serverErrorBanner" class="mb-6 rounded-2xl bg-[#F7E8E6] border-2 border-[#DFB8B3] p-4 sm:p-5 shadow-sm transition-opacity duration-300">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-full bg-[#9E3B33] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">!</span>
                    <div class="space-y-1">
                        <h3 class="font-bold text-sm text-[#7A2720]">Checkout Notice</h3>
                        <p class="text-xs text-[#7A2720]/90 leading-relaxed">
                            {{ session('error') ?: $errors->first() }}
                        </p>
                    </div>
                </div>
                <button type="button"
                        onclick="document.getElementById('serverErrorBanner')?.remove()"
                        aria-label="Close notice"
                        class="px-2 py-1 rounded-lg text-xs font-bold text-[#7A2720] hover:bg-[#DFB8B3]/50 transition shrink-0">
                    ✕
                </button>
            </div>
        </div>
    @endif

    <!-- Dynamic Checkout / Stock Notice Banner (Auto-dismisses after 4 seconds) -->
    <div id="stockConflictBanner" class="hidden mb-6 rounded-2xl bg-[#F7E8E6] border-2 border-[#DFB8B3] p-4 sm:p-5 shadow-sm transition-opacity duration-300">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full bg-[#9E3B33] text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">!</span>
                <div class="space-y-1">
                    <h3 class="font-bold text-sm text-[#7A2720]">Checkout Notice</h3>
                    <p id="stockConflictMessage" class="text-xs text-[#7A2720]/90 leading-relaxed whitespace-pre-line"></p>
                </div>
            </div>
            <button type="button"
                    id="dismissNoticeBtn"
                    onclick="dismissCheckoutNotice()"
                    aria-label="Close notice"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-[#7A2720] hover:bg-[#DFB8B3]/50 transition shrink-0 cursor-pointer">
                ✕
            </button>
        </div>
    </div>

    @if(empty($items))
        <div class="bg-white rounded-3xl border border-[#E2D8C3] p-12 text-center shadow-sm space-y-4">
            <p class="text-sm text-[#5C4E42]">Your basket is currently empty.</p>
            <a href="{{ route('store.index') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition">
                Explore Japanese Goods
            </a>
        </div>
    @else
        @php
            $user = auth()->user();
            $subtotalMinor = collect($items)->sum(function ($item) {
                return ($item['product']->price_minor ?? 0) * ($item['quantity'] ?? 1);
            });
            $subtotalPesos = round($subtotalMinor / 100, 2);
            $walletBalance = (float) $user->points_balance;
            $maxRedeemablePoints = min($walletBalance, $subtotalPesos);
            $earnRate = $user->isBirthdayMonth() ? 0.20 : 0.10;
            $initialFulfillment = old('fulfillment_type', 'pickup');

            $hasPerishables = collect($items)->contains(function ($item) {
                $cat = strtolower(trim((string) ($item['product']->category ?? '')));
                return in_array($cat, ['foods', 'drinks'], true);
            });
            $maxPickupDatetime = $hasPerishables
                ? now()->addDay()->format('Y-m-d\TH:i')
                : now()->addDays(7)->format('Y-m-d\TH:i');
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Basket Items -->
            <div id="basketItemsContainer" class="lg:col-span-7 space-y-4">
                @foreach($items as $item)
                    @php
                        $product = $item['product'];
                        $qty = (int) $item['quantity'];
                    @endphp
                    <div id="cart-item-card-{{ $product->id }}"
                         data-cart-item-card="1"
                         class="bg-white rounded-2xl border border-[#E2D8C3] p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition">
                        <div class="flex items-center gap-4">
                            <img src="{{ $product->image_url ?: 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=300&q=80' }}"
                                 alt="{{ $product->name }}"
                                 class="w-16 h-16 rounded-xl object-cover bg-[#EFE9DC] shrink-0">
                            <div>
                                <span class="text-[10px] uppercase tracking-wider text-[#7A6B5D] font-semibold">{{ $product->category }}</span>
                                <h3 class="font-bold text-sm text-[#2E2A25]">{{ $product->name }}</h3>
                                <p class="text-xs text-[#6B4E3D] font-semibold mt-0.5">
                                    {{ $formatter->format($product->price_minor) }} each
                                </p>
                                <p id="item-stock-status-{{ $product->id }}"
                                   class="text-[11px] mt-1 font-semibold {{ $product->stock_quantity <= 2 ? 'text-[#B45309]' : 'text-[#3F5A45]' }}">
                                    {{ $product->stock_quantity }} available in store
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3">
                            <div class="inline-flex items-center rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] overflow-hidden">
                                <button type="button"
                                        data-update-url="{{ route('cart.update', $product) }}"
                                        data-product-id="{{ $product->id }}"
                                        data-delta="-1"
                                        onclick="adjustItemQty(this)"
                                        aria-label="Decrease quantity"
                                        class="px-3 py-1.5 text-sm font-bold text-[#5C4E42] hover:bg-[#E5DEC9] active:bg-[#D4C8B4] transition">
                                    −
                                </button>
                                <span id="item-qty-display-{{ $product->id }}"
                                      class="px-3 py-1.5 text-xs font-bold text-[#2E2A25] min-w-[2.25rem] text-center bg-white border-x border-[#E2D8C3]">
                                    {{ $qty }}
                                </span>
                                <button type="button"
                                        id="item-plus-btn-{{ $product->id }}"
                                        data-update-url="{{ route('cart.update', $product) }}"
                                        data-product-id="{{ $product->id }}"
                                        data-delta="1"
                                        onclick="adjustItemQty(this)"
                                        @disabled($qty >= $product->stock_quantity)
                                        aria-label="Increase quantity"
                                        class="px-3 py-1.5 text-sm font-bold text-[#3F5A45] hover:bg-[#E5DEC9] active:bg-[#D4C8B4] disabled:opacity-40 disabled:cursor-not-allowed transition">
                                    +
                                </button>
                            </div>

                            <form action="{{ route('cart.remove', $product) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3.5 py-1.5 rounded-lg bg-[#F7E8E6] text-[#9E3B33] hover:bg-[#DFB8B3] text-xs font-semibold transition">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Right Column: Online Payment, Fulfillment Toggle & Loyalty Checkout -->
            <div class="lg:col-span-5">
                <form id="checkoutForm"
                      action="{{ route('checkout.store') }}"
                      method="POST"
                      data-user-id="{{ $user->id }}"
                      data-validate-url="{{ route('api.cart.validate-stock') }}"
                      data-quote-url="{{ route('api.delivery.quote') }}"
                      data-subtotal="{{ $subtotalPesos }}"
                      data-wallet-balance="{{ $walletBalance }}"
                      data-max-points="{{ $maxRedeemablePoints }}"
                      data-earn-rate="{{ $earnRate }}"
                      class="bg-white rounded-3xl border border-[#E2D8C3] p-6 sm:p-7 shadow-sm space-y-5 sticky top-24">
                    @csrf
                    <input type="hidden" name="payment_method" value="pay_online">
                    <input type="hidden" id="fulfillmentTypeInput" name="fulfillment_type" value="{{ $initialFulfillment }}">
                    <input type="hidden" id="deliveryLatInput" name="delivery_lat" value="{{ old('delivery_lat') }}">
                    <input type="hidden" id="deliveryLngInput" name="delivery_lng" value="{{ old('delivery_lng') }}">

                    <h2 class="font-bold text-xl text-[#2E2A25] border-b border-[#F0EAE0] pb-3">
                        Online Checkout & Fulfillment
                    </h2>

                    <!-- Fulfillment Method Toggle -->
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-[#2E2A25] block">How would you like to receive your order?</span>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button type="button"
                                    id="btnModePickup"
                                    onclick="setFulfillmentMode('pickup')"
                                    class="p-3 rounded-2xl border-2 text-left transition {{ $initialFulfillment === 'pickup' ? 'border-[#3F5A45] bg-[#E6EFE8]/60' : 'border-[#E2D8C3] bg-[#FAF7F2] hover:border-[#B8D1BE]' }}">
                                <div class="text-sm font-bold text-[#2E2A25]">🏪 Store Pickup</div>
                                <div class="text-[11px] text-[#5C4E42] mt-0.5">Free • Project 8, QC</div>
                            </button>

                            <button type="button"
                                    id="btnModeDelivery"
                                    onclick="setFulfillmentMode('delivery')"
                                    class="p-3 rounded-2xl border-2 text-left transition {{ $initialFulfillment === 'delivery' ? 'border-[#6B4E3D] bg-[#FDF4E7]' : 'border-[#E2D8C3] bg-[#FAF7F2] hover:border-[#D9B382]' }}">
                                <div class="text-sm font-bold text-[#2E2A25]">🛵 Lalamove</div>
                                <div class="text-[11px] text-[#7A4E1D] mt-0.5">Same-day • Metro Manila</div>
                            </button>
                        </div>
                    </div>

                    <!-- MODE A: Store Pickup -->
                    <div id="pickupFieldsSection" class="bg-[#FDF4E7] rounded-2xl border-2 border-[#D9B382] p-4 space-y-2.5 shadow-sm {{ $initialFulfillment === 'delivery' ? 'hidden' : '' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label for="pickup_scheduled_at" class="text-xs font-bold text-[#6B4E3D] flex items-center gap-1.5">
                                <span>📅</span>
                                <span>Pickup Date & Time <span class="text-[#9E3B33]">*</span></span>
                            </label>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full {{ $hasPerishables ? 'bg-[#9E3B33]' : 'bg-[#6B4E3D]' }} text-[#FAF7F2] text-[10px] font-bold uppercase tracking-wide whitespace-nowrap shrink-0">
                                {{ $hasPerishables ? 'Max 1 Day • Perishable' : 'Max 1 Week' }}
                            </span>
                        </div>
                        <input type="datetime-local"
                               id="pickup_scheduled_at"
                               name="pickup_scheduled_at"
                               value="{{ old('pickup_scheduled_at') }}"
                               min="{{ now()->addMinutes(15)->format('Y-m-d\TH:i') }}"
                               max="{{ $maxPickupDatetime }}"
                               {{ $initialFulfillment === 'pickup' ? 'required' : '' }}
                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#C69C6D] bg-white text-xs font-semibold text-[#2E2A25] focus:outline-none focus:ring-2 focus:ring-[#6B4E3D]/30 focus:border-[#6B4E3D]">
                        <p class="text-[11px] text-[#7A4E1D] leading-relaxed">
                            @if($hasPerishables)
                                ⚠️ Your basket contains <strong>Foods or Drinks</strong> — pickup must be scheduled within <strong>24 hours (1 day)</strong> to preserve freshness.
                            @else
                                Non-perishable items (<strong>Skincare / Other</strong>) may be scheduled for pickup within <strong>7 days (1 week)</strong>.
                            @endif
                            <br>Location: <strong>41 Insurance, Project 8, Quezon City</strong> (9:00 AM – 8:00 PM).
                        </p>
                    </div>

                    <!-- MODE B: Lalamove Express Delivery -->
                    <div id="deliveryFieldsSection" class="bg-[#FDF4E7] rounded-2xl border-2 border-[#D9B382] p-4 space-y-3 shadow-sm {{ $initialFulfillment !== 'delivery' ? 'hidden' : '' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-[#6B4E3D] flex items-center gap-1.5">
                                <span>🛵</span>
                                <span>Lalamove Drop-Off Details <span class="text-[#9E3B33]">*</span></span>
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#6B4E3D] text-[#FAF7F2] text-[10px] font-semibold uppercase whitespace-nowrap shrink-0">
                                Express Moto
                            </span>
                        </div>

                        <!-- Customer Saved Pin Presets -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] uppercase tracking-wider font-semibold text-[#7A4E1D]">
                                    My Saved Pin Presets:
                                </span>
                                <button type="button"
                                        onclick="toggleSavePinForm()"
                                        class="text-[11px] font-bold text-[#3F5A45] hover:underline">
                                    + Save Current Pin
                                </button>
                            </div>

                            <div id="savePinFormBox" class="hidden p-2.5 rounded-xl bg-white border border-[#D9B382] space-y-2">
                                <div class="flex items-center gap-1.5">
                                    <input type="text"
                                           id="newPinLabelInput"
                                           maxlength="24"
                                           placeholder="Pin label (e.g., Home, Dorm, Office)"
                                           class="flex-1 px-2.5 py-1.5 rounded-lg border border-[#D4C8B4] bg-[#FAF7F2] text-xs font-semibold text-[#2E2A25] focus:outline-none focus:border-[#3F5A45]">
                                    <button type="button"
                                            onclick="saveCustomPinPreset()"
                                            class="px-3 py-1.5 rounded-lg bg-[#3F5A45] text-[#FAF7F2] text-[11px] font-semibold hover:bg-[#324837] transition">
                                        Save
                                    </button>
                                    <button type="button"
                                            onclick="toggleSavePinForm(false)"
                                            class="px-2 py-1.5 rounded-lg text-[11px] font-semibold text-[#7A6B5D] hover:text-[#2E2A25]">
                                        Cancel
                                    </button>
                                </div>
                                <p id="savePinErrorText" class="hidden text-[10px] font-semibold text-[#9E3B33]"></p>
                            </div>

                            <div id="customPinPresetsList" class="flex flex-wrap gap-1.5"></div>
                        </div>

                        <div>
                            <label for="delivery_address" class="block text-[11px] font-bold text-[#5C4E42] mb-1">
                                Complete Street / Condo / Barangay Address <span class="text-[#9E3B33]">*</span>
                            </label>
                            <textarea id="delivery_address"
                                      name="delivery_address"
                                      rows="2"
                                      placeholder="e.g., Unit 12B, Avida Towers Vita, EDSA, Quezon City"
                                      class="w-full px-3 py-2 rounded-xl border border-[#C69C6D] bg-white text-xs text-[#2E2A25] focus:outline-none focus:border-[#6B4E3D]">{{ old('delivery_address') }}</textarea>
                        </div>

                        <div>
                            <label for="delivery_recipient_phone" class="block text-[11px] font-bold text-[#5C4E42] mb-1">
                                Recipient Mobile Number (for Rider) <span class="text-[#9E3B33]">*</span>
                            </label>
                            <input type="text"
                                   id="delivery_recipient_phone"
                                   name="delivery_recipient_phone"
                                   value="{{ old('delivery_recipient_phone') }}"
                                   placeholder="0917 123 4567"
                                   class="w-full px-3 py-2 rounded-xl border border-[#C69C6D] bg-white text-xs font-semibold text-[#2E2A25] focus:outline-none focus:border-[#6B4E3D]">
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <button type="button"
                                    id="calculateQuoteBtn"
                                    onclick="fetchDeliveryQuote()"
                                    class="px-3.5 py-2 rounded-xl bg-[#6B4E3D] text-[#FAF7F2] text-xs font-semibold hover:bg-[#573E30] transition shrink-0">
                                Calculate Lalamove Fee
                            </button>
                            <span id="quoteStatusText" class="text-[11px] font-semibold text-[#7A4E1D] text-right">
                                Enter address to get fare
                            </span>
                        </div>
                    </div>

                    <!-- Loyalty Points Discount Box -->
                    <div class="bg-[#E6EFE8]/80 rounded-2xl border border-[#B8D1BE] p-4 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[#2B4230]">Redeem Loyalty Points</span>
                            <span class="px-2 py-0.5 rounded-full bg-[#3F5A45] text-[#FAF7F2] text-[10px] font-semibold">
                                Wallet: {{ number_format($walletBalance, 2) }} pts
                            </span>
                        </div>
                        <p class="text-[11px] text-[#2B4230]/90 leading-relaxed">
                            <strong>1 pt = ₱1.00 off.</strong> Enter points to deduct from this order, or leave at <code>0</code> to save them for the <a href="{{ route('profile.rewards') }}" class="underline font-semibold">Rewards Catalog</a>.
                        </p>

                        @if($walletBalance > 0)
                            <div class="flex items-center gap-2 pt-1">
                                <input type="number"
                                       id="pointsInput"
                                       name="points_to_use"
                                       step="1"
                                       min="0"
                                       max="{{ $maxRedeemablePoints }}"
                                       value="{{ old('points_to_use', 0) }}"
                                       class="w-full px-3 py-2 rounded-xl border border-[#9BB9A2] bg-white text-xs font-bold text-[#2B4230] focus:outline-none focus:border-[#3F5A45]">
                                <button type="button"
                                        id="useMaxPointsBtn"
                                        onclick="applyMaxPoints()"
                                        class="shrink-0 px-3 py-2 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-[11px] font-semibold hover:bg-[#324837] transition">
                                    Use Max ({{ number_format($maxRedeemablePoints, 0) }})
                                </button>
                            </div>
                        @else
                            <input type="hidden" name="points_to_use" value="0">
                            <p class="text-[11px] text-[#5C4E42] italic">No points available yet.</p>
                        @endif
                    </div>

                    <!-- Live Totals Breakdown -->
                    <div class="border-t border-[#F0EAE0] pt-3 space-y-1.5 text-xs">
                        <div class="flex justify-between text-[#5C4E42]">
                            <span>Basket Subtotal</span>
                            <span id="subtotalDisplay" class="font-semibold">₱{{ number_format($subtotalPesos, 2) }}</span>
                        </div>
                        <div id="deliveryFeeRow" class="hidden flex justify-between text-[#7A4E1D] font-semibold">
                            <span id="deliveryFeeLabel">Lalamove Delivery Fee</span>
                            <span id="deliveryFeeDisplay">₱0.00</span>
                        </div>
                        <div class="flex justify-between text-[#3F5A45] font-semibold">
                            <span>Points Discount</span>
                            <span id="discountDisplay">-₱0.00</span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-[#2E2A25] pt-2 border-t border-[#E2D8C3]">
                            <span>Total Paid Online</span>
                            <span id="finalTotalDisplay" class="text-[#6B4E3D]">₱{{ number_format($subtotalPesos, 2) }}</span>
                        </div>
                    </div>

                    <div class="bg-[#FAF7F2] border border-[#E2D8C3] rounded-xl p-2.5 text-[11px] text-[#5C4E42]">
                        ✨ Earn <strong id="pointsEarnedDisplay" class="text-[#3F5A45]">+{{ number_format($subtotalPesos * $earnRate, 2) }} pts</strong> immediately upon payment{{ $user->isBirthdayMonth() ? ' (🎂 2× Birthday Bonus!)' : '' }}.
                    </div>

                    <button type="submit"
                            id="payConfirmBtn"
                            class="w-full py-3.5 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-sm font-semibold hover:bg-[#324837] disabled:opacity-60 disabled:cursor-not-allowed transition shadow-sm">
                        Pay & Confirm Order
                    </button>
                </form>
            </div>
        </div>

        <script>
            const checkoutForm = document.getElementById('checkoutForm');
            const userId = checkoutForm.dataset.userId || 'guest';
            const validateUrl = checkoutForm.dataset.validateUrl;
            const quoteUrl = checkoutForm.dataset.quoteUrl;
            const csrfToken = checkoutForm.querySelector('input[name="_token"]').value;
            const PIN_STORAGE_KEY = `kohanmart_saved_pins_u_${userId}`;

            let subtotalPesos = parseFloat(checkoutForm.dataset.subtotal || '0');
            const walletBalance = parseFloat(checkoutForm.dataset.walletBalance || '0');
            let maxPoints = parseFloat(checkoutForm.dataset.maxPoints || '0');
            const earnRate = parseFloat(checkoutForm.dataset.earnRate || '0.1');

            let fulfillmentMode = document.getElementById('fulfillmentTypeInput').value || 'pickup';
            let deliveryFeePesos = 0;
            let hasValidQuote = false;
            let hasStockConflict = false;
            let noticeAutoHideTimer = null;

            const pointsInput = document.getElementById('pointsInput');
            const useMaxPointsBtn = document.getElementById('useMaxPointsBtn');
            const payConfirmBtn = document.getElementById('payConfirmBtn');
            const conflictBanner = document.getElementById('stockConflictBanner');
            const conflictMessage = document.getElementById('stockConflictMessage');
            const liveStockBadge = document.getElementById('liveStockBadge');
            const liveStockDot = document.getElementById('liveStockDot');
            const liveStockText = document.getElementById('liveStockText');

            const serverBanner = document.getElementById('serverErrorBanner');
            if (serverBanner) {
                setTimeout(() => serverBanner.remove(), 4000);
            }

            function showCheckoutNotice(message, autoDismissMs = 4000) {
                if (noticeAutoHideTimer) {
                    clearTimeout(noticeAutoHideTimer);
                    noticeAutoHideTimer = null;
                }

                conflictMessage.textContent = message;
                conflictBanner.classList.remove('hidden', 'opacity-0');

                if (autoDismissMs > 0) {
                    noticeAutoHideTimer = setTimeout(() => {
                        dismissCheckoutNotice();
                    }, autoDismissMs);
                }
            }

            function dismissCheckoutNotice() {
                if (noticeAutoHideTimer) {
                    clearTimeout(noticeAutoHideTimer);
                    noticeAutoHideTimer = null;
                }
                conflictBanner.classList.add('hidden');
            }

            function formatPesos(amount) {
                return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function loadCustomPinPresets() {
                try {
                    const raw = localStorage.getItem(PIN_STORAGE_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    return [];
                }
            }

            function persistCustomPinPresets(pins) {
                localStorage.setItem(PIN_STORAGE_KEY, JSON.stringify(pins));
            }

            function renderCustomPinPresets() {
                const container = document.getElementById('customPinPresetsList');
                const pins = loadCustomPinPresets();
                container.innerHTML = '';

                if (pins.length === 0) {
                    const emptyHint = document.createElement('span');
                    emptyHint.className = 'text-[11px] text-[#7A6B5D] italic';
                    emptyHint.textContent = 'No saved pins yet. Enter an address below and click "+ Save Current Pin".';
                    container.appendChild(emptyHint);
                    return;
                }

                pins.forEach((pin, idx) => {
                    const chip = document.createElement('div');
                    chip.className = 'inline-flex items-center rounded-lg bg-white border border-[#D9B382] overflow-hidden shadow-2xs';

                    const applyBtn = document.createElement('button');
                    applyBtn.type = 'button';
                    applyBtn.className = 'px-2.5 py-1 text-[11px] font-semibold text-[#5C4E42] hover:bg-[#EFE9DC] transition flex items-center gap-1';
                    applyBtn.textContent = `📍 ${pin.label}`;
                    applyBtn.title = pin.address;
                    applyBtn.addEventListener('click', () => applyCustomPinPreset(pin));

                    const delBtn = document.createElement('button');
                    delBtn.type = 'button';
                    delBtn.className = 'px-1.5 py-1 text-[11px] font-bold text-[#9E3B33] hover:bg-[#F7E8E6] border-l border-[#EFE9DC] transition';
                    delBtn.textContent = '×';
                    delBtn.title = `Remove ${pin.label}`;
                    delBtn.addEventListener('click', () => deleteCustomPinPreset(idx));

                    chip.appendChild(applyBtn);
                    chip.appendChild(delBtn);
                    container.appendChild(chip);
                });
            }

            function toggleSavePinForm(forceState) {
                const box = document.getElementById('savePinFormBox');
                const errorText = document.getElementById('savePinErrorText');
                const labelInput = document.getElementById('newPinLabelInput');

                errorText.classList.add('hidden');
                const shouldShow = typeof forceState === 'boolean' ? forceState : box.classList.contains('hidden');

                if (shouldShow) {
                    const addr = document.getElementById('delivery_address').value.trim();
                    if (addr.length < 5) {
                        const quoteStatus = document.getElementById('quoteStatusText');
                        quoteStatus.textContent = 'Enter a delivery address first before saving a pin';
                        quoteStatus.className = 'text-[11px] font-semibold text-[#9E3B33] text-right';
                        document.getElementById('delivery_address').focus();
                        return;
                    }
                    box.classList.remove('hidden');
                    labelInput.value = '';
                    labelInput.focus();
                } else {
                    box.classList.add('hidden');
                }
            }

            function saveCustomPinPreset() {
                const label = document.getElementById('newPinLabelInput').value.trim();
                const address = document.getElementById('delivery_address').value.trim();
                const phone = document.getElementById('delivery_recipient_phone').value.trim();
                const lat = document.getElementById('deliveryLatInput').value;
                const lng = document.getElementById('deliveryLngInput').value;
                const errorText = document.getElementById('savePinErrorText');

                if (!label) {
                    errorText.textContent = 'Please enter a label (e.g., Home, Dorm, Office).';
                    errorText.classList.remove('hidden');
                    return;
                }
                if (address.length < 5) {
                    errorText.textContent = 'Please enter a complete delivery address first.';
                    errorText.classList.remove('hidden');
                    return;
                }

                const pins = loadCustomPinPresets();
                const existingIndex = pins.findIndex(p => p.label.toLowerCase() === label.toLowerCase());
                const newPin = { label, address, phone, lat, lng };

                if (existingIndex >= 0) {
                    pins[existingIndex] = newPin;
                } else {
                    pins.push(newPin);
                }

                persistCustomPinPresets(pins);
                renderCustomPinPresets();
                toggleSavePinForm(false);
            }

            function applyCustomPinPreset(pin) {
                document.getElementById('delivery_address').value = pin.address || '';
                if (pin.phone) {
                    document.getElementById('delivery_recipient_phone').value = pin.phone;
                }
                document.getElementById('deliveryLatInput').value = pin.lat || '';
                document.getElementById('deliveryLngInput').value = pin.lng || '';
                fetchDeliveryQuote();
            }

            function deleteCustomPinPreset(index) {
                const pins = loadCustomPinPresets();
                pins.splice(index, 1);
                persistCustomPinPresets(pins);
                renderCustomPinPresets();
            }

            function syncSubmitButtonState() {
                if (hasStockConflict) {
                    payConfirmBtn.disabled = true;
                    payConfirmBtn.textContent = 'Resolve Stock Conflict First';
                    return;
                }
                if (fulfillmentMode === 'delivery' && !hasValidQuote) {
                    payConfirmBtn.disabled = true;
                    payConfirmBtn.textContent = 'Calculate Lalamove Fee First';
                    return;
                }
                payConfirmBtn.disabled = false;
                payConfirmBtn.textContent = fulfillmentMode === 'delivery'
                    ? 'Pay & Book Lalamove Rider'
                    : 'Pay & Confirm Order';
            }

            function updateTotals() {
                const activeDeliveryFee = fulfillmentMode === 'delivery' ? deliveryFeePesos : 0;
                const grossTotal = subtotalPesos + activeDeliveryFee;
                maxPoints = Math.min(walletBalance, grossTotal);

                if (pointsInput) pointsInput.max = maxPoints;
                if (useMaxPointsBtn) useMaxPointsBtn.textContent = `Use Max (${Math.floor(maxPoints)})`;

                let pts = pointsInput ? parseFloat(pointsInput.value || '0') : 0;
                if (isNaN(pts) || pts < 0) pts = 0;
                if (pts > maxPoints) {
                    pts = maxPoints;
                    if (pointsInput) pointsInput.value = maxPoints;
                }

                const finalTotal = Math.max(0, grossTotal - pts);
                const earned = (finalTotal * earnRate).toFixed(2);

                document.getElementById('subtotalDisplay').textContent = formatPesos(subtotalPesos);
                document.getElementById('deliveryFeeDisplay').textContent = formatPesos(activeDeliveryFee);
                document.getElementById('discountDisplay').textContent = `-${formatPesos(pts)}`;
                document.getElementById('finalTotalDisplay').textContent = formatPesos(finalTotal);
                document.getElementById('pointsEarnedDisplay').textContent = `+${earned} pts`;
            }

            function setFulfillmentMode(mode) {
                fulfillmentMode = mode;
                document.getElementById('fulfillmentTypeInput').value = mode;
                dismissCheckoutNotice();

                const btnPickup = document.getElementById('btnModePickup');
                const btnDelivery = document.getElementById('btnModeDelivery');
                const pickupSec = document.getElementById('pickupFieldsSection');
                const deliverySec = document.getElementById('deliveryFieldsSection');
                const pickupInput = document.getElementById('pickup_scheduled_at');
                const addrInput = document.getElementById('delivery_address');
                const phoneInput = document.getElementById('delivery_recipient_phone');
                const feeRow = document.getElementById('deliveryFeeRow');

                if (mode === 'delivery') {
                    btnDelivery.className = 'p-3 rounded-2xl border-2 text-left transition border-[#6B4E3D] bg-[#FDF4E7]';
                    btnPickup.className = 'p-3 rounded-2xl border-2 text-left transition border-[#E2D8C3] bg-[#FAF7F2] hover:border-[#B8D1BE]';
                    pickupSec.classList.add('hidden');
                    deliverySec.classList.remove('hidden');
                    feeRow.classList.remove('hidden');

                    pickupInput.required = false;
                    addrInput.required = true;
                    phoneInput.required = true;
                } else {
                    btnPickup.className = 'p-3 rounded-2xl border-2 text-left transition border-[#3F5A45] bg-[#E6EFE8]/60';
                    btnDelivery.className = 'p-3 rounded-2xl border-2 text-left transition border-[#E2D8C3] bg-[#FAF7F2] hover:border-[#D9B382]';
                    deliverySec.classList.add('hidden');
                    pickupSec.classList.remove('hidden');
                    feeRow.classList.add('hidden');

                    pickupInput.required = true;
                    addrInput.required = false;
                    phoneInput.required = false;
                }

                updateTotals();
                syncSubmitButtonState();
            }

            document.getElementById('delivery_address').addEventListener('input', () => {
                document.getElementById('deliveryLatInput').value = '';
                document.getElementById('deliveryLngInput').value = '';
                hasValidQuote = false;
                syncSubmitButtonState();
            });

            async function fetchDeliveryQuote() {
                const deliveryAddress = document.getElementById('delivery_address').value.trim();
                const lat = document.getElementById('deliveryLatInput').value;
                const lng = document.getElementById('deliveryLngInput').value;
                const quoteStatus = document.getElementById('quoteStatusText');
                const calcBtn = document.getElementById('calculateQuoteBtn');

                if (deliveryAddress.length < 5) {
                    quoteStatus.textContent = 'Enter a complete street address first';
                    quoteStatus.className = 'text-[11px] font-semibold text-[#9E3B33] text-right';
                    return;
                }

                calcBtn.disabled = true;
                calcBtn.textContent = 'Calculating...';
                quoteStatus.textContent = 'Checking route distance...';
                quoteStatus.className = 'text-[11px] font-semibold text-[#6B4E3D] text-right';

                try {
                    const payload = { delivery_address: deliveryAddress };
                    if (lat && lng) {
                        payload.lat = parseFloat(lat);
                        payload.lng = parseFloat(lng);
                    }

                    const res = await fetch(quoteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();

                    if (!res.ok || !data.ok) {
                        hasValidQuote = false;
                        deliveryFeePesos = 0;
                        const errMsg = data.errors?.delivery_address?.[0] || data.message || 'Address outside delivery zone';
                        quoteStatus.textContent = errMsg;
                        quoteStatus.className = 'text-[11px] font-bold text-[#9E3B33] text-right';
                        updateTotals();
                        syncSubmitButtonState();
                        return;
                    }

                    hasValidQuote = true;
                    deliveryFeePesos = Math.round(data.fee_minor) / 100;
                    document.getElementById('deliveryLatInput').value = data.lat;
                    document.getElementById('deliveryLngInput').value = data.lng;
                    document.getElementById('deliveryFeeLabel').textContent = `Lalamove Fee (${data.distance_km} km)`;
                    quoteStatus.textContent = `${data.distance_km} km • ~${data.eta_minutes} mins (${data.fee_formatted})`;
                    quoteStatus.className = 'text-[11px] font-bold text-[#2B4230] text-right';

                    updateTotals();
                    syncSubmitButtonState();
                } catch (err) {
                    quoteStatus.textContent = 'Unable to fetch quote right now';
                    quoteStatus.className = 'text-[11px] font-semibold text-[#9E3B33] text-right';
                } finally {
                    calcBtn.disabled = false;
                    calcBtn.textContent = 'Calculate Lalamove Fee';
                }
            }

            function applyMaxPoints() {
                if (!pointsInput) return;
                pointsInput.value = Math.floor(maxPoints);
                updateTotals();
            }

            if (pointsInput) {
                pointsInput.addEventListener('input', updateTotals);
            }

            async function adjustItemQty(btn) {
                const url = btn.dataset.updateUrl;
                const productId = btn.dataset.productId;
                const delta = parseInt(btn.dataset.delta, 10);

                btn.disabled = true;

                try {
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ delta })
                    });

                    const data = await res.json();

                    if (!res.ok || !data.ok) {
                        showCheckoutNotice(data.message || 'Could not update item quantity.', 4000);
                        return;
                    }

                    if (typeof window.updateNavCartBadge === 'function') {
                        window.updateNavCartBadge(data.cart_count);
                    }

                    if (data.removed) {
                        const card = document.getElementById(`cart-item-card-${productId}`);
                        if (card) card.remove();

                        if (data.cart_count === 0) {
                            window.location.reload();
                            return;
                        }
                    } else {
                        const qtyDisplay = document.getElementById(`item-qty-display-${productId}`);
                        const plusBtn = document.getElementById(`item-plus-btn-${productId}`);
                        if (qtyDisplay) qtyDisplay.textContent = data.item_qty;
                        if (plusBtn) plusBtn.disabled = data.item_qty >= data.available;
                    }

                    subtotalPesos = Math.round((data.subtotal_minor || 0)) / 100;
                    updateTotals();
                    await pollLiveStock();
                } catch (err) {
                    window.location.reload();
                } finally {
                    if (delta < 0) btn.disabled = false;
                }
            }

            function renderStockState(data) {
                const visibleCardsCount = document.querySelectorAll('[data-cart-item-card]').length;

                if (!data.ok && (!data.items || Object.keys(data.items).length === 0) && visibleCardsCount > 0) {
                    hasStockConflict = false;
                    syncSubmitButtonState();
                    return;
                }

                if (data.items) {
                    Object.values(data.items).forEach(item => {
                        const card = document.getElementById(`cart-item-card-${item.product_id}`);
                        const statusEl = document.getElementById(`item-stock-status-${item.product_id}`);
                        const plusBtn = document.getElementById(`item-plus-btn-${item.product_id}`);
                        if (!card || !statusEl) return;

                        if (plusBtn) {
                            plusBtn.disabled = item.requested >= item.available;
                        }

                        if (!item.in_stock) {
                            card.classList.remove('border-[#E2D8C3]');
                            card.classList.add('border-[#DFB8B3]', 'bg-[#FDF6F5]');
                            statusEl.className = 'text-[11px] mt-1 font-bold text-[#9E3B33]';
                            statusEl.textContent = item.available === 0
                                ? 'Sold out just now — remove item to proceed'
                                : `Only ${item.available} left in store (you have ${item.requested})`;
                        } else {
                            card.classList.remove('border-[#DFB8B3]', 'bg-[#FDF6F5]');
                            card.classList.add('border-[#E2D8C3]');
                            statusEl.className = `text-[11px] mt-1 font-semibold ${item.available <= 2 ? 'text-[#B45309]' : 'text-[#3F5A45]'}`;
                            statusEl.textContent = `${item.available} available in store`;
                        }
                    });
                }

                if (!data.ok) {
                    const wasAlreadyConflicted = hasStockConflict;
                    hasStockConflict = true;
                    if (!wasAlreadyConflicted) {
                        showCheckoutNotice(data.message, 4000);
                    }
                    liveStockBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#F7E8E6] border border-[#DFB8B3] text-[11px] font-semibold text-[#9E3B33]';
                    liveStockDot.className = 'w-2 h-2 rounded-full bg-[#9E3B33]';
                    liveStockText.textContent = 'Stock Conflict Detected';
                } else {
                    hasStockConflict = false;
                    liveStockBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#E6EFE8] border border-[#B8D1BE] text-[11px] font-semibold text-[#2B4230]';
                    liveStockDot.className = 'w-2 h-2 rounded-full bg-[#3F5A45] animate-pulse';
                    liveStockText.textContent = 'Live Stock Verified';
                }

                syncSubmitButtonState();
            }

            async function pollLiveStock() {
                try {
                    const res = await fetch(validateUrl, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    renderStockState(data);
                    return data.ok;
                } catch (e) {
                    return true;
                }
            }

            setInterval(pollLiveStock, 5000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) pollLiveStock();
            });

            checkoutForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (payConfirmBtn.disabled) return;

                dismissCheckoutNotice();
                payConfirmBtn.disabled = true;
                payConfirmBtn.textContent = 'Securing Stock & Processing...';

                try {
                    const formData = new FormData(checkoutForm);
                    const res = await fetch(checkoutForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': formData.get('_token')
                        },
                        body: formData
                    });

                    const rawText = await res.text();
                    let data = null;
                    try {
                        data = JSON.parse(rawText);
                    } catch (parseErr) {
                        data = null;
                    }

                    if (res.ok && data && data.ok) {
                        payConfirmBtn.textContent = 'Confirmed! Redirecting...';
                        window.location.href = data.redirect_url;
                        return;
                    }

                    let errorMsg = 'Unable to complete checkout.';
                    if (data) {
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join('\n');
                        } else if (data.message) {
                            errorMsg = data.message;
                        }
                    } else {
                        errorMsg = `Server error (${res.status}). Check storage/logs/laravel.log for details.`;
                    }

                    showCheckoutNotice(errorMsg, 4500);
                    await pollLiveStock();
                } catch (err) {
                    showCheckoutNotice('Network error while submitting order: ' + (err.message || 'Please try again.'), 4500);
                } finally {
                    syncSubmitButtonState();
                }
            });

            renderCustomPinPresets();
            syncSubmitButtonState();
        </script>
    @endif
</div>
@endsection