<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->enum('status', ['pago', 'pendente', 'cancelado'])->default('pendente');
            $table->enum('payment_method', ['mastercard', 'visa', 'pix', 'boleto'])->default('pix');
            $table->date('sale_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales');
    }
};
