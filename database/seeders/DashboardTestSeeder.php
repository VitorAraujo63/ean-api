<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use Carbon\Carbon;

class DashboardTestSeeder extends Seeder
{
    /**
     * Create specific data to showcase dashboard features
     */
    public function run()
    {
        // Get categories for specific dashboard examples
        $boneCategory = Category::where('name', 'Boné')->first();
        $tenisCategory = Category::where('name', 'Tênis')->first();

        if (!$boneCategory || !$tenisCategory) {
            $this->command->error('Please run CategorySeeder first!');
            return;
        }

        // Get products from these categories
        $boneProducts = Product::where('category_id', $boneCategory->id)->get();
        $tenisProducts = Product::where('category_id', $tenisCategory->id)->get();

        if ($boneProducts->isEmpty() || $tenisProducts->isEmpty()) {
            $this->command->error('Please run ProductSeeder first!');
            return;
        }

        $customers = Customer::all();
        if ($customers->isEmpty()) {
            $this->command->error('Please run CustomerSeeder first!');
            return;
        }

        // Create specific sales to match dashboard values
        // Bonés category - targeting $1,200.50 revenue
        $boneTargetRevenue = 1200.50;
        $currentBoneRevenue = 0;
        $boneSalesCount = 0;

        while ($currentBoneRevenue < $boneTargetRevenue && $boneSalesCount < 50) {
            $product = $boneProducts->random();
            $customer = $customers->random();

            $remainingRevenue = $boneTargetRevenue - $currentBoneRevenue;
            $maxPrice = min($product->price * 1.2, $remainingRevenue);
            $price = max($product->price * 0.8, min($maxPrice, $product->price));

            $sale = Sale::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'price' => $price,
                'shipping' => rand(0, 1) ? 0 : 15.90,
                'status' => 'pago',
                'payment_method' => ['mastercard', 'visa', 'pix'][rand(0, 2)],
                'sale_date' => Carbon::now()->subDays(rand(0, 30))->toDateString(),
                'created_at' => Carbon::now()->subDays(rand(0, 30)),
                'updated_at' => Carbon::now()
            ]);

            $currentBoneRevenue += ($sale->price + $sale->shipping);
            $boneSalesCount++;
        }

        // Create daily sales data for Bonés chart (last 7 days)
        $dailyTargets = [552, 618, 721, 589, 634, 756, 689]; // Sample chart values

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $targetRevenue = $dailyTargets[6 - $i];
            $currentDayRevenue = 0;
            $attempts = 0;

            while ($currentDayRevenue < $targetRevenue && $attempts < 20) {
                $product = $boneProducts->random();
                $customer = $customers->random();

                $remainingRevenue = $targetRevenue - $currentDayRevenue;
                $price = min($product->price, $remainingRevenue);

                if ($price > 0) {
                    Sale::create([
                        'customer_id' => $customer->id,
                        'product_id' => $product->id,
                        'price' => $price,
                        'shipping' => rand(0, 1) ? 0 : 15.90,
                        'status' => 'pago',
                        'payment_method' => ['mastercard', 'visa', 'pix'][rand(0, 2)],
                        'sale_date' => $date->toDateString(),
                        'created_at' => $date,
                        'updated_at' => $date
                    ]);

                    $currentDayRevenue += $price;
                }
                $attempts++;
            }
        }

        // Create Tênis sales for the other dashboard section
        // SEG (Segunda) - 10k target, TER (Terça) - 10m target
        $monday = Carbon::now()->startOfWeek();
        $tuesday = Carbon::now()->startOfWeek()->addDay();

        // Monday sales (SEG) - 10k
        $mondayTarget = 10000;
        $currentMondayRevenue = 0;

        while ($currentMondayRevenue < $mondayTarget) {
            $product = $tenisProducts->random();
            $customer = $customers->random();

            $remainingRevenue = $mondayTarget - $currentMondayRevenue;
            $price = min($product->price, $remainingRevenue);

            if ($price > 0) {
                Sale::create([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'price' => $price,
                    'shipping' => 0,
                    'status' => 'pago',
                    'payment_method' => ['mastercard', 'visa', 'pix'][rand(0, 2)],
                    'sale_date' => $monday->toDateString(),
                    'created_at' => $monday,
                    'updated_at' => $monday
                ]);

                $currentMondayRevenue += $price;
            } else {
                break;
            }
        }

        // Tuesday sales (TER) - 10m (10,000,000)
        $tuesdayTarget = 10000000;
        $currentTuesdayRevenue = 0;

        while ($currentTuesdayRevenue < $tuesdayTarget) {
            $product = $tenisProducts->random();
            $customer = $customers->random();

            $remainingRevenue = $tuesdayTarget - $currentTuesdayRevenue;
            $price = min($product->price * 10, $remainingRevenue); // Higher prices for bulk

            if ($price > 0) {
                Sale::create([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'price' => $price,
                    'shipping' => 0,
                    'status' => 'pago',
                    'payment_method' => 'pix',
                    'sale_date' => $tuesday->toDateString(),
                    'created_at' => $tuesday,
                    'updated_at' => $tuesday
                ]);

                $currentTuesdayRevenue += $price;
            } else {
                break;
            }
        }

        $this->command->info('Dashboard test data seeded successfully!');
        $this->command->info("Bonés revenue: R$ " . number_format($currentBoneRevenue, 2, ',', '.'));
        $this->command->info("Monday (SEG) revenue: R$ " . number_format($currentMondayRevenue, 2, ',', '.'));
        $this->command->info("Tuesday (TER) revenue: R$ " . number_format($currentTuesdayRevenue, 2, ',', '.'));
    }
}
