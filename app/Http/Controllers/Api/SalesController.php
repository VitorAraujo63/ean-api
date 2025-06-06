<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use App\Http\Resources\SaleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['customer', 'items.product']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('sale_number', 'like', "%{$search}%");
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
            'data' => SaleResource::collection($sales->items()),
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
            'shipping' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'status' => 'required|in:pago,pendente,cancelado',
            'payment_method' => 'required|in:mastercard,visa,pix,boleto',
            'sale_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Create sale
            $sale = Sale::create([
                'customer_id' => $validated['customer_id'],
                'shipping' => $validated['shipping'] ?? 0,
                'discount_total' => $validated['discount_total'] ?? 0,
                'tax_total' => $validated['tax_total'] ?? 0,
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'],
                'sale_date' => $validated['sale_date'],
                'notes' => $validated['notes'] ?? null
            ]);

            // Create sale items
            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);

                // Use product price if unit_price not provided
                $unitPrice = $itemData['unit_price'] ?? $product->price ?? 0;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'discount' => $itemData['discount'] ?? 0,
                    'notes' => $itemData['notes'] ?? null
                ]);
            }

            // Calculate totals
            $sale->calculateTotals();

            // Load relationships for response
            $sale->load(['customer', 'items.product']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venda criada com sucesso',
                'data' => new SaleResource($sale)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating sale: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar venda: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['customer', 'items.product']);

        return response()->json([
            'success' => true,
            'data' => new SaleResource($sale)
        ]);
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'shipping' => 'sometimes|numeric|min:0',
            'discount_total' => 'sometimes|numeric|min:0',
            'tax_total' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pago,pendente,cancelado',
            'payment_method' => 'sometimes|in:mastercard,visa,pix,boleto',
            'sale_date' => 'sometimes|date',
            'notes' => 'sometimes|nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'sometimes|exists:sale_items,id',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'sometimes|numeric|min:0',
            'items.*.discount' => 'sometimes|numeric|min:0',
            'items.*.notes' => 'sometimes|nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Update sale basic info
            $sale->update(collect($validated)->except('items')->toArray());

            // Update items if provided
            if (isset($validated['items'])) {
                // Delete existing items
                $sale->items()->delete();

                // Create new items
                foreach ($validated['items'] as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    $unitPrice = $itemData['unit_price'] ?? $product->price ?? 0;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $unitPrice,
                        'discount' => $itemData['discount'] ?? 0,
                        'notes' => $itemData['notes'] ?? null
                    ]);
                }

                // Recalculate totals
                $sale->calculateTotals();
            }

            $sale->load(['customer', 'items.product']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venda atualizada com sucesso',
                'data' => new SaleResource($sale)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating sale: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar venda: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Sale $sale): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete items first (cascade should handle this, but being explicit)
            $sale->items()->delete();

            // Delete sale
            $sale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venda excluída com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting sale: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir venda: ' . $e->getMessage()
            ], 500);
        }
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
                SUM(total) as total_revenue,
                COUNT(*) as total_sales,
                COUNT(DISTINCT customer_id) as total_customers,
                SUM(subtotal) as subtotal_sum,
                SUM(shipping) as shipping_sum
            ')
            ->first();

        // Last month metrics for comparison
        $lastMetrics = Sale::whereMonth('sale_date', $lastMonth)
            ->whereYear('sale_date', $lastMonthYear)
            ->selectRaw('
                SUM(total) as total_revenue,
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
                ],
                'breakdown' => [
                    'subtotal' => number_format($currentMetrics->subtotal_sum ?? 0, 2, ',', '.'),
                    'shipping' => number_format($currentMetrics->shipping_sum ?? 0, 2, ',', '.')
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
        $query = Sale::with(['customer', 'items.product']);

        // Apply same filters as index method
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('sale_number', 'like', "%{$search}%");
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        $exportData = $sales->map(function ($sale) {
            return [
                'Número da Venda' => $sale->sale_number,
                'Data' => $sale->sale_date->format('d/m/Y'),
                'Cliente' => $sale->customer->name,
                'Qtd Produtos' => $sale->products_count,
                'Qtd Itens' => $sale->items_count,
                'Subtotal' => 'R$ ' . number_format($sale->subtotal, 2, ',', '.'),
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
