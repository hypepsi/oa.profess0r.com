<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipt_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Provider 名称（HE / Cogent / PCCW 等）
            $table->enum('bandwidth', ['1G', '10G'])->default('1G'); // 带宽下拉
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipt_providers');
    }
};
