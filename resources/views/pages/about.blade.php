@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-[#F3EDE0] rounded-2xl border border-[#E2D8C3] p-8 md:p-12">
        <p class="text-xs uppercase tracking-widest text-[#3F5A45] font-semibold mb-2">About Kohan Mart • 私たちについて</p>
        <h1 class="text-3xl font-bold text-[#2E2A25] mb-5">{{ $about['heading'] }}</h1>

        <div class="space-y-4 text-sm text-[#4A3B31] leading-relaxed">
            <p>{{ $about['body_1'] }}</p>
            <p>{{ $about['body_2'] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8 pt-8 border-t border-[#DCD0BA]">
            <div>
                <p class="font-jp-serif text-xl font-bold text-[#3F5A45]">100% Authentic</p>
                <p class="text-xs text-[#6B5D50] mt-1">Direct Japanese imports curated for daily living.</p>
            </div>
            <div>
                <p class="font-jp-serif text-xl font-bold text-[#6B4E3D]">Zero Overselling</p>
                <p class="text-xs text-[#6B5D50] mt-1">Atomic stock locking guarantees your reserved items wait for you.</p>
            </div>
            <div>
                <p class="font-jp-serif text-xl font-bold text-[#3F5A45]">Pay on Pickup</p>
                <p class="text-xs text-[#6B5D50] mt-1">Simple, transparent counter checkout in Philippine Pesos.</p>
            </div>
        </div>
    </div>
</div>
@endsection