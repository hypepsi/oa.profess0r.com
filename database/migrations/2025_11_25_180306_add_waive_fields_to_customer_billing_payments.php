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
            $table->boolean('is_waived')->default(false)->after('is_paid');
            $table->timestamp('waived_at')->nullable()->after('is_waived');
            $table->foreignId('waived_by_user_id')
                ->nullable()
                ->after('waived_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_billing_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('waived_by_user_id');
            $table->dropColumn(['is_waived', 'waived_at']);
        });
    }
};
