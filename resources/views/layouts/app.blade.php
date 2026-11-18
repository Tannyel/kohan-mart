<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kohan Mart (湖畔マート) — Authentic Japanese Mini Mart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600;700&family=Shippori+Mincho:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        html {
            scroll-behavior: smooth;
        }
        body {
            font-family: 'Noto Sans JP', sans-serif;
            background-color: #FAF7F2;
            color: #2E2A25;
        }
        h1, h2, h3, .font-serif-jp {
            font-family: 'Shippori Mincho', serif;
        }
        @keyframes marquee {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
        .animate-marquee {
            display: flex;
            width: max-content;
            animation: marquee 32s linear infinite;
        }
        .animate-marquee:hover {
            animation-play-state: paused;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #FFFFFF !important;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Top Announcement Bar -->
    <div class="no-print bg-[#3F5A45] text-[#FAF7F2] text-xs py-2 px-4 text-center tracking-wide">
        湖畔マート • Authentic Japanese Goods in Project 8, Quezon City — Reserve & Pay Online for Express Store Pickup
    </div>

    <!-- Main Navigation Header -->
    <header class="no-print bg-[#FAF7F2]/95 backdrop-blur border-b border-[#E2D8C3] sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-4">
            <!-- Brand Logo (Routes to Home) -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-[#3F5A45] text-[#FAF7F2] flex items-center justify-center font-serif-jp font-bold text-lg shadow-sm group-hover:bg-[#324837] transition">
                    湖畔
                </div>
                <div>
                    <span class="font-serif-jp font-bold text-xl text-[#2E2A25] block leading-none">Kohan Mart</span>
                    <span class="text-[10px] uppercase tracking-[0.2em] text-[#7A6B5D] block mt-1">Japanese Mini Mart</span>
                </div>
            </a>

            <!-- Center Navigation Links -->
            <nav class="flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('home') }}"
                   class="{{ request()->routeIs('home') ? 'text-[#3F5A45] font-bold underline underline-offset-8 decoration-2' : 'text-[#5C4E42] hover:text-[#3F5A45]' }} transition">
                    Home
                </a>
                <a href="{{ route('store.index') }}"
                   class="{{ request()->routeIs('store.index') ? 'text-[#3F5A45] font-bold underline underline-offset-8 decoration-2' : 'text-[#5C4E42] hover:text-[#3F5A45]' }} transition">
                    Catalog
                </a>
                <a href="{{ route('pages.about') }}"
                   class="{{ request()->routeIs('pages.about') ? 'text-[#3F5A45] font-bold underline underline-offset-8 decoration-2' : 'text-[#5C4E42] hover:text-[#3F5A45]' }} transition">
                    About Us
                </a>
                <a href="{{ route('pages.contact') }}"
                   class="{{ request()->routeIs('pages.contact') ? 'text-[#3F5A45] font-bold underline underline-offset-8 decoration-2' : 'text-[#5C4E42] hover:text-[#3F5A45]' }} transition">
                    Contact
                </a>
            </nav>

            <!-- Right User / Staff Actions -->
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                @auth
                    @if(auth()->user()->isStaff())
                        <a href="{{ route('staff.dashboard') }}"
                           class="px-4 py-2 rounded-xl bg-[#6B4E3D] text-[#FAF7F2] font-semibold hover:bg-[#573E30] transition shadow-sm">
                            Admin Dashboard
                        </a>
                    @else
                        @php
                            $cartCount = array_sum(session('cart', []));
                        @endphp
                        <a href="{{ route('cart.index') }}"
                           class="px-3.5 py-2 rounded-xl bg-[#F3EDE0] border border-[#D4C8B4] text-[#2E2A25] font-semibold hover:border-[#3F5A45] transition flex items-center gap-2">
                            <span>Basket</span>
                            <span id="navCartBadge" class="px-2 py-0.5 rounded-full bg-[#3F5A45] text-[#FAF7F2] text-[11px] font-bold transition-transform {{ $cartCount > 0 ? '' : 'hidden' }}">
                                {{ $cartCount }}
                            </span>
                        </a>
                        <a href="{{ route('store.orders') }}"
                           class="px-3 py-2 text-[#5C4E42] font-semibold hover:text-[#3F5A45] transition">
                            My Reservations
                        </a>
                        <a href="{{ route('profile.index') }}"
                           class="px-3.5 py-2 rounded-xl bg-[#E6EFE8] border border-[#B8D1BE] text-[#2B4230] font-semibold hover:border-[#3F5A45] transition">
                            My Profile ({{ number_format(auth()->user()->points_balance, 0) }} pts)
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-[#7A6B5D] hover:text-[#9E3B33] transition pl-1">
                            Logout ({{ auth()->user()->name }})
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-[#3F5A45] text-[#FAF7F2] font-semibold hover:bg-[#324837] transition">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl border border-[#D4C8B4] text-[#2E2A25] font-semibold hover:border-[#3F5A45] transition">
                        Register
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Fixed Top-Right Floating Toast Notifications -->
    <div id="toastContainer" class="no-print fixed top-20 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none">
        @if(session('success'))
            <div class="auto-toast pointer-events-auto bg-[#E6EFE8] border border-[#B8D1BE] text-[#2B4230] px-4 py-3.5 rounded-2xl shadow-lg text-xs font-medium flex items-start justify-between gap-3 transition-opacity duration-500">
                <span>✓ {{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-[#2B4230]/70 hover:text-[#2B4230] font-bold">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div class="auto-toast pointer-events-auto bg-[#F7E8E6] border border-[#DFB8B3] text-[#9E3B33] px-4 py-3.5 rounded-2xl shadow-lg text-xs font-medium flex items-start justify-between gap-3 transition-opacity duration-500">
                <span>⚠ {{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-[#9E3B33]/70 hover:text-[#9E3B33] font-bold">✕</button>
            </div>
        @endif

        @if($errors->any())
            <div class="auto-toast pointer-events-auto bg-[#F7E8E6] border border-[#DFB8B3] text-[#9E3B33] px-4 py-3.5 rounded-2xl shadow-lg text-xs font-medium flex items-start justify-between gap-3 transition-opacity duration-500">
                <span>⚠ {{ $errors->first() }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-[#9E3B33]/70 hover:text-[#9E3B33] font-bold">✕</button>
            </div>
        @endif
    </div>

    <!-- Page Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Dark Wood Storefront Footer -->
    <footer class="no-print bg-[#3A2B22] border-t border-[#523D31] mt-16 text-[#E6DDD0]">
        <div class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Brand Column -->
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#3F5A45] text-[#FAF7F2] flex items-center justify-center font-serif-jp font-bold text-base shadow-sm">
                        湖畔
                    </div>
                    <div>
                        <span class="font-serif-jp font-bold text-lg text-[#FAF7F2] block leading-none">Kohan Mart</span>
                        <span class="text-[10px] uppercase tracking-[0.2em] text-[#C8B9A6] block mt-0.5">Japanese Mini Mart</span>
                    </div>
                </div>
                <p class="text-xs leading-relaxed text-[#D2C5B4] max-w-sm">
                    Everyday Japanese ramen, bottled teas, pantry staples, snacks, and gentle skincare—reserved and paid online in real time for effortless pickup in Quezon City.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="font-bold text-sm text-[#FAF7F2] mb-3">Quick Navigation</h3>
                <ul class="space-y-2 text-xs text-[#D2C5B4]">
                    <li><a href="{{ route('home') }}" class="hover:text-[#FAF7F2] transition">Home</a></li>
                    <li><a href="{{ route('store.index') }}" class="hover:text-[#FAF7F2] transition">Browse Product Catalog</a></li>
                    <li><a href="{{ route('pages.about') }}" class="hover:text-[#FAF7F2] transition">About Kohan Mart & Pickup Guide</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-[#FAF7F2] transition">Contact & Store Map</a></li>
                    <li>
                        <button type="button" onclick="openTermsModal('footer')" class="hover:text-[#FAF7F2] underline underline-offset-4 transition text-left">
                            Terms & Regulations
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Store Location & Hours -->
            <div class="space-y-2 text-xs text-[#D2C5B4]">
                <h3 class="font-bold text-sm text-[#FAF7F2] mb-3">Visit Our Store</h3>
                <p><strong class="text-[#FAF7F2]">Address:</strong> 41 Insurance, Project 8, Quezon City, Metro Manila</p>
                <p><strong class="text-[#FAF7F2]">Hours:</strong> Monday – Sunday, 9:00 AM – 8:00 PM</p>
                <p><strong class="text-[#FAF7F2]">Inquiries:</strong> +63 (02) 8555-0194 • hello@kohanmart.test</p>
            </div>
        </div>

        <div class="border-t border-[#523D31] py-4 px-4 text-center text-[11px] text-[#B8A896] flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
            <span>© {{ date('Y') }} Kohan Mart (湖畔マート). All rights reserved.</span>
            <span>•</span>
            <button type="button" onclick="openTermsModal('footer')" class="underline hover:text-[#FAF7F2] transition">
                Store Rules & Terms and Regulations
            </button>
        </div>
    </footer>

    <!-- Shared Wabi-Sabi Terms & Regulations / Buying Rules Modal -->
    <div id="termsRulesModal" class="no-print fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div onclick="closeTermsModal()" class="fixed inset-0 bg-[#2E2A25]/60 backdrop-blur-xs transition-opacity"></div>

        <div class="relative z-10 w-full max-w-2xl bg-[#FAF7F2] rounded-3xl border border-[#E2D8C3] shadow-2xl overflow-hidden flex flex-col max-h-[88vh]">
            <!-- Modal Header -->
            <div class="bg-[#3F5A45] px-6 py-4 flex items-center justify-between text-[#FAF7F2] shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center font-serif-jp font-bold text-sm">
                        湖畔
                    </div>
                    <div>
                        <span id="termsModalSubtitle" class="text-[10px] uppercase tracking-widest text-[#DCE8DF] block">
                            Customer Guidelines
                        </span>
                        <h3 id="termsModalTitle" class="font-serif-jp font-bold text-lg leading-tight">
                            Kohan Mart Rules & Regulations
                        </h3>
                    </div>
                </div>
                <button type="button" onclick="closeTermsModal()" class="text-[#FAF7F2]/80 hover:text-white text-xl leading-none px-1">
                    ×
                </button>
            </div>

            <!-- Scrollable Rules & Regulations Content -->
            <div class="p-6 overflow-y-auto space-y-4 text-xs text-[#4A3B31] leading-relaxed">
                <div class="bg-[#F3EDE0] border border-[#E2D8C3] rounded-2xl p-4">
                    <p class="font-semibold text-[#2E2A25]">
                        Welcome to Kohan Mart (湖畔マート). To maintain inventory accuracy, food safety, and reliable same-day fulfillment, all registered members and shoppers must comply with the following formal store Terms & Regulations:
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                        <h4 class="font-bold text-sm text-[#3F5A45]">1. Account Eligibility, Security & Binding Agreement</h4>
                        <p>By registering an account, signing in, or placing an order on the Kohan Mart (湖畔マート) digital storefront, you confirm that all registration details, contact numbers, and delivery addresses provided are accurate and truthful. These Terms & Regulations constitute a binding service agreement between the customer and Kohan Mart.</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                        <h4 class="font-bold text-sm text-[#3F5A45]">2. 100% Online Payment & Atomic Inventory Allocation</h4>
                        <p>All online reservations and courier delivery orders require full online payment at checkout. Inventory is locked atomically at the exact microsecond payment settles—placing items into a shopping basket alone does not reserve stock if another customer completes checkout first.</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                        <h4 class="font-bold text-sm text-[#3F5A45]">3. Store Pickup Deadlines & Perishable Item Window</h4>
                        <p>Store pickup orders must be claimed at <strong>41 Insurance, Project 8, Quezon City</strong> during operating hours (<strong>9:00 AM – 8:00 PM</strong>) by presenting your <strong>Order Number</strong> or <strong>Official Digital Receipt</strong>:</p>
                        <ul class="list-disc pl-5 pt-1 space-y-0.5 text-[#5C4E42]">
                            <li><strong>Foods & Drinks (Perishables):</strong> Strictly must be scheduled and claimed within <strong>1 day (24 hours)</strong> of order placement to guarantee freshness and food safety.</li>
                            <li><strong>Skincare & Non-Perishable Goods:</strong> Must be scheduled and claimed within <strong>1 week (7 calendar days)</strong> of order placement.</li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                        <h4 class="font-bold text-sm text-[#3F5A45]">4. Same-Day Lalamove Courier Dispatch & Delivery</h4>
                        <p>Delivery orders are fulfilled via third-party courier partners (Lalamove) from our Project 8 branch. Customers must ensure a recipient is reachable at the provided mobile number and pin location upon rider arrival.</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                        <h4 class="font-bold text-sm text-[#3F5A45]">5. Order Cancellations, Mandatory Reason & Non-Refundable Delivery Fee</h4>
                        <p>Every order cancellation—whether initiated by the customer or store administrator—requires selecting a valid cancellation reason and providing a written explanation for audit and quality assurance:</p>
                        <ul class="list-disc pl-5 pt-1 space-y-0.5 text-[#5C4E42]">
                            <li><strong>Prior to Rider Assignment / Preparation:</strong> Eligible for a 100% full refund of the item subtotal, delivery fee (if applicable), and redeemed loyalty points.</li>
                            <li><strong>After Lalamove Rider Assignment / Dispatch:</strong> Self-service cancellation is locked. If an order is cancelled after a rider has already been assigned or dispatched, <strong>only the item payment amount is reimbursed—the Lalamove delivery fee is strictly non-reimbursable</strong> as courier dispatch costs have already been incurred.</li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                        <h4 class="font-bold text-sm text-[#3F5A45]">6. Loyalty Points & Rewards Program</h4>
                        <p>Registered members earn <strong>10% back in loyalty points</strong> (or <strong>20% during their registered birthday month</strong>) on net online payments (<code>1 pt = ₱1.00</code>). Points are non-transferable, carry no cash conversion value, and are automatically reversed if the qualifying order is cancelled.</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#E2D8C3] p-4 space-y-1">
                        <h4 class="font-bold text-sm text-[#3F5A45]">7. Product Authenticity, Inspection & Liability</h4>
                        <p>All Japanese snacks, beverages, ramen, and skincare products are inspected prior to counter handoff or courier dispatch. Customers are advised to inspect perishable, fragile, or temperature-sensitive items immediately upon receipt. Kohan Mart reserves the right to cancel and refund any order affected by unforeseen stock discrepancies, force majeure, or courier service disruptions.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Action -->
            <div class="bg-[#F3EDE0] border-t border-[#E2D8C3] px-6 py-4 flex items-center justify-between gap-3 shrink-0">
                <span class="text-[11px] text-[#6B5D50]">Project 8, Quezon City • 湖畔マート</span>
                <button type="button"
                        id="termsModalAcknowledgeBtn"
                        onclick="closeTermsModal()"
                        class="px-5 py-2.5 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition shadow-sm">
                    I Understand & Agree
                </button>
            </div>
        </div>
    </div>

    <script>
        const termsRulesModal = document.getElementById('termsRulesModal');
        const termsModalTitle = document.getElementById('termsModalTitle');
        const termsModalSubtitle = document.getElementById('termsModalSubtitle');
        const termsModalAcknowledgeBtn = document.getElementById('termsModalAcknowledgeBtn');

        function openTermsModal(context = 'general') {
            if (!termsRulesModal) return;

            if (context === 'login') {
                termsModalSubtitle.textContent = 'Welcome Back • Buying Guidelines';
                termsModalTitle.textContent = 'Rules & Regulations for Online Ordering';
                termsModalAcknowledgeBtn.textContent = 'Got It, Start Shopping';
            } else if (context === 'register') {
                termsModalSubtitle.textContent = 'Member Agreement';
                termsModalTitle.textContent = 'Kohan Mart Terms & Regulations';
                termsModalAcknowledgeBtn.textContent = 'I Understand';
            } else {
                termsModalSubtitle.textContent = 'Store Policies';
                termsModalTitle.textContent = 'Terms & Regulations';
                termsModalAcknowledgeBtn.textContent = 'Close';
            }

            termsRulesModal.classList.remove('hidden');
            termsRulesModal.classList.add('flex');
        }

        function closeTermsModal() {
            if (!termsRulesModal) return;
            termsRulesModal.classList.add('hidden');
            termsRulesModal.classList.remove('flex');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && termsRulesModal && !termsRulesModal.classList.contains('hidden')) {
                closeTermsModal();
            }
        });

        function updateNavCartBadge(count) {
            const badge = document.getElementById('navCartBadge');
            if (!badge || typeof count === 'undefined') return;

            badge.textContent = count;
            if (Number(count) > 0) {
                badge.classList.remove('hidden');
                badge.classList.add('scale-125');
                setTimeout(() => badge.classList.remove('scale-125'), 200);
            } else {
                badge.classList.add('hidden');
            }
        }

        function showFloatingToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            const colors = type === 'error'
                ? 'bg-[#F7E8E6] border-[#DFB8B3] text-[#9E3B33]'
                : 'bg-[#E6EFE8] border-[#B8D1BE] text-[#2B4230]';

            toast.className = `pointer-events-auto ${colors} border px-4 py-3.5 rounded-2xl shadow-lg text-xs font-medium flex items-start justify-between gap-3 transition-opacity duration-500`;
            toast.innerHTML = `
                <span>${type === 'error' ? '⚠' : '✓'} ${message}</span>
                <button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100 font-bold">✕</button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 3200);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.auto-toast').forEach(toast => {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500);
                }, 3500);
            });

            @if(session('show_rules_modal'))
                openTermsModal('login');
            @endif
        });
    </script>
</body>
</html>