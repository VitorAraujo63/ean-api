<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Faker\Factory as Faker;

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

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $isWeekend = $date->isWeekend();
            $baseDaily = $isWeekend ? rand(2, 8) : rand(5, 15);

            $monthsAgo = $date->diffInMonths(Carbon::now());
            $growthMultiplier = 1 + (6 - $monthsAgo) * 0.1;

            $dailySales = (int)($baseDaily * $growthMultiplier);

            for ($i = 0; $i < $dailySales; $i++) {
                $customer = $customers->random();
                $product = $products->random();

                $statusIndex = $this->weightedRandom($statusWeights);
                $status = $statuses[$statusIndex];

                $paymentIndex = $this->weightedRandom($paymentWeights);
                $paymentMethod = $paymentMethods[$paymentIndex];

                $basePrice = $product->price;
                $priceVariation = $faker->randomFloat(2, 0.8, 1.2);
                $finalPrice = $basePrice * $priceVariation;

                $shipping = 0;
                if ($finalPrice < 100) {
                    $shipping = 15.90;
                } elseif ($finalPrice < 500) {
                    $shipping = $faker->randomFloat(2, 0, 25.90);
                }

                $saleDateTime = $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59));

                Sale::create([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'price' => $finalPrice,
                    'shipping' => $shipping,
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'sale_date' => $saleDateTime->toDateString(),
                    'created_at' => $saleDateTime,
                    'updated_at' => $saleDateTime
                ]);

                $totalSales++;
            }
        }

        for ($i = 0; $i < 20; $i++) {
            $customer = $customers->random();
            $expensiveProducts = $products->where('price', '>', 500);
            $product = $expensiveProducts->isNotEmpty() ? $expensiveProducts->random() : $products->random();

            $saleDate = Carbon::instance($faker->dateTimeBetween('-3 months', 'now'));

            Sale::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'price' => $product->price * $faker->randomFloat(2, 0.9, 1.1),
                'shipping' => 0,
                'status' => $faker->randomElement(['pago', 'pago', 'pago', 'pendente']),
                'payment_method' => $faker->randomElement(['mastercard', 'visa', 'pix']),
                'sale_date' => $saleDate->toDateString(),
                'created_at' => $saleDate,
                'updated_at' => $saleDate
            ]);

            $totalSales++;
        }

        for ($i = 0; $i < 10; $i++) {
            $customer = $customers->random();
            $product = $products->random();
            $quantity = rand(5, 20);

            $saleDate = Carbon::instance($faker->dateTimeBetween('-2 months', 'now'));

            for ($j = 0; $j < $quantity; $j++) {
                Sale::create([
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'price' => $product->price * 0.85,
                    'shipping' => $j === 0 ? 25.90 : 0,
                    'status' => 'pago',
                    'payment_method' => 'pix',
                    'sale_date' => $saleDate->toDateString(),
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate
                ]);

                $totalSales++;
            }
        }

        $this->command->info("$totalSales sales seeded successfully!");
        $this->command->info('Sales distribution:');

        $paidSales = Sale::where('status', 'pago')->count();
        $pendingSales = Sale::where('status', 'pendente')->count();
        $cancelledSales = Sale::where('status', 'cancelado')->count();
        $totalRevenue = Sale::where('status', 'pago')->sum(\DB::raw('price + shipping'));

        $this->command->info("- Paid: $paidSales");
        $this->command->info("- Pending: $pendingSales");
        $this->command->info("- Cancelled: $cancelledSales");
        $this->command->info("- Total Revenue: R$ " . number_format($totalRevenue, 2, ',', '.'));
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
