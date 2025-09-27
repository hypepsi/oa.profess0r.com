<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 设备名称
            $table->string('type'); // 设备类型 (Router / Switch / Firewall / Server / Other)
            $table->string('main_ip'); // 主 IP 地址
            $table->unsignedBigInteger('location_id')->nullable(); // 关联 Location
            $table->unsignedBigInteger('provider_id')->nullable(); // 关联 Provider
            $table->text('credentials')->nullable(); // 登录信息（账号/密码）
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
