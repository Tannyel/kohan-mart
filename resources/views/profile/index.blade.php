@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-10 space-y-8">
    <!-- Top Header & Birthday Month Status -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="text-xs uppercase tracking-widest text-[#6B4E3D] font-semibold">Member Account • 湖畔マート</span>
            <h1 class="text-3xl font-bold text-[#2E2A25] mt-1">My Profile & Loyalty Wallet</h1>
        </div>

        @if($user->isBirthdayMonth())
            <div class="px-4 py-2.5 rounded-2xl bg-[#FDF4E7] border border-[#E6C79C] text-[#7A4E1D] text-xs font-semibold flex items-center gap-2 shadow-sm">
                <span>🎂</span>
                <span>Happy Birthday Month, {{ $user->name }}! You earn <strong>2× Double Points (0.20 pts/₱1)</strong> all month!</span>
            </div>
        @endif
    </div>

    <!-- Loyalty Wallet Banner -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-5 bg-[#3F5A45] text-[#FAF7F2] rounded-3xl p-7 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-widest text-[#D7E4DA] font-semibold">Available Incentives</span>
                    <span class="px-2.5 py-1 rounded-full bg-white/15 text-[11px] font-semibold">1 pt = ₱1.00</span>
                </div>

                <div class="mt-5 flex items-baseline gap-3">
                    <span class="font-serif-jp text-5xl font-bold">{{ number_format($user->points_balance, 2) }}</span>
                    <span class="text-sm text-[#D7E4DA]">pts</span>
                </div>
                <p class="text-xs text-[#D7E4DA] mt-2">
                    Redeemable Value: <strong class="text-white">₱{{ number_format($user->points_balance, 2) }}</strong> off your next store pickup order (or 100% free if your points cover the total!).
                </p>
            </div>

            <div class="mt-6 pt-5 border-t border-white/15 space-y-2 text-xs text-[#E6EFE8]">
                <p>• <strong>Earn Rate:</strong> {{ $user->isBirthdayMonth() ? '0.20 pts per ₱1 (Birthday 2× Bonus!)' : '0.10 pts per ₱1 spent on paid orders' }}</p>
                <p>• <strong>How to Use:</strong> Apply points at Checkout as a partial discount or redeem a free item in our Points Catalog.</p>
                <div class="pt-2">
                    <a href="{{ route('profile.rewards') }}"
                       class="inline-block px-4 py-2 rounded-xl bg-[#FAF7F2] text-[#3F5A45] font-semibold text-xs hover:bg-[#EFE9DC] transition shadow-sm">
                        Shop & Redeem Points →
                    </a>
                </div>
            </div>
        </div>

        <!-- Edit Personal Information -->
        <div class="lg:col-span-7 bg-white rounded-3xl border border-[#E2D8C3] p-7 shadow-sm">
            <h2 class="text-xl font-bold text-[#2E2A25]">Personal Information</h2>
            <p class="text-xs text-[#6B5D50] mt-1">Update your display name or birthday anytime.</p>

            <form action="{{ route('profile.update') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#4A3B31] mb-1">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] text-sm focus:outline-none focus:border-[#3F5A45]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#4A3B31] mb-1">Verified Email</label>
                        <input type="email" value="{{ $user->email }}" disabled
                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2D8C3] bg-[#F3EDE0]/70 text-sm text-[#7A6B5D] cursor-not-allowed">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-[#4A3B31] mb-1">Birthday</label>
                        <input type="date" name="birthday"
                               value="{{ old('birthday', $user->birthday?->format('Y-m-d')) }}"
                               max="{{ now()->subDay()->format('Y-m-d') }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#D4C8B4] bg-[#FAF7F2] text-sm focus:outline-none focus:border-[#3F5A45]">
                    </div>

                    <div class="bg-[#FAF7F2] border border-[#E2D8C3] rounded-xl p-3 text-xs text-[#5C4E42]">
                        @if($user->birthday)
                            🎂 Birth Month: <strong>{{ $user->birthday->format('F') }}</strong>
                            ({{ $user->isBirthdayMonth() ? 'Active now — 2× points!' : '2× points automatically unlocked every ' . $user->birthday->format('F') }})
                        @else
                            Add your birthday to unlock <strong>2× Double Points</strong> during your birth month!
                        @endif
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-[#6B4E3D] text-[#FAF7F2] text-xs font-semibold hover:bg-[#573E30] transition shadow-sm">
                        Save Profile Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Points Ledger / Transaction History -->
    <div class="bg-white rounded-3xl border border-[#E2D8C3] p-7 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-[#2E2A25]">Loyalty Points History</h2>
                <p class="text-xs text-[#6B5D50]">Track every welcome bonus, order reward, and point redemption.</p>
            </div>
        </div>

        @if($transactions->isEmpty())
            <p class="text-xs text-[#7A6B5D] py-6 text-center">No points activity recorded yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E2D8C3] text-[#7A6B5D] uppercase tracking-wider text-[11px]">
                            <th class="py-3 pr-4">Date</th>
                            <th class="py-3 pr-4">Activity</th>
                            <th class="py-3 pr-4">Type</th>
                            <th class="py-3 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0EAE0]">
                        @foreach($transactions as $tx)
                            <tr>
                                <td class="py-3.5 pr-4 text-[#6B5D50] whitespace-nowrap">
                                    {{ $tx->created_at->format('M d, Y • h:i A') }}
                                </td>
                                <td class="py-3.5 pr-4 font-medium text-[#2E2A25]">
                                    {{ $tx->description }}
                                </td>
                                <td class="py-3.5 pr-4">
                                    @php
                                        $badgeStyles = match($tx->type) {
                                            'welcome_bonus' => 'bg-[#E6EFE8] text-[#2B4230]',
                                            'earned' => 'bg-[#E6EFE8] text-[#2B4230]',
                                            'redeemed' => 'bg-[#FDF4E7] text-[#7A4E1D]',
                                            'refunded' => 'bg-[#EEF2F6] text-[#3B526B]',
                                            default => 'bg-[#F3EDE0] text-[#5C4E42]',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold capitalize {{ $badgeStyles }}">
                                        {{ str_replace('_', ' ', $tx->type) }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-right font-bold whitespace-nowrap {{ $tx->points >= 0 ? 'text-[#3F5A45]' : 'text-[#9E3B33]' }}">
                                    {{ $tx->points >= 0 ? '+' : '' }}{{ number_format($tx->points, 2) }} pts
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection