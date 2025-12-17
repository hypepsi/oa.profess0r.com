<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_assets', function (Blueprint $table) {
            // 添加 Geo Location 字段
            $table->string('geo_location')->nullable()->after('location_id');
            
            // 添加历史追踪时间字段
            $table->timestamp('released_at')->nullable()->after('status');
            $table->timestamp('client_changed_at')->nullable()->after('client_id');
            $table->timestamp('cost_changed_at')->nullable()->after('cost');
            $table->timestamp('price_changed_at')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('ip_assets', function (Blueprint $table) {
            $table->dropColumn([
                'geo_location',
                'released_at',
                'client_changed_at',
                'cost_changed_at',
                'price_changed_at',
            ]);
        });
    }
};

