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
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('salesplay_product_id')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'salesplay_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
