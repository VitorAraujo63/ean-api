<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('pt_BR');
        $customers = Customer::all();
        $products = Product::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->error('Please run CustomerSeeder and ProductSeeder first!');
            return;
        }

        $statuses = ['pago', 'pendente', 'cancelado'];
        $paymentMethods = ['mastercard', 'visa', 'pix', 'boleto'];
        $statusWeights = [70, 20, 10]; // 70% paid, 20% pending, 10% cancelled
        $paymentWeights = [25, 25, 35, 15]; // Distribution of payment methods

        $startDate = Carbon::now()->subMonths(6);
        $endDate = Carbon::now();

        $totalSales = 0;
        $totalItems = 0;

        $this->command->info('Creating regular daily sales...');

        // Regular daily sales with multiple products
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $isWeekend = $date->isWeekend();
            $baseDaily = $isWeekend ? rand(2, 8) : rand(5, 15);

            $monthsAgo = $date->diffInMonths(Carbon::now());
            $growthMultiplier = 1 + (6 - $monthsAgo) * 0.1;

            $dailySales = (int)($baseDaily * $growthMultiplier);

            for ($i = 0; $i < $dailySales; $i++) {
                try {
                    $customer = $customers->random();

                    $statusIndex = $this->weightedRandom($statusWeights);
                    $status = $statuses[$statusIndex];

                    $paymentIndex = $this->weightedRandom($paymentWeights);
                    $paymentMethod = $paymentMethods[$paymentIndex];

                    $saleDateTime = $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59));

                    // Create sale
                    $sale = Sale::create([
                        'customer_id' => $customer->id,
                        'shipping' => 0, // Will be calculated later
                        'discount_total' => 0,
                        'tax_total' => 0,
                        'subtotal' => 0,
                        'total' => 0,
                        'status' => $status,
                        'payment_method' => $paymentMethod,
                        'sale_date' => $saleDateTime->toDateString(),
                        'notes' => $faker->optional(0.3)->sentence(),
                        'created_at' => $saleDateTime,
                        'updated_at' => $saleDateTime
                    ]);

                    // Determine number of products in this sale (1-5 products, weighted towards fewer)
                    $productCount = $this->weightedRandom([50, 30, 15, 4, 1]); // 50% chance of 1 product, 30% of 2, etc.
                    $productCount = max(1, $productCount); // Ensure at least 1 product

                    $selectedProducts = $products->random(min($productCount, $products->count()));
                    if (!is_iterable($selectedProducts)) {
                        $selectedProducts = collect([$selectedProducts]);
                    }

                    foreach ($selectedProducts as $product) {
                        $quantity = $faker->randomElement([1, 1, 1, 2, 2, 3]); // Most sales have qty 1
                        $basePrice = $product->price;

                        // Add some price variation (±20%)
                        $priceVariation = $faker->randomFloat(2, 0.8, 1.2);
                        $unitPrice = $basePrice * $priceVariation;

                        // Random discount (0-15% chance of discount)
                        $discount = $faker->optional(0.15)->randomFloat(2, 0, $unitPrice * 0.2) ?? 0;

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'discount' => $discount,
                            'notes' => $faker->optional(0.2)->sentence(3)
                        ]);

                        $totalItems++;
                    }

                    // Calculate shipping based on subtotal
                    $saleSubtotal = $sale->items->sum('total_price');
                    $shipping = 0;
                    if ($saleSubtotal < 100) {
                        $shipping = 15.90;
                    } elseif ($saleSubtotal < 500) {
                        $shipping = $faker->randomFloat(2, 0, 25.90);
                    }

                    // Apply sale-level discount occasionally (10% chance)
                    $saleDiscount = $faker->optional(0.1)->randomFloat(2, 0, $saleSubtotal * 0.1) ?? 0;

                    // Update sale with calculated values
                    $sale->update([
                        'shipping' => $shipping,
                        'discount_total' => $saleDiscount
                    ]);

                    // Calculate final totals
                    $sale->calculateTotals();
                    $totalSales++;

                } catch (\Exception $e) {
                    $this->command->error("Error creating sale: " . $e->getMessage());
                    continue;
                }
            }
        }

        // Create some high-value sales with expensive products
        $this->command->info('Creating high-value sales...');
        for ($i = 0; $i < 20; $i++) {
            try {
                $customer = $customers->random();
                $expensiveProducts = $products->where('price', '>', 500);

                if ($expensiveProducts->isEmpty()) {
                    $expensiveProducts = $products->sortByDesc('price')->take(5);
                }

                $saleDate = Carbon::instance($faker->dateTimeBetween('-3 months', 'now'));

                $sale = Sale::create([
                    'customer_id' => $customer->id,
                    'shipping' => 0, // Free shipping for high-value sales
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                    'status' => $faker->randomElement(['pago', 'pago', 'pago', 'pendente']), // Mostly paid
                    'payment_method' => $faker->randomElement(['mastercard', 'visa', 'pix']),
                    'sale_date' => $saleDate->toDateString(),
                    'notes' => 'Venda de alto valor',
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate
                ]);

                // Add 1-3 expensive products
                $selectedProducts = $expensiveProducts->random(rand(1, min(3, $expensiveProducts->count())));
                if (!is_iterable($selectedProducts)) {
                    $selectedProducts = collect([$selectedProducts]);
                }

                foreach ($selectedProducts as $product) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => $product->price * $faker->randomFloat(2, 0.9, 1.1),
                        'discount' => 0,
                        'notes' => 'Produto premium'
                    ]);
                    $totalItems++;
                }

                $sale->calculateTotals();
                $totalSales++;

            } catch (\Exception $e) {
                $this->command->error("Error creating high-value sale: " . $e->getMessage());
                continue;
            }
        }

        // Create bulk sales (same customer buying multiple quantities)
        $this->command->info('Creating bulk sales...');
        for ($i = 0; $i < 10; $i++) {
            try {
                $customer = $customers->random();
                $saleDate = Carbon::instance($faker->dateTimeBetween('-2 months', 'now'));

                $sale = Sale::create([
                    'customer_id' => $customer->id,
                    'shipping' => 25.90,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                    'status' => 'pago',
                    'payment_method' => 'pix',
                    'sale_date' => $saleDate->toDateString(),
                    'notes' => 'Compra em quantidade - desconto aplicado',
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate
                ]);

                // Select 2-4 products for bulk purchase
                $selectedProducts = $products->random(rand(2, 4));
                if (!is_iterable($selectedProducts)) {
                    $selectedProducts = collect([$selectedProducts]);
                }

                foreach ($selectedProducts as $product) {
                    $quantity = rand(5, 20); // Bulk quantity
                    $unitPrice = $product->price * 0.85; // 15% bulk discount

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount' => $product->price * 0.15, // Show the discount amount
                        'notes' => "Desconto por quantidade (${quantity} unidades)"
                    ]);
                    $totalItems++;
                }

                $sale->calculateTotals();
                $totalSales++;

            } catch (\Exception $e) {
                $this->command->error("Error creating bulk sale: " . $e->getMessage());
                continue;
            }
        }

        // Create some mixed cart sales (many different products, small quantities)
        $this->command->info('Creating mixed cart sales...');
        for ($i = 0; $i < 15; $i++) {
            try {
                $customer = $customers->random();
                $saleDate = Carbon::instance($faker->dateTimeBetween('-1 month', 'now'));

                $sale = Sale::create([
                    'customer_id' => $customer->id,
                    'shipping' => 12.90,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                    'status' => $faker->randomElement(['pago', 'pendente']),
                    'payment_method' => $faker->randomElement($paymentMethods),
                    'sale_date' => $saleDate->toDateString(),
                    'notes' => 'Carrinho misto com vários produtos',
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate
                ]);

                // Select 5-10 different products
                $selectedProducts = $products->random(rand(5, min(10, $products->count())));
                if (!is_iterable($selectedProducts)) {
                    $selectedProducts = collect([$selectedProducts]);
                }

                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 3); // Small quantities

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'discount' => 0,
                        'notes' => null
                    ]);
                    $totalItems++;
                }

                $sale->calculateTotals();
                $totalSales++;

            } catch (\Exception $e) {
                $this->command->error("Error creating mixed cart sale: " . $e->getMessage());
                continue;
            }
        }

        $this->command->info("$totalSales sales with $totalItems items seeded successfully!");
        $this->command->info('Sales distribution:');

        // Get statistics
        $paidSales = Sale::where('status', 'pago')->count();
        $pendingSales = Sale::where('status', 'pendente')->count();
        $cancelledSales = Sale::where('status', 'cancelado')->count();

        $totalRevenue = Sale::where('status', 'pago')->sum('total');
        $avgItemsPerSale = $totalSales > 0 ? round($totalItems / $totalSales, 1) : 0;
        $avgSaleValue = Sale::where('status', 'pago')->avg('total') ?? 0;

        $this->command->info("- Paid: $paidSales");
        $this->command->info("- Pending: $pendingSales");
        $this->command->info("- Cancelled: $cancelledSales");
        $this->command->info("- Total Revenue: R$ " . number_format($totalRevenue, 2, ',', '.'));
        $this->command->info("- Average items per sale: $avgItemsPerSale");
        $this->command->info("- Average sale value: R$ " . number_format($avgSaleValue, 2, ',', '.'));

        // Show some sample sales
        $this->command->info("\nSample sales created:");
        $sampleSales = Sale::with(['customer', 'items.product'])
            ->latest()
            ->take(3)
            ->get();

        foreach ($sampleSales as $sale) {
            $itemsCount = $sale->items->count();
            $totalQuantity = $sale->items->sum('quantity');
            $this->command->info("- Sale #{$sale->sale_number}: {$sale->customer->name} - {$itemsCount} products, {$totalQuantity} items - R$ " . number_format($sale->total, 2, ',', '.'));
        }
    }

    private function weightedRandom($weights)
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($weights as $index => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $index;
            }
        }

        return 0;
    }
}
