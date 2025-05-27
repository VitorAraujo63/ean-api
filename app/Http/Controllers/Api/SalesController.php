<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with('customer');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sale_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $sales = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sales->items(),
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'from' => $sales->firstItem(),
                'to' => $sales->lastItem(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'price' => 'required|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'status' => 'required|in:pago,pendente,cancelado',
            'payment_method' => 'required|in:mastercard,visa,pix,boleto',
            'sale_date' => 'required|date'
        ]);

        $sale = Sale::create($validated);
        $sale->load('customer');

        return response()->json([
            'success' => true,
            'message' => 'Venda criada com sucesso',
            'data' => $sale
        ], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load('customer');

        return response()->json([
            'success' => true,
            'data' => $sale
        ]);
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'price' => 'sometimes|numeric|min:0',
            'shipping' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pago,pendente,cancelado',
            'payment_method' => 'sometimes|in:mastercard,visa,pix,boleto',
            'sale_date' => 'sometimes|date'
        ]);

        $sale->update($validated);
        $sale->load('customer');

        return response()->json([
            'success' => true,
            'message' => 'Venda atualizada com sucesso',
            'data' => $sale
        ]);
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $sale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Venda excluída com sucesso'
        ]);
    }

    public function metrics(): JsonResponse
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $lastMonth = Carbon::now()->subMonth()->month;
        $lastMonthYear = Carbon::now()->subMonth()->year;

        // Current month metrics
        $currentMetrics = Sale::whereMonth('sale_date', $currentMonth)
            ->whereYear('sale_date', $currentYear)
            ->selectRaw('
                SUM(price + shipping) as total_revenue,
                COUNT(*) as total_sales,
                COUNT(DISTINCT customer_id) as total_customers
            ')
            ->first();

        // Last month metrics for comparison
        $lastMetrics = Sale::whereMonth('sale_date', $lastMonth)
            ->whereYear('sale_date', $lastMonthYear)
            ->selectRaw('
                SUM(price + shipping) as total_revenue,
                COUNT(*) as total_sales,
                COUNT(DISTINCT customer_id) as total_customers
            ')
            ->first();

        // Calculate percentage changes
        $revenueChange = $this->calculatePercentageChange(
            $lastMetrics->total_revenue ?? 0,
            $currentMetrics->total_revenue ?? 0
        );

        $salesChange = $this->calculatePercentageChange(
            $lastMetrics->total_sales ?? 0,
            $currentMetrics->total_sales ?? 0
        );

        $customersChange = $this->calculatePercentageChange(
            $lastMetrics->total_customers ?? 0,
            $currentMetrics->total_customers ?? 0
        );

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => [
                    'value' => number_format($currentMetrics->total_revenue ?? 0, 2, ',', '.'),
                    'change' => $revenueChange
                ],
                'total_sales' => [
                    'value' => $currentMetrics->total_sales ?? 0,
                    'change' => $salesChange
                ],
                'total_customers' => [
                    'value' => $currentMetrics->total_customers ?? 0,
                    'change' => $customersChange
                ]
            ]
        ]);
    }

    private function calculatePercentageChange($oldValue, $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }

    public function export(Request $request): JsonResponse
    {
        $query = Sale::with('customer');

        // Apply same filters as index method
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        $exportData = $sales->map(function ($sale) {
            return [
                'ID' => $sale->id,
                'Data' => $sale->sale_date->format('d/m/Y'),
                'Cliente' => $sale->customer->name,
                'Preço' => 'R$ ' . number_format($sale->price, 2, ',', '.'),
                'Frete' => 'R$ ' . number_format($sale->shipping, 2, ',', '.'),
                'Total' => 'R$ ' . number_format($sale->total, 2, ',', '.'),
                'Status' => ucfirst($sale->status),
                'Pagamento' => ucfirst($sale->payment_method),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'filename' => 'vendas_' . date('Y-m-d_H-i-s') . '.csv'
        ]);
    }
}
