<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('pt_BR');

        // Create some specific customers first
        $specificCustomers = [
            [
                'name' => 'Claudio Reis',
                'email' => 'claudio.reis@email.com',
                'phone' => '(11) 99999-1234',
                'address' => 'Rua das Flores, 123 - São Paulo, SP'
            ],
            [
                'name' => 'Vitor Henrique',
                'email' => 'vitor.henrique@email.com',
                'phone' => '(21) 98888-5678',
                'address' => 'Av. Copacabana, 456 - Rio de Janeiro, RJ'
            ],
            [
                'name' => 'Mateus Amaro',
                'email' => 'mateus.amaro@email.com',
                'phone' => '(31) 97777-9012',
                'address' => 'Rua da Liberdade, 789 - Belo Horizonte, MG'
            ],
            [
                'name' => 'Thiago Silva',
                'email' => 'thiago.silva@email.com',
                'phone' => '(47) 96666-3456',
                'address' => 'Rua XV de Novembro, 321 - Blumenau, SC'
            ],
            [
                'name' => 'Miguel Santos',
                'email' => 'miguel.santos@email.com',
                'phone' => '(85) 95555-7890',
                'address' => 'Av. Beira Mar, 654 - Fortaleza, CE'
            ],
            [
                'name' => 'Jeann Oliveira',
                'email' => 'jeann.oliveira@email.com',
                'phone' => '(62) 94444-2468',
                'address' => 'Rua T-25, 987 - Goiânia, GO'
            ]
        ];

        foreach ($specificCustomers as $customerData) {
            Customer::create($customerData);
        }

        // Create additional random customers
        for ($i = 0; $i < 44; $i++) {
            Customer::create([
                'name' => $faker->name,
                'email' => $faker->unique()->email,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address
            ]);
        }

        $this->command->info('50 customers seeded successfully!');
    }
}
