<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->decimal('deduction_amount', 10, 2)->default(0)->after('approved_at')->comment('Deduction amount if overdue');
            $table->boolean('is_overdue')->default(false)->after('deduction_amount')->comment('Marked as overdue');
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn(['deduction_amount', 'is_overdue']);
        });
    }
};
