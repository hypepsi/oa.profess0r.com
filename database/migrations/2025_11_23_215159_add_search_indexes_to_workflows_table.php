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
     * Add search indexes to optimize workflow search performance
     */
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            // Add index for title (for search)
            $table->index('title');
            // Add index for created_at (for sorting and grouping)
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['created_at']);
        });
    }
};
