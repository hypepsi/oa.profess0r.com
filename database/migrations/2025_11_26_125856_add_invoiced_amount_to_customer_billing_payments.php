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
        Schema::table('customer_billing_payments', function (Blueprint $table) {
            $table->decimal('invoiced_amount', 10, 2)->nullable()->after('actual_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_billing_payments', function (Blueprint $table) {
            $table->dropColumn('invoiced_amount');
        });
    }
};
