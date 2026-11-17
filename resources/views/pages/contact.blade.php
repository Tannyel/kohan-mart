@extends('layouts.app')

@section('content')
@php
    $storeAddress = $settings['contact_address'] ?? '41 Insurance, Project 8, Quezon City, Metro Manila';
    $encodedAddress = urlencode($storeAddress);
@endphp

<div class="max-w-6xl mx-auto px-4 py-12">
    <div class="text-center max-w-2xl mx-auto mb-10">
        <span class="text-xs uppercase tracking-widest text-[#6B4E3D] font-semibold">Visit Our Store</span>
        <h1 class="text-3xl sm:text-4xl font-bold text-[#2E2A25] mt-1 mb-3">Contact Kohan Mart</h1>
        <p class="text-sm text-[#5C4E42]">
            {{ $settings['contact_intro'] ?? 'Looking for a specific Japanese snack, skincare refill, or bulk pickup arrangement? Visit our Project 8 store or send us a message below.' }}
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left Column: Store Details & Pinned Google Map -->
        <div class="lg:col-span-6 space-y-5">
            <div class="bg-[#F3EDE0] rounded-2xl border border-[#E2D8C3] p-6 space-y-5">
                <div>
                    <h2 class="font-bold text-base text-[#3F5A45] mb-1">Store Pickup Location</h2>
                    <p class="text-sm font-semibold text-[#2E2A25]">{{ $storeAddress }}</p>
                    <p class="text-xs text-[#6B5D50] mt-0.5">Look for the Kohan Mart (湖畔マート) storefront sign along Insurance Street.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-[#E2D8C3]">
                    <div>
                        <h3 class="font-bold text-xs uppercase tracking-wider text-[#3F5A45] mb-1">Pickup & Store Hours</h3>
                        <p class="text-xs text-[#5C4E42] leading-relaxed">{{ $settings['contact_hours'] ?? 'Monday – Sunday, 9:00 AM to 8:00 PM' }}</p>
                    </div>
                    <div>
                        <h3 class="font-bold text-xs uppercase tracking-wider text-[#3F5A45] mb-1">Phone & Email</h3>
                        <p class="text-xs text-[#5C4E42] leading-relaxed">{{ $settings['contact_phone_email'] ?? '+63 (02) 8555-0194 • hello@kohanmart.test' }}</p>
                    </div>
                </div>
            </div>

            <!-- Interactive Google Maps Pin Card -->
            <div class="bg-white rounded-2xl border border-[#E2D8C3] overflow-hidden shadow-sm">
                <div class="px-5 py-3.5 bg-[#FAF7F2] border-b border-[#E2D8C3] flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-base">📍</span>
                        <span class="text-xs font-bold text-[#2E2A25]">Pinned Map: 41 Insurance, Project 8</span>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $encodedAddress }}"
                       target="_blank" rel="noopener noreferrer"
                       class="text-xs font-semibold text-[#3F5A45] hover:text-[#6B4E3D] underline underline-offset-4 transition">
                        Open in Google Maps ↗
                    </a>
                </div>

                <div class="w-full h-72 bg-[#EFE9DC]">
                    <iframe
                        title="Kohan Mart Location — {{ $storeAddress }}"
                        src="https://maps.google.com/maps?q={{ $encodedAddress }}&t=&z=17&ie=UTF8&iwloc=&output=embed"
                        class="w-full h-full border-0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>

        <!-- Right Column: Customer Inquiry Form -->
        <div class="lg:col-span-6 bg-white rounded-2xl border border-[#E2D8C3] p-7 shadow-sm">
            <h2 class="text-xl font-bold text-[#2E2A25] mb-1">Send a Message</h2>
            <p class="text-xs text-[#6B5D50] mb-5">Have a question about an item or scheduled pickup? Our Project 8 team will get back to you.</p>

            <form action="{{ route('pages.contact.send') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase text-[#5C4E42] mb-1">Your Name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] text-sm focus:outline-none focus:border-[#3F5A45]">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-[#5C4E42] mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] text-sm focus:outline-none focus:border-[#3F5A45]">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-[#5C4E42] mb-1">Inquiry / Item Request</label>
                    <textarea name="message" rows="5" required placeholder="Hi Kohan Mart! Do you have Ichiran Ramen or Hada Labo refills available this week?"
                              class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] text-sm focus:outline-none focus:border-[#3F5A45]">{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-[#3F5A45] text-[#FAF7F2] font-medium text-sm hover:bg-[#324837] transition shadow-sm">
                    Send Inquiry to Store
                </button>
            </form>
        </div>
    </div>
</div>
@endsection