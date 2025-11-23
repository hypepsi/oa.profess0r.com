<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            if (!Schema::hasColumn('workflows', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            if (Schema::hasColumn('workflows', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
