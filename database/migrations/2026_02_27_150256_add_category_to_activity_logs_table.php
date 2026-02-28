<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('category', 30)->default('system')->after('action');
            $table->index('category');
        });

        // --- Backfill existing records ---

        // 1. Auth events (by action)
        DB::table('activity_logs')
            ->whereIn('action', ['login', 'logout'])
            ->update(['category' => 'auth']);

        // 2. Income events (by action)
        DB::table('activity_logs')
            ->whereIn('action', ['payment_recorded', 'invoice_updated', 'payment_waived', 'payment_reset'])
            ->update(['category' => 'income']);

        // 3. Expense events (by action)
        DB::table('activity_logs')
            ->whereIn('action', ['expense_payment_recorded', 'expense_invoice_updated', 'expense_waived', 'expense_reset'])
            ->update(['category' => 'expense']);

        // 4. Workflow events (by action)
        DB::table('activity_logs')
            ->whereIn('action', ['workflow_created', 'workflow_updated', 'workflow_status_changed', 'workflow_assigned', 'workflow_comment_added'])
            ->update(['category' => 'workflows']);

        // 5. Document events (by action)
        DB::table('activity_logs')
            ->whereIn('action', ['document_uploaded', 'document_updated', 'document_deleted'])
            ->update(['category' => 'documents']);

        // 6. IP Asset events (by action)
        DB::table('activity_logs')
            ->whereIn('action', ['ip_asset_status_changed', 'ip_asset_customer_changed', 'ip_asset_price_changed', 'ip_asset_cost_changed'])
            ->update(['category' => 'ip_assets']);

        // 7. Model-type-based backfill for any remaining 'system' records
        $modelCategoryMap = [
            'income' => [
                'App\\Models\\CustomerBillingPayment',
                'App\\Models\\BillingPaymentRecord',
                'App\\Models\\BillingOtherItem',
                'App\\Models\\IncomeOtherItem',
            ],
            'expense' => [
                'App\\Models\\ProviderExpensePayment',
                'App\\Models\\ExpensePaymentRecord',
            ],
            'ip_assets' => [
                'App\\Models\\IpAsset',
                'App\\Models\\Device',
                'App\\Models\\Location',
                'App\\Models\\GeoFeedLocation',
            ],
            'workflows' => [
                'App\\Models\\Workflow',
                'App\\Models\\WorkflowUpdate',
            ],
            'documents' => [
                'App\\Models\\Document',
            ],
        ];

        foreach ($modelCategoryMap as $category => $models) {
            DB::table('activity_logs')
                ->where('category', 'system') // only update records still at default
                ->whereIn('model_type', $models)
                ->update(['category' => $category]);
        }
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
