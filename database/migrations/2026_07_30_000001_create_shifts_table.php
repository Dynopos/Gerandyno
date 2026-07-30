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
        // An earlier, unrelated attempt at a "shifts" feature (PR #19,
        // reverted in PR #20) left this table behind on production — its
        // migration file was deleted as part of the revert, so Laravel has
        // no record of it and this migration collides with the orphaned
        // table. Drop it first; its schema doesn't match what we create
        // below anyway, so there's no data worth preserving from it.
        Schema::dropIfExists('shifts');

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salesplay_account_id')->constrained()->cascadeOnDelete();
            $table->string('salesplay_shift_id');
            $table->string('pos_device_id')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('opened_by_employee')->nullable();
            $table->string('closed_by_employee')->nullable();
            $table->decimal('starting_cash', 12, 2)->default(0);
            $table->decimal('cash_payments', 12, 2)->default(0);
            $table->decimal('cash_refunds', 12, 2)->default(0);
            $table->decimal('paid_in', 12, 2)->default(0);
            $table->decimal('paid_out', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('actual_cash', 12, 2)->default(0);
            $table->decimal('gross_sales', 12, 2)->default(0);
            $table->decimal('refunds', 12, 2)->default(0);
            $table->decimal('discounts', 12, 2)->default(0);
            $table->decimal('net_sales', 12, 2)->default(0);
            $table->decimal('tip', 12, 2)->default(0);
            $table->decimal('surcharge', 12, 2)->default(0);
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['salesplay_account_id', 'salesplay_shift_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
