@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E2A25]">Japanese Goods Catalog</h1>
            <p class="text-sm text-[#6B5D50]">Reserve and pay online to lock your items for express pickup at Project 8, Quezon City.</p>
        </div>

        <div class="relative w-full md:w-72">
            <input type="text" id="liveSearchInput"
                   placeholder="Type to filter SKU, name, or item..."
                   autocomplete="off"
                   class="w-full pl-3.5 pr-9 py-2 border border-[#D4C8B4] rounded-xl text-sm focus:outline-none focus:border-[#3F5A45] bg-white shadow-sm">
            <button type="button" id="clearSearchBtn"
                    class="hidden absolute right-3 top-2 text-[#8C7A6B] hover:text-[#2E2A25] text-sm font-bold">
                ✕
            </button>
        </div>
    </div>

    <!-- Category Filter Pills -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-2" id="categoryPills">
            @foreach($categories as $cat)
                <button type="button" data-category="{{ $cat }}"
                        class="category-btn px-4 py-1.5 rounded-full text-xs font-medium border transition {{ $cat === 'All' ? 'bg-[#3F5A45] text-[#FAF7F2] border-[#3F5A45]' : 'bg-[#FAF7F2] text-[#5C4E42] border-[#D4C8B4] hover:border-[#3F5A45]' }}">
                    {{ $cat }}
                </button>
            @endforeach
        </div>

        <span id="resultCounter" class="text-xs text-[#7A6B5D]">
            Showing {{ $products->count() }} item(s)
        </span>
    </div>

    <!-- Product Grid -->
    <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($products as $product)
            <div class="product-card bg-white rounded-2xl border border-[#E2D8C3] overflow-hidden flex flex-col justify-between shadow-sm transition hover:border-[#3F5A45]"
                 data-name="{{ strtolower($product->name) }}"
                 data-sku="{{ strtolower($product->sku) }}"
                 data-desc="{{ strtolower($product->description ?? '') }}"
                 data-category="{{ $product->category }}">
                <div>
                    <div class="h-48 w-full bg-[#EFE9DC] overflow-hidden relative">
                        <img src="{{ $product->image_url ?: 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=800&q=80' }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover hover:scale-105 transition duration-500">
                        <span class="absolute top-3 left-3 px-2.5 py-0.5 rounded-full bg-[#FAF7F2]/90 backdrop-blur text-[#3F5A45] text-[11px] font-semibold">
                            {{ $product->category }}
                        </span>
                    </div>

                    <div class="p-5 pb-3">
                        <span class="text-[11px] text-[#8C7A6B] font-mono">{{ $product->sku }}</span>
                        <h2 class="font-bold text-[#2E2A25] text-base mt-0.5 mb-1">{{ $product->name }}</h2>
                        <p class="text-xs text-[#6B5D50] leading-relaxed">{{ $product->description }}</p>
                    </div>
                </div>

                <div class="p-5 pt-2">
                    <div class="flex items-baseline justify-between mb-3 pt-3 border-t border-[#F0EAE0]">
                        <span class="text-lg font-bold text-[#6B4E3D]">{{ $formatter->format($product->price_minor) }}</span>
                        <span class="text-xs font-medium {{ $product->available_stock > 5 ? 'text-[#3F5A45]' : ($product->available_stock > 0 ? 'text-[#B86B28]' : 'text-[#9E3B33]') }}">
                            {{ $product->available_stock > 0 ? "{$product->available_stock} in stock" : 'Out of stock' }}
                        </span>
                    </div>

                    @if($product->available_stock > 0)
                        @auth
                            @if(auth()->user()->isStaff())
                                <a href="{{ route('staff.dashboard') }}" class="block text-center w-full py-2 rounded-xl bg-[#EFE9DC] text-[#6B4E3D] text-xs font-semibold hover:bg-[#E5DEC9] transition">
                                    Manage in Admin Dashboard
                                </a>
                            @else
                                <form action="{{ route('cart.add', $product) }}" method="POST" class="ajax-cart-form flex gap-2">
                                    @csrf
                                    <input type="number" name="quantity" value="1" min="1" max="{{ $product->available_stock }}"
                                           class="w-16 px-2 py-1.5 border border-[#D4C8B4] rounded-xl text-sm text-center bg-[#FAF7F2]">
                                    <button type="submit" class="flex-1 py-2 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-sm font-medium hover:bg-[#324837] transition">
                                        Add to Basket
                                    </button>
                                </form>
                            @endif
                        @else
                            <button type="button"
                                    onclick="openAuthModal('{{ addslashes($product->name) }}')"
                                    class="w-full py-2.5 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-medium hover:bg-[#324837] transition shadow-sm">
                                Add to Basket
                            </button>
                        @endauth
                    @else
                        <button disabled class="w-full py-2 rounded-xl bg-[#EFE9DC] text-[#9E9082] text-sm font-medium cursor-not-allowed">
                            Unavailable
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div id="emptyState" class="hidden text-center py-12 bg-white rounded-2xl border border-[#E2D8C3] text-[#6B5D50] text-sm">
        No matching Japanese goods found for "<span id="emptyQuery" class="font-semibold text-[#2E2A25]"></span>".
    </div>
</div>

<!-- Centered Cozy HTML+CSS Login Prompt Modal for Guests -->
@guest
<div id="authPromptModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-[#2E2A25]/50 backdrop-blur-sm transition-opacity">
    <div class="bg-[#FAF7F2] border border-[#DCD0BA] rounded-2xl max-w-md w-full p-7 shadow-xl text-center relative">
        <button type="button" onclick="closeAuthModal()"
                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-[#EFE9DC] text-[#5C4E42] hover:bg-[#E2D8C3] flex items-center justify-center text-xs font-bold transition">
            ✕
        </button>

        <div class="w-12 h-12 rounded-xl bg-[#3F5A45] text-[#FAF7F2] font-jp-serif font-bold text-lg flex items-center justify-center mx-auto mb-3 shadow-sm">
            湖畔
        </div>

        <span class="text-[11px] uppercase tracking-widest text-[#6B4E3D] font-semibold">Member Reservation</span>
        <h3 class="font-jp-serif text-2xl font-bold text-[#2E2A25] mt-1 mb-2">Sign in to reserve your basket</h3>
        <p class="text-xs text-[#5C4E42] leading-relaxed mb-6">
            To lock <span id="modalProductName" class="font-semibold text-[#2E2A25]">this item</span> on our Project 8 shelf and track your pickup status, please sign in or create a free Kohan Mart account.
        </p>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('login') }}"
               class="flex-1 py-2.5 px-4 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-sm font-medium hover:bg-[#324837] transition shadow-sm">
                Sign In
            </a>
            <a href="{{ route('register') }}"
               class="flex-1 py-2.5 px-4 rounded-xl bg-[#6B4E3D] text-[#FAF7F2] text-sm font-medium hover:bg-[#573E30] transition shadow-sm">
                Create Account
            </a>
        </div>

        <button type="button" onclick="closeAuthModal()" class="mt-4 text-xs text-[#8C7A6B] hover:text-[#2E2A25] transition">
            Continue browsing catalog
        </button>
    </div>
