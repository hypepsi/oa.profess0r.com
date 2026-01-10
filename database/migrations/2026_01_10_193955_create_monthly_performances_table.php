<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            
            // Revenue breakdown
            $table->decimal('ip_asset_revenue', 12, 2)->default(0)->comment('Revenue from IP assets (price sum)');
            $table->decimal('other_income', 12, 2)->default(0)->comment('Other income');
            $table->decimal('total_revenue', 12, 2)->default(0)->comment('Total revenue');
            
            // Cost breakdown
            $table->decimal('ip_direct_cost', 12, 2)->default(0)->comment('Direct cost from IP assets (cost sum)');
            $table->decimal('shared_cost', 12, 2)->default(0)->comment('Allocated shared costs');
            $table->decimal('shared_cost_ratio', 5, 4)->default(0)->comment('Ratio for shared cost allocation');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('Total cost');
            
            // Profit & Compensation
            $table->decimal('net_profit', 12, 2)->default(0)->comment('Net profit (revenue - total cost)');
            $table->decimal('base_salary', 10, 2)->default(0)->comment('Base salary for this month');
            $table->decimal('commission_rate', 5, 4)->default(0)->comment('Commission rate used');
            $table->decimal('commission_amount', 10, 2)->default(0)->comment('Commission earned');
            $table->decimal('total_compensation', 10, 2)->default(0)->comment('Total compensation');
            
            // Statistics
            $table->integer('active_subnet_count')->default(0)->comment('Number of active subnets');
            $table->integer('total_subnet_count')->default(0)->comment('Total subnets in system');
            $table->integer('active_customer_count')->default(0)->comment('Number of active customers');
            
            $table->text('calculation_details')->nullable()->comment('JSON with detailed calculation');
            $table->text('notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('calculated_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['employee_id', 'year', 'month']);
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_performances');
    }
};
