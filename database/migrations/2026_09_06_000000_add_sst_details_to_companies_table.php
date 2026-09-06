<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a shop is registered for service tax decides how its own figures
 * must be read: a registered shop collects SST on the government's behalf,
 * so it is not income; an unregistered shop keeps every ringgit it takes.
 * Reporting the same receipts the same way for both is wrong for one of
 * them, and there is no way to tell from the receipts themselves.
 *
 * The remaining fields are the business details an SST-02 return has to
 * carry — the SST report renders them on the printable statement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('sst_registered')->default(false)->after('status');
            $table->string('sst_no')->nullable()->after('sst_registered');
            $table->string('ssm_no')->nullable()->after('sst_no');
            $table->string('address')->nullable()->after('ssm_no');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['sst_registered', 'sst_no', 'ssm_no', 'address']);
        });
    }
};
