<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteSeeder extends Seeder
{
    /**
     * Run all seeders in the correct order for the multi-product sales system.
     */
    public function run()
    {
        $this->command->info('🌱 Starting complete database seeding...');

        // Clear existing data to avoid conflicts
        $this->command->info('🧹 Cleaning existing data...');
        $this->clearExistingData();

        // Core data first
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
        ]);

        $this->command->info('✅ Core data seeded successfully!');

        // Sales data (includes SaleItems automatically)
        $this->call([
            SaleSeeder::class,
        ]);

        $this->command->info('✅ Sales data seeded successfully!');

        // Final statistics
        $this->showFinalStats();

        $this->command->info('🎉 Complete seeding finished successfully!');
    }

    private function clearExistingData()
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear tables in correct order (respecting foreign keys)
        DB::table('sale_items')->truncate();
        DB::table('sales')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('customers')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('   ✓ Existing data cleared');
    }

    private function showFinalStats()
    {
        $stats = [
            'Categories' => \App\Models\Category::count(),
            'Products' => \App\Models\Product::count(),
            'Customers' => \App\Models\Customer::count(),
            'Sales' => \App\Models\Sale::count(),
            'Sale Items' => \App\Models\SaleItem::count(),
        ];

        $this->command->info("\n📊 Final Database Statistics:");
        foreach ($stats as $model => $count) {
            $this->command->info("   {$model}: {$count}");
        }

        // Revenue stats
        $totalRevenue = \App\Models\Sale::where('status', 'pago')->sum('total');
        $avgSaleValue = \App\Models\Sale::where('status', 'pago')->avg('total');
        $totalItems = \App\Models\SaleItem::sum('quantity');

        $this->command->info("\n💰 Revenue Statistics:");
        $this->command->info("   Total Revenue: R$ " . number_format($totalRevenue, 2, ',', '.'));
        $this->command->info("   Average Sale: R$ " . number_format($avgSaleValue, 2, ',', '.'));
        $this->command->info("   Total Items Sold: " . number_format($totalItems));
    }
}
