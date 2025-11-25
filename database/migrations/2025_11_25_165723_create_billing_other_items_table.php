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
        Schema::create('billing_other_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->unsignedSmallInteger('billing_year');
            $table->unsignedTinyInteger('billing_month');
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['billing_year', 'billing_month'], 'billing_other_items_month_index');
            $table->index(['customer_id', 'billing_year', 'billing_month'], 'billing_other_items_customer_month_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_other_items');
    }
};
