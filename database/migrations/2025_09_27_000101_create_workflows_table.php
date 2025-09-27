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

            // 创建人 & 审批人（指向 users 表）
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // 常用索引
            $table->index(['status', 'priority']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
