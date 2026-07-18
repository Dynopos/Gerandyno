<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salesplay_account_id')->constrained()->cascadeOnDelete();
            $table->string('salesplay_receipt_id')->unique();
            $table->string('receipt_number')->nullable();
            $table->dateTime('transaction_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_status')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'transaction_date']);
            $table->index(['salesplay_account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
