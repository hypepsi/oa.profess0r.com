<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // 员工姓名
            $table->string('email')->unique();               // 邮箱（唯一）
            $table->string('phone')->nullable();             // 电话
            $table->string('department')->default('sales');  // sales / technical
            $table->boolean('is_active')->default(true);     // 在职状态
            $table->timestamps();

            $table->index(['department', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
