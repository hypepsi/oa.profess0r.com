<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('geofeed_locations', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'region', 'city', 'postal_code'], 'geofeed_locations_unique');
            $table->index(['country_code', 'region', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofeed_locations');
    }
};
