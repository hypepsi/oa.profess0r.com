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
     * 创建活动日志表，记录系统所有操作
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // 操作者
            $table->string('action', 50); // created, updated, deleted, login, logout
            $table->string('model_type')->nullable(); // 模型类名，如 App\Models\IpAsset
            $table->unsignedBigInteger('model_id')->nullable(); // 模型记录ID
            $table->string('description'); // 操作描述
            $table->json('properties')->nullable(); // 变更前后的数据（JSON格式）
            $table->string('ip_address', 45)->nullable(); // IP地址
            $table->text('user_agent')->nullable(); // 用户代理
            $table->timestamps();
            
            // 索引优化
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
