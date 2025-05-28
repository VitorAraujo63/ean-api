<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            CategorySeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            SaleSeeder::class,
        ]);
    }
}
