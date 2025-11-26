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
        Schema::create('provider_expense_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_type'); // 'App\Models\Provider' or 'App\Models\IptProvider'
            $table->unsignedBigInteger('provider_id');
            $table->unsignedSmallInteger('expense_year');
            $table->unsignedTinyInteger('expense_month');
            $table->decimal('expected_amount', 12, 2)->nullable();
            $table->decimal('actual_amount', 12, 2)->nullable();
            $table->decimal('invoiced_amount', 12, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_waived')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('waived_at')->nullable();
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('waived_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider_type', 'provider_id', 'expense_year', 'expense_month'], 'provider_month_unique');
            $table->index(['expense_year', 'expense_month'], 'provider_expense_month_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_expense_payments');
    }
};
