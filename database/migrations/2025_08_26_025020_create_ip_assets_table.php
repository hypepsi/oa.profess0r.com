<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_assets', function (Blueprint $table) {
            $table->id();
            $table->string('cidr');

            // 外键关系（存 ID，但表单里显示 name）
            $table->unsignedBigInteger('ip_provider_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('ipt_provider_id')->nullable();

            $table->enum('type', ['BGP', 'ISP ASN'])->nullable();
            $table->unsignedBigInteger('asn')->nullable();

            $table->enum('status', ['Active', 'Reserved', 'Released'])->default('Active');
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_assets');
    }
};
