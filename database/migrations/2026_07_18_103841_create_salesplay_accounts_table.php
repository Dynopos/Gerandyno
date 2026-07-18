<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salesplay_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('shop_name');
            $table->string('salesplay_shop_id')->nullable();
            $table->text('api_token');
            $table->timestamp('last_synced_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('last_sync_status', ['success', 'failed'])->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'salesplay_shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesplay_accounts');
    }
};
