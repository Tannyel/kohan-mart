@extends('layouts.app')

@section('content')
@php
    $activeTab = session('active_tab', 'orders');
    $activeOrderTab = session('active_order_tab', 'all');

    $orderCounts = [
        'all' => $orders->count(),
        'confirmed' => $orders->where('status', 'confirmed')->count(),
        'ready_for_pickup' => $orders->where('status', 'ready_for_pickup')->count(),
        'out_for_delivery' => $orders->where('status', 'out_for_delivery')->count(),
        'completed' => $orders->where('status', 'completed')->count(),
        'cancelled' => $orders->where('status', 'cancelled')->count(),
    ];
@endphp

<div class="max-w-[90rem] mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-xs uppercase tracking-widest text-[#6B4E3D] font-semibold">Admin & Store Operations</span>
            <h1 class="text-3xl font-bold text-[#2E2A25]">Kohan Mart Management Portal</h1>
        </div>
        <a href="{{ route('staff.analytics') }}"
           class="px-5 py-2.5 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition shadow-sm flex items-center gap-2">
            <span>📊 AI Assisted Analytics & Forecast</span>
            <span>→</span>
        </a>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="bg-[#F3EDE0] p-5 rounded-2xl border border-[#E2D8C3]">
            <p class="text-xs text-[#6B5D50] uppercase font-semibold">Collected Sales (Paid)</p>
            <p class="text-2xl font-bold text-[#3F5A45] mt-1">{{ $formatter->format((int) $paidTotalMinor) }}</p>
        </div>
        <div class="bg-[#F3EDE0] p-5 rounded-2xl border border-[#E2D8C3]">
            <p class="text-xs text-[#6B5D50] uppercase font-semibold">Customer Orders (Pickup & Delivery)</p>
            <p class="text-2xl font-bold text-[#2E2A25] mt-1">{{ $orders->count() }}</p>
        </div>
        <div class="bg-[#F3EDE0] p-5 rounded-2xl border border-[#E2D8C3]">
            <p class="text-xs text-[#6B5D50] uppercase font-semibold">Low Stock Alerts (≤ 5)</p>
            <p class="text-2xl font-bold {{ $lowStockProducts->count() > 0 ? 'text-[#9E3B33]' : 'text-[#3F5A45]' }} mt-1">
                {{ $lowStockProducts->count() }} item(s)
            </p>
        </div>
    </div>

    <!-- Main Unified Workspace Container with Screen Switcher Buttons -->
    <div class="bg-white rounded-2xl border border-[#E2D8C3] shadow-sm overflow-hidden mb-10">
        <!-- Workspace Top Tab Bar -->
        <div class="bg-[#F3EDE0] border-b border-[#E2D8C3] p-3.5 flex flex-wrap items-center gap-2.5">
            <button type="button" data-tab="orders"
                    class="admin-tab-btn px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 {{ $activeTab === 'orders' ? 'bg-[#3F5A45] text-[#FAF7F2] shadow-sm' : 'bg-[#FAF7F2] text-[#5C4E42] hover:bg-[#E5DEC9]' }}">
                <span>Customer Orders</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10">{{ $orders->count() }}</span>
            </button>

            <button type="button" data-tab="inventory"
                    class="admin-tab-btn px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 {{ $activeTab === 'inventory' ? 'bg-[#3F5A45] text-[#FAF7F2] shadow-sm' : 'bg-[#FAF7F2] text-[#5C4E42] hover:bg-[#E5DEC9]' }}">
                <span>Catalog & Inventory Control</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10">{{ $products->count() }}</span>
            </button>

            <button type="button" data-tab="cms"
                    class="admin-tab-btn px-4 py-2 rounded-xl text-xs font-semibold transition {{ $activeTab === 'cms' ? 'bg-[#3F5A45] text-[#FAF7F2] shadow-sm' : 'bg-[#FAF7F2] text-[#5C4E42] hover:bg-[#E5DEC9]' }}">
                Edit Storefront Pages
            </button>

            <button type="button" data-tab="staff"
                    class="admin-tab-btn px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 {{ $activeTab === 'staff' ? 'bg-[#3F5A45] text-[#FAF7F2] shadow-sm' : 'bg-[#FAF7F2] text-[#5C4E42] hover:bg-[#E5DEC9]' }}">
                <span>Staff & Admin Accounts</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10">{{ $staffUsers->count() }}</span>
            </button>
        </div>

        <!-- SCREEN 1: Customer Orders (Pickup & Lalamove Delivery) -->
        <div id="panel-orders" class="admin-panel p-7 {{ $activeTab !== 'orders' ? 'hidden' : '' }}">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-[#2E2A25]">Customer Pickup & Delivery Orders</h2>
                <p class="text-xs text-[#6B5D50] mt-0.5">
                    <strong>New Orders:</strong> Paid & stock deducted •
                    <strong>Ready For Pickup:</strong> Packed at counter •
                    <strong>Out for Delivery:</strong> Dispatched via Lalamove •
                    <strong>Completed:</strong> Fulfilled
                </p>
            </div>

            <!-- Order Status Sub-Navigation Bar -->
            <div class="flex flex-wrap gap-2 mb-5 pb-4 border-b border-[#EFE9DC]">
                @php
                    $orderStatuses = [
                        'all' => 'All Orders',
                        'confirmed' => 'New Orders',
                        'ready_for_pickup' => 'Ready For Pickup',
                        'out_for_delivery' => 'Out for Delivery',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ];
                @endphp

                @foreach($orderStatuses as $statusKey => $statusLabel)
                    <button type="button" data-order-status="{{ $statusKey }}"
                            class="order-subtab-btn px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition flex items-center gap-1.5 {{ $activeOrderTab === $statusKey ? 'bg-[#6B4E3D] text-[#FAF7F2] border-[#6B4E3D] shadow-sm' : 'bg-[#FAF7F2] text-[#5C4E42] border-[#D4C8B4] hover:border-[#6B4E3D]' }}">
                        <span>{{ $statusLabel }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeOrderTab === $statusKey ? 'bg-white/20 text-white' : 'bg-[#EFE9DC] text-[#5C4E42]' }}">
                            {{ $orderCounts[$statusKey] }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="rounded-xl border border-[#E2D8C3] overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#FAF7F2] border-b border-[#E2D8C3] text-xs uppercase text-[#5C4E42]">
                        <tr>
                            <th class="p-4">Order #</th>
                            <th class="p-4">Customer & Fulfillment</th>
                            <th class="p-4">Ordered Items</th>
                            <th class="p-4">Total & Payment</th>
                            <th class="p-4 min-w-[380px]">Fulfillment Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0EAE0]" id="ordersTableBody">
                        @forelse($orders as $order)
                            @php
                                $isPointsRedemption = ($order->payment_method === 'points_redemption' || $order->payment_method === 'points' || $order->total_minor === 0);
                            @endphp
                            <tr class="order-row {{ ($activeOrderTab !== 'all' && $order->status !== $activeOrderTab) ? 'hidden' : '' }}"
                                data-status="{{ $order->status }}">
                                <td class="p-4 align-top">
                                    <div class="font-mono font-bold text-[#2E2A25] whitespace-nowrap">{{ $order->order_number }}</div>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $order->isDelivery() ? 'bg-[#FDF4E7] text-[#7A4E1D] border-[#D9B382]' : 'bg-[#F3EDE0] text-[#4A3B31] border-[#D4C8B4]' }}">
                                        {{ $order->isDelivery() ? '🛵 Lalamove' : '🏪 Pickup' }}
                                    </span>
                                </td>
                                <td class="p-4 align-top max-w-xs">
                                    <div class="font-semibold text-[#2E2A25]">{{ $order->user->name }}</div>
                                    @if($order->isDelivery())
                                        <div class="mt-1 p-2 rounded-lg bg-[#FDF4E7] border border-[#E3CCA8] text-[11px] text-[#4A3B31] space-y-0.5">
                                            <div class="font-semibold text-[#7A4E1D]">
                                                📍 {{ $order->delivery_address }}
                                            </div>
                                            @if($order->delivery_recipient_phone)
                                                <div>📞 {{ $order->delivery_recipient_phone }}</div>
                                            @endif
                                            @if($order->courier_driver_name)
                                                <div class="text-[#2B4230] font-semibold">
                                                    Rider: {{ $order->courier_driver_name }} ({{ $order->courier_driver_plate }})
                                                    @if($order->courier_driver_phone)
                                                        • {{ $order->courier_driver_phone }}
                                                    @endif
                                                </div>
                                            @endif
                                            <a href="{{ route('delivery.track', $order) }}"
                                               class="inline-block mt-1 text-[#3F5A45] font-bold hover:underline">
                                                Track Live ShareLink →
                                            </a>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1.5 mt-1 px-2.5 py-0.5 rounded-md bg-[#F3EDE0] border border-[#E2D8C3] text-xs font-semibold text-[#4A3B31] whitespace-nowrap">
                                            <span>📅 {{ $order->pickup_scheduled_at?->format('M d, Y • h:i A') }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4 align-top text-xs text-[#5C4E42] space-y-0.5">
                                    @foreach($order->items as $item)
                                        <div>{{ $item->product->name ?? 'Catalog Item' }} (×{{ $item->quantity }})</div>
                                    @endforeach
                                    @if($order->isDelivery() && $order->delivery_fee_minor > 0)
                                        <div class="text-[#7A4E1D] font-semibold pt-1">
                                            + Delivery: {{ $formatter->format($order->delivery_fee_minor) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4 align-top whitespace-nowrap">
                                    <div class="font-bold text-[#6B4E3D]">{{ $formatter->format($order->total_minor) }}</div>
                                    
                                    @if($isPointsRedemption)
                                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#E6EFE8] text-[#2B4230] border border-[#B8D1BE]">
                                            ⭐ Points Payment
                                        </span>
                                    @elseif($order->is_paid)
                                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E6EFE8] text-[#2B4230] border border-[#B8D1BE]">
                                            ✓ Paid
                                        </span>
                                    @else
                                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F7E8E6] text-[#9E3B33] border border-[#DFB8B3]">
                                            Pending Payment
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 align-top">
                                    @if($order->status === 'cancelled')
                                        <div class="space-y-1.5">
                                            @if($order->cancelled_by === 'customer')
                                                <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-semibold bg-[#F7E8E6] text-[#9E3B33] border border-[#DFB8B3]">
                                                    Cancelled by Customer
                                                </span>
                                            @elseif($order->cancelled_by === 'admin')
                                                <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-semibold bg-[#EFE9DC] text-[#6B4E3D] border border-[#D4C8B4]">
                                                    Cancelled by Store Admin
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-semibold bg-[#F7E8E6] text-[#9E3B33] border border-[#DFB8B3]">
                                                    Cancelled
                                                </span>
                                            @endif

                                            @if($order->delivery_fee_forfeited)
                                                <div class="text-[11px] font-semibold text-[#7A4E1D]">
                                                    Delivery Fee Forfeited • Refunded {{ $formatter->format($order->refunded_minor) }}
                                                </div>
                                            @endif

                                            @if($order->isAwaitingStockReturn())
                                                <div class="p-2.5 rounded-xl bg-[#FDF4E7] border border-[#D9B382] space-y-1.5">
                                                    <div class="text-[11px] font-bold text-[#7A4E1D]">
                                                        ⚠️ Bag with Lalamove Rider (Stock Held)
                                                    </div>
                                                    <form action="{{ route('staff.orders.confirm-return', $order) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="current_order_tab" class="current-order-tab-input" value="{{ $activeOrderTab }}">
                                                        <button type="submit"
                                                                class="px-3 py-1.5 rounded-lg bg-[#3F5A45] text-[#FAF7F2] text-[11px] font-semibold hover:bg-[#324837] transition">
                                                            ✓ Confirm Returned to Shelf
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('staff.orders.update', $order) }}" method="POST"
                                                  class="grid grid-cols-[175px_75px] items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="current_order_tab" class="current-order-tab-input" value="{{ $activeOrderTab }}">

                                                <select name="status" class="w-full border border-[#D4C8B4] bg-[#FAF7F2] rounded-lg px-2.5 py-1.5 text-xs font-medium text-[#2E2A25]">
                                                    <option value="confirmed" @selected($order->status === 'confirmed')>New Order</option>
                                                    @if($order->isDelivery())
                                                        <option value="out_for_delivery" @selected($order->status === 'out_for_delivery')>Out for Delivery</option>
                                                    @else
                                                        <option value="ready_for_pickup" @selected($order->status === 'ready_for_pickup')>Ready For Pickup</option>
                                                    @endif
                                                    <option value="completed" @selected($order->status === 'completed')>Completed</option>
                                                </select>

                                                <button type="submit" class="w-full py-1.5 rounded-lg bg-[#3F5A45] text-[#FAF7F2] text-xs font-medium hover:bg-[#324837] transition text-center">
                                                    Save
                                                </button>
                                            </form>

                                            @if($order->status !== 'completed')
                                                <button type="button"
                                                        data-action="{{ route('staff.orders.update', $order) }}"
                                                        data-order-number="{{ $order->order_number }}"
                                                        data-customer-name="{{ $order->user->name }}"
                                                        onclick="openCancelOrderModal(this)"
                                                        class="px-3 py-1.5 rounded-lg bg-[#F7E8E6] text-[#9E3B33] hover:bg-[#DFB8B3] text-xs font-medium transition whitespace-nowrap">
                                                    Cancel
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-[#7A6B5D] text-xs">No customer orders yet.</td></tr>
                        @endforelse
                        <tr id="noOrdersInTabRow" class="hidden">
                            <td colspan="5" class="p-8 text-center text-[#7A6B5D] text-xs">No orders under this status right now.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SCREEN 2: Catalog & Inventory Control -->
        <div id="panel-inventory" class="admin-panel p-7 {{ $activeTab !== 'inventory' ? 'hidden' : '' }}">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-[#2E2A25]">Catalog & Inventory Control</h2>
                <p class="text-xs text-[#6B5D50]">Add new Japanese items, update stock/prices, or remove discontinued items from the shelf.</p>
            </div>

            <div class="bg-[#FAF7F2] rounded-xl border border-[#E2D8C3] p-5 mb-6">
                <h3 class="font-bold text-sm text-[#3F5A45] mb-3">+ Add New Catalog Item</h3>
                <form action="{{ route('staff.products.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-[#5C4E42] mb-1">SKU Code</label>
                        <input type="text" name="sku" placeholder="KM-FOOD-003" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#5C4E42] mb-1">Product Name</label>
                        <input type="text" name="name" placeholder="Matcha KitKat (12-Pack)" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#5C4E42] mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                            <option value="Foods">Foods</option>
                            <option value="Drinks">Drinks</option>
                            <option value="Skincare">Skincare</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-[#5C4E42] mb-1">Price (in Centavos)</label>
                        <input type="number" name="price_minor" placeholder="25000 = ₱250.00" min="0" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#5C4E42] mb-1">Initial Stock</label>
                        <input type="number" name="stock_quantity" value="10" min="0" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#5C4E42] mb-1">Image URL (Optional)</label>
                        <input type="url" name="image_url" placeholder="https://..." class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-[#5C4E42] mb-1">Short Description</label>
                        <div class="flex gap-2">
                            <input type="text" name="description" placeholder="Authentic Uji matcha wafer chocolates..." class="flex-1 px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                            <button type="submit" class="px-4 py-2 rounded-lg bg-[#6B4E3D] text-[#FAF7F2] font-semibold hover:bg-[#573E30] transition">
                                Add Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-[#E2D8C3] overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#FAF7F2] border-b border-[#E2D8C3] text-xs uppercase text-[#5C4E42]">
                        <tr>
                            <th class="p-4">SKU</th>
                            <th class="p-4">Product</th>
                            <th class="p-4">In Stock</th>
                            <th class="p-4">Update Stock & Price (Centavos)</th>
                            <th class="p-4 text-right">Remove</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0EAE0]">
                        @foreach($products as $product)
                            <tr>
                                <td class="p-4 font-mono text-xs text-[#7A6B5D]">{{ $product->sku }}</td>
                                <td class="p-4 font-semibold text-[#2E2A25]">{{ $product->name }}</td>
                                <td class="p-4">
                                    <span class="font-bold {{ $product->stock_quantity <= 5 ? 'text-[#9E3B33]' : 'text-[#3F5A45]' }}">
                                        {{ $product->stock_quantity }}
                                    </span>
                                    <span class="text-[#8C7A6B] text-xs">units</span>
                                </td>
                                <td class="p-4">
                                    <form action="{{ route('staff.products.update', $product) }}" method="POST" class="flex flex-wrap items-center gap-2.5">
                                        @csrf
                                        @method('PATCH')
                                        <label class="text-xs text-[#6B5D50]">Stock:</label>
                                        <input type="number" name="stock_quantity" value="{{ $product->stock_quantity }}" min="0"
                                               class="w-20 border border-[#D4C8B4] bg-[#FAF7F2] rounded-lg px-2.5 py-1.5 text-xs">
                                        <label class="text-xs text-[#6B5D50]">Centavos:</label>
                                        <input type="number" name="price_minor" value="{{ $product->price_minor }}" min="0"
                                               class="w-24 border border-[#D4C8B4] bg-[#FAF7F2] rounded-lg px-2.5 py-1.5 text-xs">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#3F5A45] text-[#FAF7F2] text-xs font-medium hover:bg-[#324837] transition">
                                            Update
                                        </button>
                                    </form>
                                </td>
                                <td class="p-4 text-right">
                                    <form action="{{ route('staff.products.destroy', $product) }}" method="POST"
                                          onsubmit="return confirm('Remove {{ addslashes($product->name) }} from the catalog?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#F7E8E6] text-[#9E3B33] text-xs font-medium hover:bg-[#DFB8B3] transition">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SCREEN 3: Edit Storefront Pages -->
        <div id="panel-cms" class="admin-panel p-7 {{ $activeTab !== 'cms' ? 'hidden' : '' }}">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-[#2E2A25]">Edit Storefront Pages</h2>
                <p class="text-xs text-[#6B5D50]">Customize your Landing Page banner, About Us story, and Contact details live.</p>
            </div>

            <form action="{{ route('staff.settings.update') }}" method="POST" class="space-y-6 text-xs">
                @csrf

                <div class="bg-[#FAF7F2] p-5 rounded-xl border border-[#E2D8C3]">
                    <h3 class="font-bold text-sm text-[#3F5A45] mb-3 border-b border-[#DCD0BA] pb-1.5">1. Home Page Hero Banner</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Top Badge Text</label>
                            <input type="text" name="hero_badge" value="{{ $settings['hero_badge'] }}" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Banner Image URL</label>
                            <input type="url" name="hero_image" value="{{ $settings['hero_image'] }}" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-semibold text-[#5C4E42] mb-1">Main Headline</label>
                            <input type="text" name="hero_title" value="{{ $settings['hero_title'] }}" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-semibold text-[#5C4E42] mb-1">Subtitle Description</label>
                            <textarea name="hero_subtitle" rows="2" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">{{ $settings['hero_subtitle'] }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-[#FAF7F2] p-5 rounded-xl border border-[#E2D8C3]">
                    <h3 class="font-bold text-sm text-[#3F5A45] mb-3 border-b border-[#DCD0BA] pb-1.5">2. About Us Page</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">About Headline</label>
                            <input type="text" name="about_heading" value="{{ $settings['about_heading'] }}" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Paragraph 1</label>
                            <textarea name="about_body_1" rows="2" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">{{ $settings['about_body_1'] }}</textarea>
                        </div>
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Paragraph 2</label>
                            <textarea name="about_body_2" rows="2" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">{{ $settings['about_body_2'] }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-[#FAF7F2] p-5 rounded-xl border border-[#E2D8C3]">
                    <h3 class="font-bold text-sm text-[#3F5A45] mb-3 border-b border-[#DCD0BA] pb-1.5">3. Contact Us Page</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Store Address</label>
                            <input type="text" name="contact_address" value="{{ $settings['contact_address'] }}" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Pickup Hours</label>
                            <input type="text" name="contact_hours" value="{{ $settings['contact_hours'] }}" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Phone & Email</label>
                            <input type="text" name="contact_phone_email" value="{{ $settings['contact_phone_email'] }}" class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#6B4E3D] text-[#FAF7F2] font-semibold hover:bg-[#573E30] transition shadow-sm">
                        Save Storefront Content
                    </button>
                </div>
            </form>
        </div>

        <!-- SCREEN 4: Staff & Admin Account Management -->
        <div id="panel-staff" class="admin-panel p-7 {{ $activeTab !== 'staff' ? 'hidden' : '' }}">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-[#2E2A25]">Staff & Admin Accounts</h2>
                <p class="text-xs text-[#6B5D50]">Authorize store managers and cashiers without needing direct database access.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-[#FAF7F2] p-5 rounded-xl border border-[#E2D8C3]">
                    <h3 class="font-bold text-sm text-[#3F5A45] mb-3">+ Register New Staff / Admin</h3>
                    <form action="{{ route('staff.users.store') }}" method="POST" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Staff Full Name</label>
                            <input type="text" name="name" placeholder="e.g., Aiko Tanaka" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div>
                            <label class="block font-semibold text-[#5C4E42] mb-1">Work Email</label>
                            <input type="email" name="email" placeholder="aiko@kohanmart.test" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-[#5C4E42] mb-1">Temporary Password</label>
                                <input type="password" name="password" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-[#5C4E42] mb-1">Confirm Password</label>
                                <input type="password" name="password_confirmation" required class="w-full px-3 py-2 rounded-lg border border-[#D4C8B4] bg-white">
                            </div>
                        </div>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-[#3F5A45] text-[#FAF7F2] font-semibold hover:bg-[#324837] transition">
                            Create Staff Account
                        </button>
                    </form>
                </div>

                <div class="rounded-xl border border-[#E2D8C3] overflow-hidden">
                    <div class="bg-[#FAF7F2] px-4 py-3 border-b border-[#E2D8C3] text-xs font-bold uppercase text-[#5C4E42]">
                        Authorized Store Staff ({{ $staffUsers->count() }})
                    </div>
                    <ul class="divide-y divide-[#F0EAE0] text-sm">
                        @foreach($staffUsers as $staff)
                            <li class="p-4 flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-[#2E2A25]">{{ $staff->name }}</p>
                                    <p class="text-xs text-[#7A6B5D]">{{ $staff->email }}</p>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#3F5A45]/10 text-[#3F5A45]">
                                    Staff / Admin
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Persistent Audit / Activity Log Section -->
    <div class="bg-[#F3EDE0] rounded-2xl border border-[#E2D8C3] p-6">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-lg font-bold text-[#2E2A25]">Recent Activity & Change Log</h2>
                <p class="text-xs text-[#6B5D50]">Complete audit trail of inventory updates, storefront edits, and order actions.</p>
            </div>
            <span class="text-xs text-[#7A6B5D]">Showing last {{ $activityLogs->count() }} change(s)</span>
        </div>

        <div class="bg-white rounded-xl border border-[#E2D8C3] divide-y divide-[#F0EAE0] max-h-64 overflow-y-auto text-xs">
            @forelse($activityLogs as $log)
                <div class="px-4 py-2.5 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-[#3F5A45]/10 text-[#3F5A45] font-semibold">{{ $log->action }}</span>
                        <span class="text-[#2E2A25]">{{ $log->details }}</span>
                    </div>
                    <div class="text-[#8C7A6B]">
                        by <strong>{{ $log->user?->name ?? 'System' }}</strong> {{ $log->created_at->diffForHumans() }}
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-[#7A6B5D]">No admin changes recorded yet.</div>
            @endforelse
        </div>
    </div>
</div>

<!-- Aesthetic Wabi-Sabi Cancel Order Modal -->
<div id="cancelOrderModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div onclick="closeCancelOrderModal()" class="fixed inset-0 bg-[#2E2A25]/60 backdrop-blur-xs transition-opacity"></div>

    <div class="relative z-10 w-full max-w-md bg-[#FAF7F2] rounded-3xl border border-[#E2D8C3] shadow-2xl overflow-hidden">
        <div class="bg-[#9E3B33] px-6 py-4 flex items-center justify-between text-[#FAF7F2]">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-full bg-white/15 flex items-center justify-center text-sm font-bold">!</span>
                <h3 class="font-bold text-base tracking-wide">Cancel Customer Order</h3>
            </div>
            <button type="button" onclick="closeCancelOrderModal()" class="text-[#FAF7F2]/80 hover:text-white text-lg leading-none">
                ×
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                <p class="text-[11px] uppercase tracking-wider text-[#7A6B5D] font-semibold">Target Order</p>
                <p class="font-mono font-bold text-base text-[#2E2A25]" id="modalOrderNumber">—</p>
                <p class="text-xs text-[#5C4E42]">Customer: <strong id="modalCustomerName">—</strong></p>
            </div>

            <div class="text-xs text-[#4A3B31] space-y-2 leading-relaxed">
                <p class="font-semibold text-[#2E2A25]">Confirming this cancellation will automatically:</p>
                <ul class="space-y-1.5 pl-1 text-[#5C4E42]">
                    <li class="flex items-start gap-2">
                        <span class="text-[#3F5A45] font-bold">✓</span>
                        <span>Restore shelf stock immediately (or hold until rider bag return if already in transit)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-[#3F5A45] font-bold">✓</span>
                        <span>Forfeit Lalamove delivery fee if rider was already assigned, and refund item balance & points</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-[#3F5A45] font-bold">✓</span>
                        <span>Send an official cancellation email to the customer</span>
                    </li>
                </ul>
            </div>

            <form id="cancelOrderModalForm" method="POST" class="pt-2 flex items-center justify-end gap-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <input type="hidden" name="current_order_tab" id="modalCurrentOrderTab" value="{{ $activeOrderTab }}">

                <button type="button"
                        onclick="closeCancelOrderModal()"
                        class="px-4 py-2.5 rounded-xl border border-[#D4C8B4] bg-white text-xs font-semibold text-[#5C4E42] hover:bg-[#EFE9DC] transition">
                    Keep Order
                </button>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#9E3B33] text-[#FAF7F2] text-xs font-semibold hover:bg-[#822E27] transition shadow-sm">
                    Yes, Cancel Order
                </button>
            </form>
        </div>
    </div>
</div>

<script id="staffPortalScript" data-initial-order-tab="{{ $activeOrderTab }}">
const cancelModal = document.getElementById('cancelOrderModal');
const cancelModalForm = document.getElementById('cancelOrderModalForm');
const modalOrderNumber = document.getElementById('modalOrderNumber');
const modalCustomerName = document.getElementById('modalCustomerName');
const modalCurrentOrderTab = document.getElementById('modalCurrentOrderTab');

function openCancelOrderModal(btn) {
    cancelModalForm.action = btn.dataset.action;
    modalOrderNumber.textContent = btn.dataset.orderNumber;
    modalCustomerName.textContent = btn.dataset.customerName;
    cancelModal.classList.remove('hidden');
    cancelModal.classList.add('flex');
}

function closeCancelOrderModal() {
    cancelModal.classList.add('hidden');
    cancelModal.classList.remove('flex');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !cancelModal.classList.contains('hidden')) {
        closeCancelOrderModal();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const initialOrderTab = document.getElementById('staffPortalScript').dataset.initialOrderTab || 'all';
    const tabButtons = document.querySelectorAll('.admin-tab-btn');
    const panels = document.querySelectorAll('.admin-panel');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            tabButtons.forEach(b => {
                const isActive = b === btn;
                b.className = `admin-tab-btn px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 ${
                    isActive
                        ? 'bg-[#3F5A45] text-[#FAF7F2] shadow-sm'
                        : 'bg-[#FAF7F2] text-[#5C4E42] hover:bg-[#E5DEC9]'
                }`;
            });

            panels.forEach(panel => {
                panel.classList.toggle('hidden', panel.id !== `panel-${target}`);
            });
        });
    });

    const orderSubBtns = document.querySelectorAll('.order-subtab-btn');
    const orderRows = document.querySelectorAll('.order-row');
    const noOrdersRow = document.getElementById('noOrdersInTabRow');
    const hiddenOrderTabInputs = document.querySelectorAll('.current-order-tab-input');

    function filterOrdersByStatus(selectedStatus) {
        let visibleRows = 0;

        orderRows.forEach(row => {
            const show = selectedStatus === 'all' || row.dataset.status === selectedStatus;
            row.classList.toggle('hidden', !show);
            if (show) visibleRows++;
        });

        if (noOrdersRow && orderRows.length > 0) {
            noOrdersRow.classList.toggle('hidden', visibleRows > 0);
        }

        hiddenOrderTabInputs.forEach(input => {
            input.value = selectedStatus;
        });

        if (modalCurrentOrderTab) {
            modalCurrentOrderTab.value = selectedStatus;
        }
    }

    orderSubBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const status = btn.dataset.orderStatus;

            orderSubBtns.forEach(b => {
                const isSelected = b === btn;
                const badge = b.querySelector('span:last-child');

                b.className = `order-subtab-btn px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition flex items-center gap-1.5 ${
                    isSelected
                        ? 'bg-[#6B4E3D] text-[#FAF7F2] border-[#6B4E3D] shadow-sm'
                        : 'bg-[#FAF7F2] text-[#5C4E42] border-[#D4C8B4] hover:border-[#6B4E3D]'
                }`;

                if (badge) {
                    badge.className = `px-1.5 py-0.2 rounded-full text-[10px] ${
                        isSelected ? 'bg-white/20 text-white' : 'bg-[#EFE9DC] text-[#5C4E42]'
                    }`;
                }
            });

            filterOrdersByStatus(status);
        });
    });

    filterOrdersByStatus(initialOrderTab);
});
</script>
@endsection