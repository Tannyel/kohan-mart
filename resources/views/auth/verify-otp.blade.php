@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-14">
    <div class="bg-white rounded-3xl border border-[#E2D8C3] p-8 shadow-sm text-center">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-[#3F5A45]/10 text-[#3F5A45] flex items-center justify-center font-serif-jp font-bold text-2xl mb-4">
            認証
        </div>

        <h1 class="text-2xl font-bold text-[#2E2A25]">Verify Your Email</h1>
        <p class="text-xs text-[#6B5D50] mt-2 leading-relaxed">
            We sent a 6-digit verification code to<br>
            <strong class="text-[#2E2A25]">{{ $user->email }}</strong>
        </p>

        @if(session('otp_fallback_notice'))
            <div class="mt-4 p-3 rounded-xl bg-[#FDF4E7] border border-[#E6C79C] text-[#7A4E1D] text-xs">
                {{ session('otp_fallback_notice') }}
            </div>
        @endif

        <form action="{{ route('otp.verify') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] uppercase tracking-widest text-[#6B4E3D] font-bold mb-2">
                    Enter 6-Digit Code
                </label>
                <input type="text"
                       name="otp_code"
                       maxlength="6"
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       autofocus
                       required
                       placeholder="000000"
                       class="w-full text-center font-mono text-3xl tracking-[0.5em] py-3.5 px-4 rounded-2xl border-2 border-[#D4C8B4] bg-[#FAF7F2] text-[#3F5A45] font-bold focus:outline-none focus:border-[#3F5A45]">
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-sm font-semibold hover:bg-[#324837] transition shadow-sm">
                Verify & Claim +20 Points
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-[#F0EAE0] flex items-center justify-between text-xs">
            <span class="text-[#7A6B5D]">Didn't receive the email?</span>
            <form action="{{ route('otp.resend') }}" method="POST">
                @csrf
                <button type="submit" class="text-[#6B4E3D] font-semibold hover:underline">
                    Resend Code
                </button>
            </form>
        </div>
    </div>
</div>
@endsection