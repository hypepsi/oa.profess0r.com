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
        Schema::create('income_other_items', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type')->default('customer'); // 'customer' or 'manual'
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('manual_source')->nullable(); // 手动输入的来源
            $table->date('date');
            $table->string('project')->nullable(); // 项目名称
            $table->decimal('cny_amount', 12, 2)->nullable(); // 人民币金额
            $table->decimal('usd_amount', 12, 2); // 美元金额
            $table->decimal('exchange_rate', 10, 4)->nullable(); // 汇率（用于自动换算）
            $table->string('payment_method')->nullable(); // 支付方式
            $table->foreignId('sales_person_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('evidence')->nullable(); // 截图证据文件路径
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('date');
            $table->index('customer_id');
            $table->index('sales_person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_other_items');
    }
};
