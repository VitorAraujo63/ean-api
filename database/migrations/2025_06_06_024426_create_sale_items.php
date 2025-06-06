<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); // Preço unitário no momento da venda
            $table->decimal('total_price', 10, 2); // quantity * unit_price
            $table->decimal('discount', 10, 2)->default(0); // Desconto aplicado
            $table->text('notes')->nullable(); // Observações do item
            $table->timestamps();

            // Índices para performance
            $table->index(['sale_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_items');
    }
};
