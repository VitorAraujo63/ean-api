<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanDatabaseSeeder extends Seeder
{
    /**
     * Clean all data from sales-related tables
     */
    public function run()
    {
        $this->command->info('🧹 Cleaning database...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // List of tables to clean (in order)
        $tables = [
            'sale_items',
            'sales',
            'products',
            'categories',
            'customers'
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("   ✓ Cleaned table: {$table}");
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Database cleaned successfully!');
    }
}