</div>
@endguest

<script>
function openAuthModal(productName) {
    const modal = document.getElementById('authPromptModal');
    const nameEl = document.getElementById('modalProductName');
    if (modal && nameEl) {
        nameEl.textContent = productName;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeAuthModal() {
    const modal = document.getElementById('authPromptModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('authPromptModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeAuthModal();
        });
    }

    // AJAX Add to Basket (Updates top navbar badge immediately with zero page reload)
    document.querySelectorAll('.ajax-cart-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    if (typeof window.updateNavCartBadge === 'function' && data.cart_count !== undefined) {
                        window.updateNavCartBadge(data.cart_count);
                    }
                    window.showFloatingToast(data.message, 'success');
                } else {
                    window.showFloatingToast(data.message || 'Could not add item to basket.', 'error');
                }
            } catch (err) {
                window.showFloatingToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });

    const searchInput = document.getElementById('liveSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const categoryBtns = document.querySelectorAll('.category-btn');
    const cards = document.querySelectorAll('.product-card');
    const emptyState = document.getElementById('emptyState');
    const emptyQuery = document.getElementById('emptyQuery');
    const resultCounter = document.getElementById('resultCounter');

    let activeCategory = 'All';

    function filterCatalog() {
        const query = searchInput.value.trim().toLowerCase();
        clearBtn.classList.toggle('hidden', query.length === 0);
        let visibleCount = 0;

        cards.forEach(card => {
            const matchesCategory = activeCategory === 'All' || card.dataset.category === activeCategory;
            const matchesSearch = !query ||
                card.dataset.name.includes(query) ||
                card.dataset.sku.includes(query) ||
                card.dataset.desc.includes(query);

            if (matchesCategory && matchesSearch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        resultCounter.textContent = `Showing ${visibleCount} item(s)`;
        emptyState.classList.toggle('hidden', visibleCount > 0);
        emptyQuery.textContent = searchInput.value;
    }

    searchInput.addEventListener('input', filterCatalog);
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterCatalog();
        searchInput.focus();
    });

    categoryBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            activeCategory = btn.dataset.category;
            categoryBtns.forEach(b => {
                const isSelected = b === btn;
                b.className = `category-btn px-4 py-1.5 rounded-full text-xs font-medium border transition ${
                    isSelected
                        ? 'bg-[#3F5A45] text-[#FAF7F2] border-[#3F5A45]'
                        : 'bg-[#FAF7F2] text-[#5C4E42] border-[#D4C8B4] hover:border-[#3F5A45]'
                }`;
            });
            filterCatalog();
        });
    });

    filterCatalog();
});
</script>
@endsection