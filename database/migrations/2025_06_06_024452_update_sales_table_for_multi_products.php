<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Remove product_id se existir (agora usaremos sale_items)
            if (Schema::hasColumn('sales', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }

            // Remove price individual (agora será calculado dos itens)
            if (Schema::hasColumn('sales', 'price')) {
                $table->dropColumn('price');
            }

            // Adicionar campos para venda com múltiplos produtos
            $table->decimal('subtotal', 10, 2)->default(0); // Soma dos itens
            $table->decimal('discount_total', 10, 2)->default(0); // Desconto total da venda
            $table->decimal('tax_total', 10, 2)->default(0); // Impostos
            $table->decimal('total', 10, 2)->default(0); // Total final
            $table->text('notes')->nullable(); // Observações da venda
            $table->string('sale_number')->unique()->nullable(); // Número da venda
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'discount_total',
                'tax_total',
                'total',
                'notes',
                'sale_number'
            ]);
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
        });
    }
};
