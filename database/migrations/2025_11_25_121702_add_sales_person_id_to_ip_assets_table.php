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
     * 添加销售人员字段，关联到employees表
     */
    public function up(): void
    {
        Schema::table('ip_assets', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_person_id')->nullable()->after('client_id');
            $table->foreign('sales_person_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_assets', function (Blueprint $table) {
            $table->dropForeign(['sales_person_id']);
            $table->dropColumn('sales_person_id');
        });
    }
};
