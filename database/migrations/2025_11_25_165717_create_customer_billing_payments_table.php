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
        Schema::create('customer_billing_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('billing_year');
            $table->unsignedTinyInteger('billing_month');
            $table->decimal('actual_amount', 12, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'billing_year', 'billing_month'], 'customer_month_unique');
            $table->index(['billing_year', 'billing_month'], 'customer_billing_month_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_billing_payments');
    }
};
