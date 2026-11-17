@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl border border-[#E2D8C3] p-8 shadow-sm">
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#3F5A45] text-[#FAF7F2] font-serif-jp font-bold text-xl flex items-center justify-center mx-auto mb-3 shadow-sm">
                湖畔
            </div>
            <span class="text-[11px] uppercase tracking-widest text-[#6B4E3D] font-semibold">Member Registration</span>
            <h1 class="text-2xl font-bold text-[#2E2A25] mt-1">Create Kohan Mart Account</h1>
            <p class="text-xs text-[#6B5D50] mt-1">Earn loyalty points on every online pickup order.</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div>
                <label class="block font-semibold text-[#5C4E42] mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       placeholder="e.g., Haruka Sato"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] focus:outline-none focus:border-[#3F5A45]">
            </div>

            <div>
                <label class="block font-semibold text-[#5C4E42] mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       placeholder="you@example.com"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] focus:outline-none focus:border-[#3F5A45]">
            </div>

            <div>
                <label class="block font-semibold text-[#5C4E42] mb-1">Birthday <span class="text-[#7A6B5D] font-normal">(For 2× Birthday Month Points)</span></label>
                <input type="date" name="birthday" value="{{ old('birthday') }}"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] focus:outline-none focus:border-[#3F5A45]">
            </div>

            <div>
                <label class="block font-semibold text-[#5C4E42] mb-1">Password</label>
                <input type="password" name="password" required minlength="6"
                       placeholder="Minimum 6 characters"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] focus:outline-none focus:border-[#3F5A45]">
            </div>

            <div>
                <label class="block font-semibold text-[#5C4E42] mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required minlength="6"
                       placeholder="Re-enter password"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] focus:outline-none focus:border-[#3F5A45]">
            </div>

            <!-- Required Terms & Regulation Agreement Checkbox -->
            <div class="bg-[#FAF7F2] border border-[#E2D8C3] rounded-xl p-3.5">
                <label for="agree_terms" class="flex items-start gap-2.5 cursor-pointer select-none">
                    <input type="checkbox"
                           id="agree_terms"
                           name="agree_terms"
                           value="1"
                           {{ old('agree_terms') ? 'checked' : '' }}
                           required
                           class="mt-0.5 w-4 h-4 rounded border-[#C6B8A3] text-[#3F5A45] focus:ring-[#3F5A45] accent-[#3F5A45] cursor-pointer shrink-0">
                    <span class="text-xs text-[#4A3B31] leading-relaxed">
                        I understand the
                        <button type="button"
                                onclick="event.preventDefault(); openTermsModal('register');"
                                class="font-bold text-[#3F5A45] underline decoration-2 underline-offset-2 hover:text-[#2B4230] transition">
                            Terms and Regulation
                        </button>
                        of Kohan Mart
                    </span>
                </label>
                @error('agree_terms')
                    <p class="text-[11px] text-[#9E3B33] font-semibold mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-sm font-semibold hover:bg-[#324837] transition shadow-sm">
                Create Account
            </button>
        </form>

        <p class="text-center text-xs text-[#6B5D50] mt-5">
            Already have an account?
            <a href="{{ route('login') }}" class="text-[#3F5A45] font-semibold hover:underline">Sign in here</a>
        </p>
    </div>
</div>
@endsection