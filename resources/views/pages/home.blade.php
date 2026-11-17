@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<section class="max-w-6xl mx-auto px-4 pt-10 pb-12">
    <div class="bg-[#F3EDE0] rounded-2xl border border-[#E2D8C3] p-8 md:p-14 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center shadow-sm">
        <div class="lg:col-span-7 space-y-5">
            <span class="inline-block px-3 py-1 rounded-full bg-[#3F5A45]/10 text-[#3F5A45] text-xs font-semibold tracking-wider uppercase">
                {{ $banner['badge'] }}
            </span>
            <h1 class="text-3xl md:text-5xl font-bold text-[#2E2A25] leading-tight">
                {{ $banner['title'] }}
            </h1>
            <p class="text-sm md:text-base text-[#5C4E42] leading-relaxed max-w-xl">
                {{ $banner['subtitle'] }}
            </p>
            <div class="flex flex-wrap gap-3 pt-2">
                <a href="{{ route('store.index') }}" class="px-6 py-3 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-sm font-medium hover:bg-[#324837] transition shadow-sm">
                    Browse Full Catalog
                </a>
                <a href="{{ route('pages.about') }}" class="px-6 py-3 rounded-xl bg-[#FAF7F2] border border-[#D4C8B4] text-[#4A3B31] text-sm font-medium hover:bg-[#EFE9DC] transition">
                    How Pickup Works
                </a>
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="relative rounded-2xl overflow-hidden border border-[#DCD0BA] shadow-md aspect-[4/3]">
                <img src="{{ $banner['image'] }}"
                     alt="Kohan Mart Banner" class="w-full h-full object-cover">
                <div class="absolute bottom-3 left-3 right-3 bg-[#FAF7F2]/90 backdrop-blur px-4 py-2.5 rounded-xl border border-[#E2D8C3] flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-[#2E2A25]">Project 8, Quezon City</p>
                        <p class="text-[11px] text-[#7A6B5D]">Same-day shelf reservation</p>
                    </div>
                    <span class="text-xs font-serif-jp text-[#3F5A45] font-bold">湖畔マート</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Moving Carousel of Catalog Images -->
<section class="py-10 bg-[#F3EDE0]/60 border-y border-[#E5DEC9] overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 mb-6 flex items-end justify-between">
        <div>
            <p class="text-xs uppercase tracking-widest text-[#6B4E3D] font-semibold">Fresh on the Shelf</p>
            <h2 class="text-2xl font-bold text-[#2E2A25]">Curated Catalog Highlights</h2>
        </div>
        <a href="{{ route('store.index') }}" class="text-xs font-semibold text-[#3F5A45] hover:underline">
            View all items →
        </a>
    </div>

    <!-- Continuous Smooth Horizontal Track (Pauses on Hover) -->
    <div class="relative w-full overflow-hidden">
        <div class="animate-marquee gap-5 px-4 flex">
            @foreach($featuredProducts->concat($featuredProducts) as $item)
                <a href="{{ route('store.index') }}"
                   class="w-64 shrink-0 bg-[#FAF7F2] rounded-xl border border-[#E2D8C3] overflow-hidden shadow-sm hover:border-[#3F5A45] transition group">
                    <div class="h-40 w-full overflow-hidden bg-[#EAE2D3]">
                        <img src="{{ $item->image_url ?: 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=600&q=80' }}"
                             alt="{{ $item->name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-4">
                        <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded bg-[#3F5A45]/10 text-[#3F5A45] font-semibold">
                            {{ $item->category }}
                        </span>
                        <h3 class="font-bold text-sm text-[#2E2A25] mt-1.5 truncate">{{ $item->name }}</h3>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-[#EFE9DC]">
                            <span class="font-semibold text-sm text-[#6B4E3D]">{{ $formatter->format($item->price_minor) }}</span>
                            <span class="text-[11px] text-[#7A6B5D]">{{ $item->available_stock ?? $item->stock_quantity }} left</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- 3-Step Value Proposition -->
<section class="max-w-6xl mx-auto px-4 pt-14 pb-6">
    <div class="text-center max-w-xl mx-auto mb-10">
        <h2 class="text-2xl font-bold text-[#2E2A25]">Thoughtful & Simple Store Pickup</h2>
        <p class="text-sm text-[#6B5D50] mt-1">No payment gateway fees or surprise out-of-stock trips.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-[#F3EDE0]/70 p-6 rounded-xl border border-[#E2D8C3]">
            <span class="text-xs font-bold text-[#3F5A45]">01 / BROWSE</span>
            <h3 class="font-bold text-lg text-[#2E2A25] mt-1 mb-2">Check Live Shelf Stock</h3>
            <p class="text-xs text-[#5C4E42] leading-relaxed">
                Every item displays real-time available units, automatically accounting for active customer reservations.
            </p>
        </div>
        <div class="bg-[#F3EDE0]/70 p-6 rounded-xl border border-[#E2D8C3]">
            <span class="text-xs font-bold text-[#6B4E3D]">02 / RESERVE</span>
            <h3 class="font-bold text-lg text-[#2E2A25] mt-1 mb-2">Lock Your Basket</h3>
            <p class="text-xs text-[#5C4E42] leading-relaxed">
                Choose your preferred pickup time. Your items are immediately set aside by our staff for 24 hours.
            </p>
        </div>
        <div class="bg-[#F3EDE0]/70 p-6 rounded-xl border border-[#E2D8C3]">
            <span class="text-xs font-bold text-[#3F5A45]">03 / PICK UP</span>
            <h3 class="font-bold text-lg text-[#2E2A25] mt-1 mb-2">Pay at the Counter</h3>
            <p class="text-xs text-[#5C4E42] leading-relaxed">
                Drop by Kohan Mart in Project 8, inspect your items, and settle your total at the register.
            </p>
        </div>
    </div>
</section>
@endsection