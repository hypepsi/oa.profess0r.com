<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billing_other_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('billing_day')->default(1)->after('billing_month');
            $table->date('starts_on')->nullable()->after('billing_day');
            $table->string('status_new')->default('active')->after('description');
            $table->timestamp('released_at')->nullable()->after('status_new');
            $table->foreignId('released_by_user_id')
                ->nullable()
                ->after('released_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('billing_other_items')->select('*')->orderBy('id')->chunkById(100, function ($items) {
            foreach ($items as $item) {
                $year = $item->billing_year ?? now()->year;
                $month = $item->billing_month ?? now()->month;
                $day = 1;

                DB::table('billing_other_items')
                    ->where('id', $item->id)
                    ->update([
                        'billing_day' => $day,
                        'starts_on' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                        'status_new' => in_array($item->status, ['released'], true) ? 'released' : 'active',
                    ]);
            }
        });

        Schema::table('billing_other_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('billing_other_items', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_other_items', function (Blueprint $table) {
            $table->string('status_old')->default('pending')->after('description');
        });

        DB::table('billing_other_items')->select('*')->orderBy('id')->chunkById(100, function ($items) {
            foreach ($items as $item) {
                DB::table('billing_other_items')
                    ->where('id', $item->id)
                    ->update([
                        'status_old' => $item->status === 'released' ? 'pending' : 'confirmed',
                    ]);
            }
        });

        Schema::table('billing_other_items', function (Blueprint $table) {
            $table->dropColumn(['released_by_user_id', 'released_at', 'status', 'billing_day', 'starts_on']);
        });

        Schema::table('billing_other_items', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });
    }
};
