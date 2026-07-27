<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salesplay_account_id')->constrained()->cascadeOnDelete();
            $table->string('salesplay_grn_id');
            $table->string('supplier_name')->nullable();
            $table->string('invoice_no')->nullable();
            $table->timestamp('received_at');
            $table->decimal('total', 12, 2)->default(0);
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['salesplay_account_id', 'salesplay_grn_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};
