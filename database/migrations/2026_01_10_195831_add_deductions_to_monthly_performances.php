<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_performances', function (Blueprint $table) {
            $table->decimal('workflow_deductions', 10, 2)->default(0)->after('net_profit')->comment('Deductions from overdue workflows');
            $table->integer('overdue_workflow_count')->default(0)->after('active_customer_count')->comment('Number of overdue workflows');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_performances', function (Blueprint $table) {
            $table->dropColumn(['workflow_deductions', 'overdue_workflow_count']);
        });
    }
};
