@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <!-- Top Header & Navigation -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#E2D8C3] pb-5">
        <div>
            <span class="text-[11px] uppercase tracking-[0.2em] font-bold text-[#3F5A45] block">Kohan Mart Intelligence</span>
            <h1 class="font-serif-jp font-bold text-2xl sm:text-3xl text-[#2E2A25] mt-1">Store Analytics & AI Forecasting</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('staff.dashboard') }}" class="px-4 py-2 rounded-xl border border-[#D4C8B4] text-xs font-semibold text-[#5C4E42] hover:border-[#3F5A45] transition">
                ← Back to Admin Dashboard
            </a>
            <button type="button" id="refreshAiSummaryBtn" onclick="fetchAiSummary()" class="px-4 py-2 rounded-xl bg-[#3F5A45] text-[#FAF7F2] text-xs font-semibold hover:bg-[#324837] transition shadow-sm flex items-center gap-2">
                <span>✨ Regenerate AI Insights</span>
            </button>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- 1. OVERALL AI EXECUTIVE SUMMARY                           -->
    <!-- ========================================================= -->
    <section class="bg-gradient-to-br from-[#F3EDE0] to-[#FAF7F2] rounded-3xl border border-[#D4C8B4] p-6 shadow-sm space-y-3">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-[#3F5A45] text-[#FAF7F2] flex items-center justify-center text-sm shadow-sm">
                    ✨
                </div>
                <div>
                    <h2 class="font-serif-jp font-bold text-lg text-[#2E2A25] leading-none">Overall AI Analytics Interpretation</h2>
                    <span class="ai-source-badge text-[10px] uppercase tracking-wider text-[#6B4E3D] font-semibold">Analyzing store telemetry...</span>
                </div>
            </div>
        </div>

        <div id="aiSummaryOverall" class="bg-white/80 rounded-2xl border border-[#E2D8C3] p-5 text-xs sm:text-sm text-[#3A2B22] leading-relaxed">
            <div class="flex items-center gap-2 text-[#6B5D50] animate-pulse">
                <span class="w-2 h-2 rounded-full bg-[#3F5A45]"></span>
                <span>Synthesizing overall store performance with AI...</span>
            </div>
        </div>
    </section>

    <!-- ========================================================= -->
    <!-- 2. DESCRIPTIVE ANALYTICS (HISTORICAL PERFORMANCE)         -->
    <!-- ========================================================= -->
    <section class="space-y-5">
        <div>
            <span class="text-[10px] uppercase tracking-wider font-bold text-[#6B4E3D]">Historical Telemetry</span>
            <h2 class="font-serif-jp font-bold text-xl text-[#2E2A25]">Descriptive Analytics</h2>
        </div>

        <!-- Revenue Earned Windows (1, 3, 6, 12 Months Ago) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-[#E2D8C3] p-5 shadow-xs">
                <span class="text-[11px] font-semibold text-[#7A6B5D] block">Revenue (Last 1 Month)</span>
                <span class="font-serif-jp font-bold text-2xl text-[#3F5A45] mt-1 block">₱{{ number_format($analytics['descriptive']['revenue_windows']['1m'], 2) }}</span>
                <span class="text-[11px] text-[#5C4E42] mt-1 block">Past 30 days</span>
            </div>
            <div class="bg-white rounded-2xl border border-[#E2D8C3] p-5 shadow-xs">
                <span class="text-[11px] font-semibold text-[#7A6B5D] block">Revenue (Last 3 Months)</span>
                <span class="font-serif-jp font-bold text-2xl text-[#2E2A25] mt-1 block">₱{{ number_format($analytics['descriptive']['revenue_windows']['3m'], 2) }}</span>
                <span class="text-[11px] text-[#5C4E42] mt-1 block">Quarterly cumulative</span>
            </div>
            <div class="bg-white rounded-2xl border border-[#E2D8C3] p-5 shadow-xs">
                <span class="text-[11px] font-semibold text-[#7A6B5D] block">Revenue (Last 6 Months)</span>
                <span class="font-serif-jp font-bold text-2xl text-[#2E2A25] mt-1 block">₱{{ number_format($analytics['descriptive']['revenue_windows']['6m'], 2) }}</span>
                <span class="text-[11px] text-[#5C4E42] mt-1 block">Semi-annual total</span>
            </div>
            <div class="bg-white rounded-2xl border border-[#E2D8C3] p-5 shadow-xs">
                <span class="text-[11px] font-semibold text-[#7A6B5D] block">Revenue (Last 12 Months)</span>
                <span class="font-serif-jp font-bold text-2xl text-[#6B4E3D] mt-1 block">₱{{ number_format($analytics['descriptive']['revenue_windows']['12m'], 2) }}</span>
                <span class="text-[11px] text-[#5C4E42] mt-1 block">Annual total ({{ $analytics['descriptive']['total_orders_12m'] }} orders)</span>
            </div>
        </div>

        <!-- Historical Charts (6-Month Trend + Fulfillment Split) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-3xl border border-[#E2D8C3] p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm text-[#2E2A25]">6-Month Revenue & Unit Velocity</h3>
                    <span class="text-[11px] text-[#7A6B5D]">AOV: <strong>₱{{ number_format($analytics['descriptive']['aov'], 2) }}</strong></span>
                </div>
                <div class="h-64">
                    <canvas id="descriptiveTrendChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-[#E2D8C3] p-5 shadow-xs flex flex-col justify-between space-y-4">
                <div>
                    <h3 class="font-bold text-sm text-[#2E2A25]">Fulfillment Channel Mix</h3>
                    <p class="text-[11px] text-[#7A6B5D]">Store Pickup vs. Lalamove Delivery</p>
                </div>
                <div class="h-44 flex items-center justify-center">
                    <canvas id="fulfillmentChart"></canvas>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center text-xs pt-2 border-t border-[#F3EDE0]">
                    <div>
                        <span class="text-[#7A6B5D] block">Store Pickup</span>
                        <strong class="text-[#3F5A45] text-sm">{{ $analytics['descriptive']['pickup_count'] }} orders</strong>
                    </div>
                    <div>
                        <span class="text-[#7A6B5D] block">Lalamove Express</span>
                        <strong class="text-[#6B4E3D] text-sm">{{ $analytics['descriptive']['delivery_count'] }} orders</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Interpretation: Revenue Trend & Channel Mix -->
        <div class="bg-[#FAF7F2] rounded-2xl border border-[#D4C8B4] p-4 flex items-start gap-3">
            <span class="w-7 h-7 rounded-lg bg-[#3F5A45]/10 text-[#3F5A45] flex items-center justify-center text-xs shrink-0 mt-0.5">✨</span>
            <div class="space-y-1 flex-1">
                <span class="text-[10px] uppercase tracking-wider font-bold text-[#3F5A45] block">AI Interpretation • Revenue & Fulfillment Trends</span>
                <div id="aiSummaryRevenueTrend" class="text-xs text-[#3A2B22] leading-relaxed">
                    <span class="text-[#7A6B5D] animate-pulse">Interpreting revenue & fulfillment charts...</span>
                </div>
            </div>
        </div>

        <!-- Most Sold vs Least Sold Products -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-3xl border border-[#E2D8C3] p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm text-[#3F5A45]">🔥 Most Sold Products</h3>
                    <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-[#E6EFE8] text-[#2B4230] font-semibold">Top 5 Performers</span>
                </div>
                <div class="divide-y divide-[#F3EDE0] text-xs">
                    @forelse($analytics['descriptive']['top_products'] as $item)
                        <div class="py-2.5 flex items-center justify-between gap-2">
                            <div>
                                <span class="font-semibold text-[#2E2A25] block">{{ $item->name }}</span>
                                <span class="text-[11px] text-[#7A6B5D]">Stock left: {{ $item->stock }} • ₱{{ number_format($item->price, 2) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-[#3F5A45] block">{{ $item->total_sold }} sold</span>
                                <span class="text-[11px] text-[#6B4E3D]">₱{{ number_format($item->total_revenue, 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-[#7A6B5D]">No product sales recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-[#E2D8C3] p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm text-[#9E3B33]">🐢 Least Sold Products</h3>
                    <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-[#F7E8E6] text-[#9E3B33] font-semibold">Slow Movers</span>
                </div>
                <div class="divide-y divide-[#F3EDE0] text-xs">
                    @forelse($analytics['descriptive']['least_products'] as $item)
                        <div class="py-2.5 flex items-center justify-between gap-2">
                            <div>
                                <span class="font-semibold text-[#2E2A25] block">{{ $item->name }}</span>
                                <span class="text-[11px] text-[#7A6B5D]">Stock on hand: {{ $item->stock }} • ₱{{ number_format($item->price, 2) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-[#9E3B33] block">{{ $item->total_sold }} sold</span>
                                <span class="text-[11px] text-[#7A6B5D]">₱{{ number_format($item->total_revenue, 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-[#7A6B5D]">No products found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- AI Interpretation: Product Velocity -->
        <div class="bg-[#FAF7F2] rounded-2xl border border-[#D4C8B4] p-4 flex items-start gap-3">
            <span class="w-7 h-7 rounded-lg bg-[#6B4E3D]/10 text-[#6B4E3D] flex items-center justify-center text-xs shrink-0 mt-0.5">✨</span>
            <div class="space-y-1 flex-1">
                <span class="text-[10px] uppercase tracking-wider font-bold text-[#6B4E3D] block">AI Interpretation • Product Velocity & Merchandising</span>
                <div id="aiSummaryProductVelocity" class="text-xs text-[#3A2B22] leading-relaxed">
                    <span class="text-[#7A6B5D] animate-pulse">Analyzing top performers & slow-moving stock...</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================= -->
    <!-- 3. FORECASTED ANALYTICS (1, 3, 6, 12 MONTH PROJECTIONS)   -->
    <!-- ========================================================= -->
    <section class="space-y-5 pt-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <span class="text-[10px] uppercase tracking-wider font-bold text-[#3F5A45]">Linear Regression Trend Projection</span>
                <h2 class="font-serif-jp font-bold text-xl text-[#2E2A25]">Forecasted Analytics</h2>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $analytics['predictive']['growth_rate_pct'] >= 0 ? 'bg-[#E6EFE8] text-[#2B4230]' : 'bg-[#F7E8E6] text-[#9E3B33]' }}">
                Projected Monthly Trend: {{ $analytics['predictive']['growth_rate_pct'] >= 0 ? '+' : '' }}{{ $analytics['predictive']['growth_rate_pct'] }}%
            </span>
        </div>

        <!-- Forecast Cards (Revenue & Products Sold) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach(['1m' => 'Next 1 Month', '3m' => 'Next 3 Months', '6m' => 'Next 6 Months', '12m' => 'Next 12 Months'] as $key => $label)
                <div class="bg-[#FAF7F2] rounded-2xl border border-[#D4C8B4] p-5 space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#6B4E3D] block">{{ $label }}</span>
                    <div>
                        <span class="text-[11px] text-[#7A6B5D] block">Forecasted Revenue</span>
                        <span class="font-serif-jp font-bold text-xl text-[#3F5A45]">₱{{ number_format($analytics['predictive']['revenue'][$key], 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-[#E2D8C3] flex items-center justify-between text-xs">
                        <span class="text-[#5C4E42]">Est. Products Sold:</span>
                        <strong class="text-[#2E2A25]">{{ number_format($analytics['predictive']['units'][$key]) }} units</strong>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Product-Level Demand Forecast & Restock Alert Table -->
        <div class="bg-white rounded-3xl border border-[#E2D8C3] p-5 shadow-xs space-y-3 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-sm text-[#2E2A25]">Top Product Demand Forecast & Inventory Runway</h3>
                <span class="text-[11px] text-[#7A6B5D]">Estimated unit demand by horizon</span>
            </div>
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-[#E2D8C3] text-[#7A6B5D] uppercase text-[10px]">
                        <th class="py-2.5 pr-4">Product</th>
                        <th class="py-2.5 px-3">Current Stock</th>
                        <th class="py-2.5 px-3">Next 1 Mo</th>
                        <th class="py-2.5 px-3">Next 3 Mos</th>
                        <th class="py-2.5 px-3">Next 6 Mos</th>
                        <th class="py-2.5 px-3">Next 12 Mos</th>
                        <th class="py-2.5 pl-3 text-right">Restock Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F3EDE0]">
                    @foreach($analytics['predictive']['product_forecasts'] as $pf)
                        @php
                            $needsRestock = $pf['current_stock'] < $pf['next_1m'];
                        @endphp
                        <tr>
                            <td class="py-3 pr-4 font-semibold text-[#2E2A25]">{{ $pf['name'] }}</td>
                            <td class="py-3 px-3 font-mono">{{ $pf['current_stock'] }} units</td>
                            <td class="py-3 px-3 font-semibold text-[#3F5A45]">{{ $pf['next_1m'] }}</td>
                            <td class="py-3 px-3">{{ $pf['next_3m'] }}</td>
                            <td class="py-3 px-3">{{ $pf['next_6m'] }}</td>
                            <td class="py-3 px-3 font-bold">{{ $pf['next_12m'] }}</td>
                            <td class="py-3 pl-3 text-right">
                                @if($needsRestock)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#F7E8E6] text-[#9E3B33]">Restock Soon</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#E6EFE8] text-[#2B4230]">Healthy Stock</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- AI Interpretation: Forecasted Analytics & Inventory Runway -->
        <div class="bg-[#FAF7F2] rounded-2xl border border-[#D4C8B4] p-4 flex items-start gap-3">
            <span class="w-7 h-7 rounded-lg bg-[#3F5A45]/10 text-[#3F5A45] flex items-center justify-center text-xs shrink-0 mt-0.5">✨</span>
            <div class="space-y-1 flex-1">
                <span class="text-[10px] uppercase tracking-wider font-bold text-[#3F5A45] block">AI Interpretation • 12-Month Forecast & Stock Planning</span>
                <div id="aiSummaryForecast" class="text-xs text-[#3A2B22] leading-relaxed">
                    <span class="text-[#7A6B5D] animate-pulse">Interpreting 12-month revenue & inventory projections...</span>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Descriptive 6-Month Revenue & Units Chart
    const trendCtx = document.getElementById('descriptiveTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: @json($analytics['descriptive']['monthly_labels']),
                datasets: [
                    {
                        label: 'Revenue (₱)',
                        data: @json($analytics['descriptive']['monthly_revenue']),
                        backgroundColor: '#3F5A45',
                        borderRadius: 8,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Units Sold',
                        data: @json($analytics['descriptive']['monthly_units']),
                        type: 'line',
                        borderColor: '#6B4E3D',
                        backgroundColor: '#6B4E3D',
                        tension: 0.35,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', position: 'left', grid: { color: '#F3EDE0' } },
                    y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // 2. Fulfillment Channel Doughnut
    const channelCtx = document.getElementById('fulfillmentChart');
    if (channelCtx) {
        new Chart(channelCtx, {
            type: 'doughnut',
            data: {
                labels: ['Store Pickup', 'Lalamove Delivery'],
                datasets: [{
                    data: [{{ $analytics['descriptive']['pickup_count'] }}, {{ $analytics['descriptive']['delivery_count'] }}],
                    backgroundColor: ['#3F5A45', '#6B4E3D'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '68%'
            }
        });
    }

    // 3. Load all 4 AI interpretations on page open
    fetchAiSummary();
});

function formatAiMarkdown(text) {
    return (text || 'Unable to generate interpretation.')
        .replace(/\*\*(.*?)\*\*/g, '<strong class="text-[#2E2A25]">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\n/g, '<br>');
}

async function fetchAiSummary() {
    const overallEl = document.getElementById('aiSummaryOverall');
    const revTrendEl = document.getElementById('aiSummaryRevenueTrend');
    const prodVelEl = document.getElementById('aiSummaryProductVelocity');
    const forecastEl = document.getElementById('aiSummaryForecast');
    const badges = document.querySelectorAll('.ai-source-badge');
    const btn = document.getElementById('refreshAiSummaryBtn');

    if (btn) btn.disabled = true;
    badges.forEach(b => b.textContent = 'Generating AI interpretations...');

    const loadingHtml = '<span class="text-[#7A6B5D] animate-pulse">Synthesizing insights...</span>';
    if (overallEl) overallEl.innerHTML = loadingHtml;
    if (revTrendEl) revTrendEl.innerHTML = loadingHtml;
    if (prodVelEl) prodVelEl.innerHTML = loadingHtml;
    if (forecastEl) forecastEl.innerHTML = loadingHtml;

    try {
        const res = await fetch("{{ route('staff.analytics.ai-summary') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await res.json();
        const s = data.summaries || {};

        if (overallEl) overallEl.innerHTML = formatAiMarkdown(s.overall);
        if (revTrendEl) revTrendEl.innerHTML = formatAiMarkdown(s.revenue_trend);
        if (prodVelEl) prodVelEl.innerHTML = formatAiMarkdown(s.product_velocity);
        if (forecastEl) forecastEl.innerHTML = formatAiMarkdown(s.forecast);

        badges.forEach(b => b.textContent = `Powered by ${data.source || 'AI Engine'}`);
    } catch (e) {
        const errHtml = '<span class="text-[#9E3B33]">Could not connect to AI summary service right now. Please try again.</span>';
        if (overallEl) overallEl.innerHTML = errHtml;
        if (revTrendEl) revTrendEl.innerHTML = errHtml;
        if (prodVelEl) prodVelEl.innerHTML = errHtml;
        if (forecastEl) forecastEl.innerHTML = errHtml;
        badges.forEach(b => b.textContent = 'Connection Error');
    } finally {
        if (btn) btn.disabled = false;
    }
}
</script>
@endsection