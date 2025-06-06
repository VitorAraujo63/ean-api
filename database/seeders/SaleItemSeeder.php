<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SaleItem;

class SaleItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: This seeder is not needed as SaleItems are created
     * directly in the SaleSeeder for better data consistency.
     */
    public function run()
    {
        $this->command->info('SaleItems are created automatically in SaleSeeder.');
        $this->command->info('No additional seeding needed for SaleItems.');
    }
}
