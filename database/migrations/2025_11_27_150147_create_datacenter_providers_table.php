<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     * 创建数据中心提供商表
     */
    public function up(): void
    {
        Schema::create('datacenter_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 数据中心提供商名称
            $table->string('location')->nullable(); // 位置
            $table->string('power')->nullable(); // 电力信息
            $table->text('address')->nullable(); // 地址
            $table->decimal('hosting_fee', 12, 2)->nullable(); // 托管费用（月）
            $table->decimal('other_fee', 12, 2)->nullable(); // 其他费用（月）
            $table->text('notes')->nullable(); // 备注
            $table->boolean('active')->default(true); // 是否启用
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datacenter_providers');
    }
};
