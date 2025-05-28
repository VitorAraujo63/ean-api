<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Starting complete database seeding...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables in correct order
        $this->command->info('🗑️  Cleaning existing data...');
        DB::table('sales')->truncate();
        DB::table('products')->truncate();
        DB::table('customers')->truncate();
        DB::table('categories')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('📊 Seeding categories...');
        $this->call(CategorySeeder::class);

        $this->command->info('👥 Seeding customers...');
        $this->call(CustomerSeeder::class);

        $this->command->info('📦 Seeding products...');
        $this->call(ProductSeeder::class);

        $this->command->info('💰 Seeding sales...');
        $this->call(SaleSeeder::class);

        $this->command->info('🎯 Seeding dashboard test data...');
        $this->call(DashboardTestSeeder::class);

        $this->command->info('✅ Database seeding completed successfully!');

        // Show final statistics
        $this->showStatistics();
    }

    private function showStatistics()
    {
        $this->command->info('📈 Final Statistics:');
        $this->command->info('- Categories: ' . DB::table('categories')->count());
        $this->command->info('- Products: ' . DB::table('products')->count());
        $this->command->info('- Customers: ' . DB::table('customers')->count());
        $this->command->info('- Sales: ' . DB::table('sales')->count());

        $totalRevenue = DB::table('sales')
            ->where('status', 'pago')
            ->sum(DB::raw('price + shipping'));

        $this->command->info('- Total Revenue: R$ ' . number_format($totalRevenue, 2, ',', '.'));

        $this->command->info('🎉 Ready to test your dashboard API!');
        $this->command->info('📡 Try: GET /api/dashboard');
    }
}
