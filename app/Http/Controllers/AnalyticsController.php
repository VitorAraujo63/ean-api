<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get detailed analytics for specific category
     */
    public function categoryAnalytics($categoryId, Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $dateRange = $this->getDateRange($period);

        $category = Category::findOrFail($categoryId);

        $analytics = DB::table('sales')
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('products.category_id', $categoryId)
            ->whereBetween('sales.sale_date', $dateRange)
            ->selectRaw('
                COUNT(*) as total_sales,
                SUM(sales.price + sales.shipping) as total_revenue,
                AVG(sales.price + sales.shipping) as avg_order_value,
                MIN(sales.price + sales.shipping) as min_order,
                MAX(sales.price + sales.shipping) as max_order
            ')
            ->first();

        // Daily breakdown
        $dailyData = DB::table('sales')
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('products.category_id', $categoryId)
            ->whereBetween('sales.sale_date', $dateRange)
            ->selectRaw('DATE(sales.sale_date) as date, SUM(sales.price + sales.shipping) as revenue, COUNT(*) as sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'summary' => [
                    'total_sales' => $analytics->total_sales ?? 0,
                    'total_revenue' => $analytics->total_revenue ?? 0,
                    'formatted_revenue' => 'R$ ' . number_format($analytics->total_revenue ?? 0, 2, ',', '.'),
                    'avg_order_value' => $analytics->avg_order_value ?? 0,
                    'min_order' => $analytics->min_order ?? 0,
                    'max_order' => $analytics->max_order ?? 0
                ],
                'daily_breakdown' => $dailyData->map(function($day) {
                    return [
                        'date' => Carbon::parse($day->date)->format('d/m'),
                        'revenue' => $day->revenue,
                        'sales' => $day->sales
                    ];
                })
            ]
        ]);
    }

    /**
     * Get profit analysis with detailed breakdown
     */
    public function profitAnalysis(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $dateRange = $this->getDateRange($period);

        // Overall profit analysis
        $profitData = DB::table('sales')
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', $dateRange)
            ->selectRaw('
                SUM(sales.price + sales.shipping) as total_revenue,
                SUM(COALESCE(products.cost, 0)) as total_cost,
                SUM((sales.price + sales.shipping) - COALESCE(products.cost, 0)) as total_profit,
                COUNT(*) as total_transactions
            ')
            ->first();

        // Profit by category
        $categoryProfits = DB::table('sales')
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.sale_date', $dateRange)
            ->selectRaw('
                categories.name as category_name,
                SUM(sales.price + sales.shipping) as revenue,
                SUM(COALESCE(products.cost, 0)) as cost,
                SUM((sales.price + sales.shipping) - COALESCE(products.cost, 0)) as profit,
                COUNT(*) as sales_count
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('profit', 'desc')
            ->get();

        $totalRevenue = $profitData->total_revenue ?? 0;
        $totalCost = $profitData->total_cost ?? 0;
        $totalProfit = $profitData->total_profit ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_revenue' => $totalRevenue,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'profit_margin' => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0,
                    'total_transactions' => $profitData->total_transactions ?? 0,
                    'formatted' => [
                        'revenue' => 'R$ ' . number_format($totalRevenue, 2, ',', '.'),
                        'cost' => 'R$ ' . number_format($totalCost, 2, ',', '.'),
                        'profit' => 'R$ ' . number_format($totalProfit, 2, ',', '.')
                    ]
                ],
                'by_category' => $categoryProfits->map(function($item) {
                    $profitMargin = $item->revenue > 0 ? round(($item->profit / $item->revenue) * 100, 2) : 0;
                    return [
                        'category' => $item->category_name,
                        'revenue' => $item->revenue,
                        'cost' => $item->cost,
                        'profit' => $item->profit,
                        'profit_margin' => $profitMargin,
                        'sales_count' => $item->sales_count,
                        'formatted' => [
                            'revenue' => 'R$ ' . number_format($item->revenue, 2, ',', '.'),
                            'cost' => 'R$ ' . number_format($item->cost, 2, ',', '.'),
                            'profit' => 'R$ ' . number_format($item->profit, 2, ',', '.')
                        ]
                    ];
                })
            ]
        ]);
    }

    private function getDateRange($period): array
    {
        switch ($period) {
            case 'day':
                return [Carbon::today(), Carbon::today()->endOfDay()];
            case 'week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'year':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            default:
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
        }
    }
}
