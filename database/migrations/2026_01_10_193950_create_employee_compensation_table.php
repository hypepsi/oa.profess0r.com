<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('base_salary', 10, 2)->default(0)->comment('Base monthly salary');
            $table->decimal('commission_rate', 5, 4)->default(0.25)->comment('Commission rate, e.g., 0.25 = 25%');
            $table->boolean('exclude_from_shared_cost')->default(false)->comment('Exclude from shared cost allocation (for boss)');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('employee_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
