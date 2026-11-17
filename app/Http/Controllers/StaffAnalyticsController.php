<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class StaffAnalyticsController extends Controller
{
    public function index()
    {
        $analytics = $this->compileAnalyticsData();
        return view('staff.analytics', compact('analytics'));
    }

    public function generateAiSummary(Request $request)
    {
        $analytics = $this->compileAnalyticsData();
        $apiKey = env('GEMINI_API_KEY');

        $prompt = "You are a retail business analyst for Kohan Mart, an authentic Japanese mini-mart in Project 8, Quezon City.\n"
            . "Analyze the store metrics below and return a JSON object with 4 distinct, concise interpretations (each 2-3 sentences or short bullet points in plain English with **bold** highlights for key numbers/actions):\n"
            . "1. 'overall': High-level executive summary of overall store health and top priority.\n"
            . "2. 'revenue_trend': What the 1, 3, 6, 12-month revenue windows, 6-month trend chart, AOV, and Pickup vs. Lalamove split reveal about customer buying behavior.\n"
            . "3. 'product_velocity': What the Most Sold vs. Least Sold products indicate for restocking priorities and promo/bundling strategies.\n"
            . "4. 'forecast': What the 1, 3, 6, 12-month Forecasted Analytics and Inventory Runway mean for upcoming purchasing and stock planning.\n\n"
            . "STORE DATA:\n"
            . "- Revenue Windows: 1M = ₱" . number_format($analytics['descriptive']['revenue_windows']['1m'], 2)
            . ", 3M = ₱" . number_format($analytics['descriptive']['revenue_windows']['3m'], 2)
            . ", 6M = ₱" . number_format($analytics['descriptive']['revenue_windows']['6m'], 2)
            . ", 12M = ₱" . number_format($analytics['descriptive']['revenue_windows']['12m'], 2) . "\n"
            . "- Monthly Revenue (Last 6 Mos): " . json_encode($analytics['descriptive']['monthly_revenue']) . "\n"
            . "- Average Order Value (AOV): ₱" . number_format($analytics['descriptive']['aov'], 2) . "\n"
            . "- Fulfillment Split: " . $analytics['descriptive']['pickup_count'] . " Store Pickups vs " . $analytics['descriptive']['delivery_count'] . " Lalamove Deliveries\n"
            . "- Most Sold Products: " . collect($analytics['descriptive']['top_products'])->map(fn($p) => "{$p->name} ({$p->total_sold} sold, {$p->stock} in stock)")->implode(', ') . "\n"
            . "- Least Sold Products: " . collect($analytics['descriptive']['least_products'])->map(fn($p) => "{$p->name} ({$p->total_sold} sold, {$p->stock} in stock)")->implode(', ') . "\n"
            . "- Forecasted Revenue: 1M = ₱" . number_format($analytics['predictive']['revenue']['1m'], 2)
            . ", 3M = ₱" . number_format($analytics['predictive']['revenue']['3m'], 2)
            . ", 6M = ₱" . number_format($analytics['predictive']['revenue']['6m'], 2)
            . ", 12M = ₱" . number_format($analytics['predictive']['revenue']['12m'], 2) . "\n"
            . "- Forecasted Units Sold: 1M = {$analytics['predictive']['units']['1m']}, 3M = {$analytics['predictive']['units']['3m']}, 6M = {$analytics['predictive']['units']['6m']}, 12M = {$analytics['predictive']['units']['12m']}\n"
            . "- Monthly Growth Trend Rate: {$analytics['predictive']['growth_rate_pct']}%\n\n"
            . "Return ONLY valid JSON with keys: overall, revenue_trend, product_velocity, forecast.";

        if (!empty($apiKey)) {
            try {
                $response = Http::timeout(20)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                        ]
                    ]
                );

                if ($response->successful()) {
                    $rawText = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    $parsed = json_decode($rawText, true);

                    if (is_array($parsed) && isset($parsed['overall'])) {
                        return response()->json([
                            'summaries' => [
                                'overall' => $parsed['overall'] ?? '',
                                'revenue_trend' => $parsed['revenue_trend'] ?? '',
                                'product_velocity' => $parsed['product_velocity'] ?? '',
                                'forecast' => $parsed['forecast'] ?? '',
                            ],
                            'source' => 'Gemini AI',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Fall through to deterministic smart summary
            }
        }

        // Smart local fallback if GEMINI_API_KEY is not set or offline
        $topName = $analytics['descriptive']['top_products']->first()->name ?? 'top Japanese snacks';
        $slowName = $analytics['descriptive']['least_products']->first()->name ?? 'slow-moving pantry items';
        $growth = $analytics['predictive']['growth_rate_pct'];
        $trendWord = $growth >= 0 ? "an upward growth trajectory (+{$growth}% monthly trend)" : "a softer seasonal pace ({$growth}% monthly trend)";
        $dominantChannel = $analytics['descriptive']['delivery_count'] >= $analytics['descriptive']['pickup_count'] ? 'Lalamove delivery' : 'in-store pickup';

        return response()->json([
            'summaries' => [
                'overall' => "Kohan Mart generated **₱" . number_format($analytics['descriptive']['revenue_windows']['1m'], 2) . "** in the last 30 days and **₱" . number_format($analytics['descriptive']['revenue_windows']['12m'], 2) . "** over the past 12 months with a **₱" . number_format($analytics['descriptive']['aov'], 2) . "** Average Order Value. Focus immediate restocking on **{$topName}** while running bundle promotions to clear slower-moving stock.",
                'revenue_trend' => "Your 6-month revenue curve shows customers spend an average of **₱" . number_format($analytics['descriptive']['aov'], 2) . "** per checkout, with **{$dominantChannel}** serving as the primary fulfillment preference ({$analytics['descriptive']['pickup_count']} pickups vs. {$analytics['descriptive']['delivery_count']} deliveries).",
                'product_velocity' => "**{$topName}** leads customer demand and should maintain a higher safety-stock buffer before weekends. Conversely, **{$slowName}** has the lowest turnover—consider pairing it in a discounted snack/meal bundle or offering bonus loyalty points to accelerate sales.",
                'forecast' => "Based on historical order velocity, Kohan Mart is tracking on {$trendWord}, projecting **₱" . number_format($analytics['predictive']['revenue']['3m'], 2) . "** next quarter and **₱" . number_format($analytics['predictive']['revenue']['12m'], 2) . "** across **~" . number_format($analytics['predictive']['units']['12m']) . " units** over the next 12 months.",
            ],
            'source' => 'Smart Analytics Engine',
        ]);
    }

    private function compileAnalyticsData(): array
    {
        $now = Carbon::now();
        $validStatuses = ['confirmed', 'ready_for_pickup', 'out_for_delivery', 'completed'];

        // 1. Descriptive: Historical Revenue Windows (1, 3, 6, 12 months ago) - converted from centavos
        $rev1m = ((float) Order::whereIn('status', $validStatuses)->where('created_at', '>=', $now->copy()->subMonths(1))->sum('total_minor')) / 100;
        $rev3m = ((float) Order::whereIn('status', $validStatuses)->where('created_at', '>=', $now->copy()->subMonths(3))->sum('total_minor')) / 100;
        $rev6m = ((float) Order::whereIn('status', $validStatuses)->where('created_at', '>=', $now->copy()->subMonths(6))->sum('total_minor')) / 100;
        $rev12m = ((float) Order::whereIn('status', $validStatuses)->where('created_at', '>=', $now->copy()->subMonths(12))->sum('total_minor')) / 100;

        $totalOrders12m = Order::whereIn('status', $validStatuses)->where('created_at', '>=', $now->copy()->subMonths(12))->count();
        $aov = $totalOrders12m > 0 ? ($rev12m / $totalOrders12m) : 0;

        $deliveryCount = Order::whereIn('status', $validStatuses)->where('fulfillment_type', 'delivery')->count();
        $pickupCount = Order::whereIn('status', $validStatuses)->where(function ($q) {
            $q->where('fulfillment_type', 'pickup')->orWhereNull('fulfillment_type');
        })->count();

        // 2. Descriptive: Most Sold & Least Sold Products
        $productSales = Product::leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) use ($validStatuses) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->whereIn('orders.status', $validStatuses);
            })
            ->select(
                'products.id',
                'products.name',
                DB::raw('products.price_minor / 100 as price'),
                'products.stock_quantity as stock',
                DB::raw('COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.quantity ELSE 0 END), 0) as total_sold'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.quantity * products.price_minor ELSE 0 END), 0) / 100 as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.price_minor', 'products.stock_quantity')
            ->get();

        $topProducts = $productSales->sortByDesc('total_sold')->take(5)->values();
        $leastProducts = $productSales->sortBy('total_sold')->take(5)->values();

        // 3. Monthly Historical Series (Last 6 Months) for Trend Chart & Linear Regression
        $monthlyLabels = [];
        $monthlyRevenue = [];
        $monthlyUnits = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $monthlyLabels[] = $monthStart->format('M Y');

            $mRev = ((float) Order::whereIn('status', $validStatuses)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total_minor')) / 100;

            $mUnits = (int) OrderItem::whereHas('order', function ($q) use ($validStatuses, $monthStart, $monthEnd) {
                $q->whereIn('status', $validStatuses)->whereBetween('created_at', [$monthStart, $monthEnd]);
            })->sum('quantity');

            $monthlyRevenue[] = $mRev;
            $monthlyUnits[] = $mUnits;
        }

        // 4. Predictive: Linear Regression Forecast for Next 1, 3, 6, 12 Months
        $revForecasts = $this->forecastSeries($monthlyRevenue, [1, 3, 6, 12]);
        $unitForecasts = $this->forecastSeries($monthlyUnits, [1, 3, 6, 12]);

        $avgMonthlyRev = count($monthlyRevenue) > 0 ? array_sum($monthlyRevenue) / count($monthlyRevenue) : 0;
        $growthRatePct = $avgMonthlyRev > 0
            ? round((($revForecasts['1m'] - $avgMonthlyRev) / $avgMonthlyRev) * 100, 1)
            : 5.0;

        // Top 5 Product Individual Forecasts (Next 1, 3, 6, 12 Months)
        $productForecasts = $topProducts->map(function ($prod) use ($growthRatePct) {
            $monthlyBase = max(1, round($prod->total_sold / 6, 1));
            $factor = 1 + ($growthRatePct / 100);
            return [
                'name' => $prod->name,
                'current_stock' => $prod->stock,
                'next_1m' => (int) round($monthlyBase * $factor),
                'next_3m' => (int) round($monthlyBase * 3 * $factor),
                'next_6m' => (int) round($monthlyBase * 6 * $factor),
                'next_12m' => (int) round($monthlyBase * 12 * $factor),
            ];
        });

        return [
            'descriptive' => [
                'revenue_windows' => [
                    '1m' => $rev1m,
                    '3m' => $rev3m,
                    '6m' => $rev6m,
                    '12m' => $rev12m,
                ],
                'aov' => $aov,
                'total_orders_12m' => $totalOrders12m,
                'delivery_count' => $deliveryCount,
                'pickup_count' => $pickupCount,
                'top_products' => $topProducts,
                'least_products' => $leastProducts,
                'monthly_labels' => $monthlyLabels,
                'monthly_revenue' => $monthlyRevenue,
                'monthly_units' => $monthlyUnits,
            ],
            'predictive' => [
                'revenue' => $revForecasts,
                'units' => [
                    '1m' => (int) round($unitForecasts['1m']),
                    '3m' => (int) round($unitForecasts['3m']),
                    '6m' => (int) round($unitForecasts['6m']),
                    '12m' => (int) round($unitForecasts['12m']),
                ],
                'growth_rate_pct' => $growthRatePct,
                'product_forecasts' => $productForecasts,
            ],
        ];
    }

    /**
     * Ordinary Least Squares (OLS) Linear Regression for cumulative multi-month horizons
     */
    private function forecastSeries(array $values, array $horizons): array
    {
        $n = count($values);
        if ($n === 0 || array_sum($values) == 0) {
            return ['1m' => 0, '3m' => 0, '6m' => 0, '12m' => 0];
        }

        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $values[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += ($x * $y);
            $sumX2 += ($x * $x);
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        $slope = $denominator != 0 ? (($n * $sumXY) - ($sumX * $sumY)) / $denominator : 0;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        // Clamp negative slope lightly so forecasts stay realistic
        $avg = $sumY / $n;
        $slope = max(-$avg * 0.08, min($avg * 0.25, $slope));

        $results = [];
        foreach ($horizons as $months) {
            $cumulative = 0;
            for ($step = 1; $step <= $months; $step++) {
                $predictedMonth = max($avg * 0.4, $intercept + ($slope * ($n + $step)));
                $cumulative += $predictedMonth;
            }
            $results["{$months}m"] = round($cumulative, 2);
        }

        return $results;
    }
}