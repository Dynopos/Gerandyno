<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * salesplay_receipt_id is the SalesPlay receipt_number, which is only
     * unique per shop (e.g. "10-0003") — a global unique constraint would
     * reject legitimate receipts from a second shop reusing the same number.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique('receipts_salesplay_receipt_id_unique');
            $table->unique(['salesplay_account_id', 'salesplay_receipt_id']);
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique(['salesplay_account_id', 'salesplay_receipt_id']);
            $table->unique('salesplay_receipt_id');
        });
    }
};
