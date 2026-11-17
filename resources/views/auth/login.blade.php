@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-[#F3EDE0] p-8 rounded-2xl border border-[#E2D8C3] shadow-sm mt-12">
    <span class="text-[11px] uppercase tracking-widest text-[#3F5A45] font-semibold">いらっしゃいませ</span>
    <h1 class="text-2xl font-bold text-[#2E2A25] mt-0.5 mb-1">Welcome Back</h1>
    <p class="text-xs text-[#6B5D50] mb-6">Sign in to manage pickup reservations or store inventory.</p>

    <form action="{{ route('login') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-[#5C4E42] mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full px-3.5 py-2.5 border border-[#D4C8B4] bg-[#FAF7F2] rounded-xl text-sm focus:outline-none focus:border-[#3F5A45]">
        </div>

        <div>
            <label class="block text-xs font-semibold text-[#5C4E42] mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full px-3.5 py-2.5 border border-[#D4C8B4] bg-[#FAF7F2] rounded-xl text-sm focus:outline-none focus:border-[#3F5A45]">
        </div>

        <button type="submit" class="w-full py-2.5 rounded-xl bg-[#3F5A45] text-[#FAF7F2] font-medium text-sm hover:bg-[#324837] transition shadow-sm">
            Sign In
        </button>
    </form>

    <p class="text-xs text-center text-[#6B5D50] mt-5 pt-4 border-t border-[#DCD0BA]">
        New to Kohan Mart? <a href="{{ route('register') }}" class="text-[#6B4E3D] font-semibold hover:underline">Create an account</a>
    </p>
</div>
@endsection