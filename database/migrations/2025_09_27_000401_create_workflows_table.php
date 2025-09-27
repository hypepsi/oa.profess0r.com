<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            // priority: low / normal / high / urgent
            $table->string('priority')->default('normal');

            // status: open / in_review / approved / closed / overdue / cancelled
            $table->string('status')->default('open');

            $table->timestamp('due_at')->nullable();

            // 创建者：当前登录用户（users），便于审计
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // 审批人：公司员工（employees）
            $table->foreignId('approver_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
