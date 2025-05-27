<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Sale;
use Carbon\Carbon;

class SalesSeeder extends Seeder
{
    public function run()
    {
        // Create customers
        $customers = [
            ['name' => 'Claudio Reis', 'email' => 'claudio@email.com'],
            ['name' => 'Vitor Henrique', 'email' => 'vitor@email.com'],
            ['name' => 'Mateus Amaro', 'email' => 'mateus@email.com'],
            ['name' => 'Thiago', 'email' => 'thiago@email.com'],
            ['name' => 'Miguel', 'email' => 'miguel@email.com'],
            ['name' => 'Jeann', 'email' => 'jeann@email.com'],
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::create($customerData);

            // Create sales for each customer
            Sale::create([
                'customer_id' => $customer->id,
                'price' => rand(50, 800) + (rand(0, 99) / 100),
                'shipping' => 95.45,
                'status' => ['pago', 'pendente', 'cancelado'][rand(0, 2)],
                'payment_method' => ['mastercard', 'visa', 'pix'][rand(0, 2)],
                'sale_date' => Carbon::now()->subDays(rand(0, 30))
            ]);
        }
    }
}
