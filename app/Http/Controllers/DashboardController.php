<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Operações relacionadas ao dashboard e métricas do negócio."
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *     summary="Obter dados completos do dashboard",
     *     @OA\Response(response=200, description="Dados do dashboard"),
     *     @OA\Response(response=500, description="Erro interno no servidor.")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month');

            // Check if we have data first
            $hasData = $this->checkDataAvailability();
            if (!$hasData['has_data']) {
                return $this->successResponse([
                    'message' => 'Nenhum dado encontrado. Execute os seeders primeiro.',
                    'suggestions' => [
                        'Run: php artisan db:seed --class=CompleteSeeder',
                        'Or: php artisan migrate:fresh --seed'
                    ],
                    'counts' => $hasData['counts']
                ]);
            }

            $dashboardData = [
                'overview' => $this->getOverviewMetrics($period),
                'categories_performance' => $this->getCategoriesPerformance($period),
                'sales_analytics' => $this->getSalesAnalytics($period),
                'profit_analysis' => $this->getProfitAnalysis($period),
                'charts_data' => $this->getChartsData($period),
                'recent_activities' => $this->getRecentActivities(),
                'top_products' => $this->getTopProducts($period),
                'customer_insights' => $this->getCustomerInsights($period)
            ];

            return $this->successResponse($dashboardData, 'Dados do dashboard carregados com sucesso.');

        } catch (\Exception $e) {
            Log::error('Dashboard Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Erro ao carregar dados do dashboard: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if we have the necessary data and relationships
     */
    private function checkDataAvailability(): array
    {
        $counts = [
            'categories' => Category::count(),
            'products' => Product::count(),
            'customers' => Customer::count(),
            'sales' => Sale::count()
        ];

        // Check if sales table has product_id column
        $hasProductIdColumn = DB::getSchemaBuilder()->hasColumn('sales', 'product_id');

        return [
            'has_data' => $counts['sales'] > 0 && $counts['customers'] > 0,
            'has_product_relation' => $hasProductIdColumn,
            'counts' => $counts
        ];
    }

    /**
     * Get overview metrics (main KPIs)
     */
    private function getOverviewMetrics($period): array
    {
        try {
            $dateRange = $this->getDateRange($period);
            $previousDateRange = $this->getPreviousDateRange($period);

            // Current period metrics
            $currentSales = Sale::whereBetween('sale_date', $dateRange)
                ->selectRaw('
                    COUNT(*) as total_sales,
                    COALESCE(SUM(price + shipping), 0) as total_revenue,
                    COALESCE(AVG(price + shipping), 0) as avg_order_value,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->first();

            // Previous period for comparison
            $previousSales = Sale::whereBetween('sale_date', $previousDateRange)
                ->selectRaw('
                    COUNT(*) as total_sales,
                    COALESCE(SUM(price + shipping), 0) as total_revenue,
                    COALESCE(AVG(price + shipping), 0) as avg_order_value,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->first();

            return [
                'total_revenue' => [
                    'value' => $currentSales->total_revenue ?? 0,
                    'formatted' => 'R$ ' . number_format($currentSales->total_revenue ?? 0, 2, ',', '.'),
                    'change' => $this->calculatePercentageChange(
                        $previousSales->total_revenue ?? 0,
                        $currentSales->total_revenue ?? 0
                    )
                ],
                'total_sales' => [
                    'value' => $currentSales->total_sales ?? 0,
                    'change' => $this->calculatePercentageChange(
                        $previousSales->total_sales ?? 0,
                        $currentSales->total_sales ?? 0
                    )
                ],
                'unique_customers' => [
                    'value' => $currentSales->unique_customers ?? 0,
                    'change' => $this->calculatePercentageChange(
                        $previousSales->unique_customers ?? 0,
                        $currentSales->unique_customers ?? 0
                    )
                ],
                'avg_order_value' => [
                    'value' => $currentSales->avg_order_value ?? 0,
                    'formatted' => 'R$ ' . number_format($currentSales->avg_order_value ?? 0, 2, ',', '.'),
                    'change' => $this->calculatePercentageChange(
                        $previousSales->avg_order_value ?? 0,
                        $currentSales->avg_order_value ?? 0
                    )
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in getOverviewMetrics: ' . $e->getMessage());
            return $this->getEmptyOverviewMetrics();
        }
    }

    /**
     * Get categories performance (simplified version)
     */
    private function getCategoriesPerformance($period): array
    {
        try {
            $dateRange = $this->getDateRange($period);

            // Check if we have product_id in sales table
            $hasProductId = DB::getSchemaBuilder()->hasColumn('sales', 'product_id');

            if (!$hasProductId) {
                // Fallback: just return category data without sales
                return Category::withCount('products')->get()->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'revenue' => 0,
                        'formatted_revenue' => 'R$ 0,00',
                        'sales_count' => 0,
                        'avg_value' => 0,
                        'products_count' => $category->products_count,
                        'daily_chart' => []
                    ];
                })->toArray();
            }

            $categoriesData = Category::withCount('products')->get()->map(function ($category) use ($dateRange) {
                // Get sales data for this category
                $salesData = DB::table('sales')
                    ->join('products', 'sales.product_id', '=', 'products.id')
                    ->where('products.category_id', $category->id)
                    ->whereBetween('sales.sale_date', $dateRange)
                    ->selectRaw('
                        COALESCE(SUM(sales.price + sales.shipping), 0) as revenue,
                        COUNT(*) as sales_count,
                        COALESCE(AVG(sales.price + sales.shipping), 0) as avg_value
                    ')
                    ->first();

                // Get daily sales for chart
                $dailySales = DB::table('sales')
                    ->join('products', 'sales.product_id', '=', 'products.id')
                    ->where('products.category_id', $category->id)
                    ->whereBetween('sales.sale_date', $dateRange)
                    ->selectRaw('DATE(sales.sale_date) as date, COALESCE(SUM(sales.price + sales.shipping), 0) as daily_revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'revenue' => $salesData->revenue ?? 0,
                    'formatted_revenue' => 'R$ ' . number_format($salesData->revenue ?? 0, 2, ',', '.'),
                    'sales_count' => $salesData->sales_count ?? 0,
                    'avg_value' => $salesData->avg_value ?? 0,
                    'products_count' => $category->products_count,
                    'daily_chart' => $dailySales->map(function($day) {
                        return [
                            'date' => Carbon::parse($day->date)->format('d/m'),
                            'value' => $day->daily_revenue
                        ];
                    })
                ];
            });

            return $categoriesData->toArray();
        } catch (\Exception $e) {
            Log::error('Error in getCategoriesPerformance: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get sales analytics with status breakdown
     */
    private function getSalesAnalytics($period): array
    {
        try {
            $dateRange = $this->getDateRange($period);

            $totalSalesInPeriod = Sale::whereBetween('sale_date', $dateRange)->count();

            if ($totalSalesInPeriod == 0) {
                return [
                    'status_breakdown' => [],
                    'payment_methods' => []
                ];
            }

            $statusBreakdown = Sale::whereBetween('sale_date', $dateRange)
                ->selectRaw('
                    status,
                    COUNT(*) as count,
                    COALESCE(SUM(price + shipping), 0) as revenue
                ')
                ->groupBy('status')
                ->get();

            $paymentMethodBreakdown = Sale::whereBetween('sale_date', $dateRange)
                ->selectRaw('
                    payment_method,
                    COUNT(*) as count,
                    COALESCE(SUM(price + shipping), 0) as revenue
                ')
                ->groupBy('payment_method')
                ->get();

            return [
                'status_breakdown' => $statusBreakdown->map(function($item) use ($totalSalesInPeriod) {
                    $percentage = $totalSalesInPeriod > 0 ? ($item->count / $totalSalesInPeriod) * 100 : 0;
                    return [
                        'status' => $item->status,
                        'count' => $item->count,
                        'revenue' => $item->revenue,
                        'percentage' => round($percentage, 1),
                        'formatted_revenue' => 'R$ ' . number_format($item->revenue, 2, ',', '.')
                    ];
                }),
                'payment_methods' => $paymentMethodBreakdown->map(function($item) {
                    return [
                        'method' => $item->payment_method,
                        'count' => $item->count,
                        'revenue' => $item->revenue,
                        'formatted_revenue' => 'R$ ' . number_format($item->revenue, 2, ',', '.')
                    ];
                })
            ];
        } catch (\Exception $e) {
            Log::error('Error in getSalesAnalytics: ' . $e->getMessage());
            return [
                'status_breakdown' => [],
                'payment_methods' => []
            ];
        }
    }

    /**
     * Get profit analysis (simplified)
     */
    private function getProfitAnalysis($period): array
    {
        try {
            $dateRange = $this->getDateRange($period);

            // Check if we have the necessary relationships
            $hasProductId = DB::getSchemaBuilder()->hasColumn('sales', 'product_id');
            $hasCostColumn = DB::getSchemaBuilder()->hasColumn('products', 'cost');

            if (!$hasProductId || !$hasCostColumn) {
                // Fallback: basic revenue analysis
                $revenue = Sale::whereBetween('sale_date', $dateRange)
                    ->sum(DB::raw('price + shipping'));

                return [
                    'total_revenue' => $revenue,
                    'total_cost' => 0,
                    'total_profit' => $revenue,
                    'profit_margin' => 100,
                    'formatted' => [
                        'revenue' => 'R$ ' . number_format($revenue, 2, ',', '.'),
                        'cost' => 'R$ 0,00',
                        'profit' => 'R$ ' . number_format($revenue, 2, ',', '.')
                    ]
                ];
            }

            $profitData = DB::table('sales')
                ->join('products', 'sales.product_id', '=', 'products.id')
                ->whereBetween('sales.sale_date', $dateRange)
                ->selectRaw('
                    COALESCE(SUM(sales.price + sales.shipping), 0) as total_revenue,
                    COALESCE(SUM(products.cost), 0) as total_cost,
                    COALESCE(SUM((sales.price + sales.shipping) - COALESCE(products.cost, 0)), 0) as total_profit
                ')
                ->first();

            $totalRevenue = $profitData->total_revenue ?? 0;
            $totalCost = $profitData->total_cost ?? 0;
            $totalProfit = $profitData->total_profit ?? 0;

            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

            return [
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'profit_margin' => round($profitMargin, 1),
                'formatted' => [
                    'revenue' => 'R$ ' . number_format($totalRevenue, 2, ',', '.'),
                    'cost' => 'R$ ' . number_format($totalCost, 2, ',', '.'),
                    'profit' => 'R$ ' . number_format($totalProfit, 2, ',', '.')
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in getProfitAnalysis: ' . $e->getMessage());
            return [
                'total_revenue' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'profit_margin' => 0,
                'formatted' => [
                    'revenue' => 'R$ 0,00',
                    'cost' => 'R$ 0,00',
                    'profit' => 'R$ 0,00'
                ]
            ];
        }
    }

    /**
     * Get charts data for various visualizations
     */
    private function getChartsData($period): array
    {
        try {
            $dateRange = $this->getDateRange($period);

            // Daily sales chart
            $dailySales = Sale::whereBetween('sale_date', $dateRange)
                ->selectRaw('DATE(sale_date) as date, COALESCE(SUM(price + shipping), 0) as revenue, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Weekly comparison
            $weeklyData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dayData = Sale::whereDate('sale_date', $date)
                    ->selectRaw('COALESCE(SUM(price + shipping), 0) as revenue, COUNT(*) as count')
                    ->first();

                $weeklyData[] = [
                    'day' => $date->format('D'),
                    'date' => $date->format('d/m'),
                    'revenue' => $dayData->revenue ?? 0,
                    'count' => $dayData->count ?? 0
                ];
            }

            return [
                'daily_sales' => $dailySales->map(function($day) {
                    return [
                        'date' => Carbon::parse($day->date)->format('d/m'),
                        'revenue' => $day->revenue,
                        'count' => $day->count
                    ];
                }),
                'weekly_comparison' => $weeklyData
            ];
        } catch (\Exception $e) {
            Log::error('Error in getChartsData: ' . $e->getMessage());
            return [
                'daily_sales' => [],
                'weekly_comparison' => []
            ];
        }
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        try {
            $recentSales = Sale::with('customer')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $recentProducts = Product::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return [
                'recent_sales' => $recentSales->map(function($sale) {
                    return [
                        'id' => $sale->id,
                        'customer' => $sale->customer ? $sale->customer->name : 'Cliente não encontrado',
                        'amount' => $sale->price + $sale->shipping,
                        'formatted_amount' => 'R$ ' . number_format($sale->price + $sale->shipping, 2, ',', '.'),
                        'status' => $sale->status,
                        'date' => $sale->created_at->format('d/m/Y H:i')
                    ];
                }),
                'recent_products' => $recentProducts->map(function($product) {
                    return [
                        'id' => $product->id,
                        'description' => $product->description,
                        'brand' => $product->brand,
                        'date' => $product->created_at->format('d/m/Y H:i')
                    ];
                })
            ];
        } catch (\Exception $e) {
            Log::error('Error in getRecentActivities: ' . $e->getMessage());
            return [
                'recent_sales' => [],
                'recent_products' => []
            ];
        }
    }

    /**
     * Get top performing products (simplified)
     */
    private function getTopProducts($period): array
    {
        try {
            $dateRange = $this->getDateRange($period);

            // Check if we have product_id in sales
            $hasProductId = DB::getSchemaBuilder()->hasColumn('sales', 'product_id');

            if (!$hasProductId) {
                return [];
            }

            return DB::table('products')
                ->join('sales', 'products.id', '=', 'sales.product_id')
                ->whereBetween('sales.sale_date', $dateRange)
                ->selectRaw('
                    products.id,
                    products.description,
                    products.brand,
                    COALESCE(SUM(sales.price + sales.shipping), 0) as revenue,
                    COUNT(sales.id) as sales_count
                ')
                ->groupBy('products.id', 'products.description', 'products.brand')
                ->orderBy('revenue', 'desc')
                ->limit(10)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'description' => $product->description,
                        'brand' => $product->brand,
                        'revenue' => $product->revenue,
                        'formatted_revenue' => 'R$ ' . number_format($product->revenue, 2, ',', '.'),
                        'sales_count' => $product->sales_count
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error in getTopProducts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer insights
     */
    private function getCustomerInsights($period): array
    {
        try {
            $dateRange = $this->getDateRange($period);

            $customerStats = Customer::withCount(['sales' => function($query) use ($dateRange) {
                    $query->whereBetween('sale_date', $dateRange);
                }])
                ->with(['sales' => function($query) use ($dateRange) {
                    $query->whereBetween('sale_date', $dateRange);
                }])
                ->get()
                ->map(function($customer) {
                    $totalSpent = $customer->sales->sum(function($sale) {
                        return $sale->price + $sale->shipping;
                    });

                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'sales_count' => $customer->sales_count,
                        'total_spent' => $totalSpent,
                        'formatted_total' => 'R$ ' . number_format($totalSpent, 2, ',', '.'),
                        'avg_order' => $customer->sales_count > 0 ? $totalSpent / $customer->sales_count : 0
                    ];
                })
                ->sortByDesc('total_spent')
                ->take(10);

            return [
                'top_customers' => $customerStats->values()->toArray(),
                'total_customers' => Customer::count(),
                'new_customers_period' => Customer::whereBetween('created_at', $dateRange)->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error in getCustomerInsights: ' . $e->getMessage());
            return [
                'top_customers' => [],
                'total_customers' => 0,
                'new_customers_period' => 0
            ];
        }
    }

    /**
     * Get quick stats for widgets
     */
    public function quickStats(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            $todayStats = Sale::whereDate('sale_date', $today)
                ->selectRaw('COUNT(*) as sales, COALESCE(SUM(price + shipping), 0) as revenue')
                ->first();

            $yesterdayStats = Sale::whereDate('sale_date', $yesterday)
                ->selectRaw('COUNT(*) as sales, COALESCE(SUM(price + shipping), 0) as revenue')
                ->first();

            return $this->successResponse([
                'today' => [
                    'sales' => $todayStats->sales ?? 0,
                    'revenue' => $todayStats->revenue ?? 0,
                    'formatted_revenue' => 'R$ ' . number_format($todayStats->revenue ?? 0, 2, ',', '.')
                ],
                'yesterday' => [
                    'sales' => $yesterdayStats->sales ?? 0,
                    'revenue' => $yesterdayStats->revenue ?? 0,
                    'formatted_revenue' => 'R$ ' . number_format($yesterdayStats->revenue ?? 0, 2, ',', '.')
                ],
                'changes' => [
                    'sales' => $this->calculatePercentageChange($yesterdayStats->sales ?? 0, $todayStats->sales ?? 0),
                    'revenue' => $this->calculatePercentageChange($yesterdayStats->revenue ?? 0, $todayStats->revenue ?? 0)
                ],
                'totals' => [
                    'products' => Product::count(),
                    'categories' => Category::count(),
                    'customers' => Customer::count(),
                    'total_sales' => Sale::count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in quickStats: ' . $e->getMessage());
            return $this->errorResponse('Erro ao carregar estatísticas rápidas: ' . $e->getMessage(), 500);
        }
    }

    // Helper methods
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

    private function getPreviousDateRange($period): array
    {
        switch ($period) {
            case 'day':
                return [Carbon::yesterday(), Carbon::yesterday()->endOfDay()];
            case 'week':
                return [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()];
            case 'month':
                return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            case 'year':
                return [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()];
            default:
                return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
        }
    }

    private function calculatePercentageChange($oldValue, $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }

    private function getEmptyOverviewMetrics(): array
    {
        return [
            'total_revenue' => ['value' => 0, 'formatted' => 'R$ 0,00', 'change' => 0],
            'total_sales' => ['value' => 0, 'change' => 0],
            'unique_customers' => ['value' => 0, 'change' => 0],
            'avg_order_value' => ['value' => 0, 'formatted' => 'R$ 0,00', 'change' => 0]
        ];
    }

    private function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    private function errorResponse($message = 'Erro inesperado.', $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $code);
    }
}
