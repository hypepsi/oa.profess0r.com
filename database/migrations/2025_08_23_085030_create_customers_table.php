k<?php

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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 客户名称
            $table->string('website')->nullable(); // 客户网站（可选）
            $table->string('contact_wechat')->nullable(); // 联系人微信
            $table->string('contact_email')->nullable(); // 联系人邮箱
            $table->string('contact_telegram')->nullable(); // 联系人 Telegram
            $table->string('abuse_email')->nullable(); // Abuse / 合规邮箱
            $table->boolean('active')->default(true); // 是否启用
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

