<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('ean')->unique();
        $table->string('description')->nullable();
        $table->string('brand')->nullable();
        $table->string('ncm')->nullable();
        $table->string('unit')->nullable();
        $table->decimal('gross_weight', 8, 3)->nullable();
        $table->decimal('net_weight', 8, 3)->nullable();
        $table->string('image')->nullable();
        $table->string('source')->nullable(); // cosmos ou openfoodfacts
        $table->boolean('complete')->default(false);
        $table->decimal('price')->nullable();
        $table->decimal('cost')->nullable();
        $table->timestamps();
    });
    }
};
