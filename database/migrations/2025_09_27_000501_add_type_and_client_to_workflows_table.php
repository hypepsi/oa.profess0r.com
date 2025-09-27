<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            // 任务类型（来自 Work Flow Management → Task Types）
            $table->foreignId('task_type_id')
                ->nullable()
                ->after('title')
                ->constrained('task_types')
                ->nullOnDelete();

            // 关联客户（来自 Asset Management → Clients，对应 customers 表）
            $table->foreignId('client_id')
                ->nullable()
                ->after('task_type_id')
                ->constrained('customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            if (Schema::hasColumn('workflows', 'task_type_id')) {
                $table->dropConstrainedForeignId('task_type_id');
            }
            if (Schema::hasColumn('workflows', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }
        });
    }
};
