<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    // =========================================================================
    // Category constants — mirror the keys in categoryRegistry() below.
    // When adding a new category, add a constant HERE and a row in the registry.
    // =========================================================================
    const CATEGORY_AUTH      = 'auth';
    const CATEGORY_INCOME    = 'income';
    const CATEGORY_EXPENSE   = 'expense';
    const CATEGORY_IP_ASSETS = 'ip_assets';
    const CATEGORY_WORKFLOWS = 'workflows';
    const CATEGORY_DOCUMENTS = 'documents';
    const CATEGORY_SYSTEM    = 'system';

    protected $fillable = [
        'user_id',
        'action',
        'category',
        'model_type',
        'model_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Category registry — THE single source of truth for all categories.
    //
    // Adding a new category (e.g. "tickets") requires exactly 3 steps:
    //   1. Add `const CATEGORY_TICKETS = 'tickets';` above.
    //   2. Add a row here: 'tickets' => [label, color, icon].
    //   3. Add detection rules in detectCategory() below.
    //
    // label : shown in UI (tabs, badges, filters)
    // color : Filament semantic color (success/warning/danger/info/primary/gray)
    // icon  : heroicon-o-* name used in tabs and other UI elements
    // =========================================================================
    private static function categoryRegistry(): array
    {
        return [
            self::CATEGORY_AUTH => [
                'label' => 'Authentication',
                'color' => 'info',
                'icon'  => 'heroicon-o-lock-closed',
            ],
            self::CATEGORY_INCOME => [
                'label' => 'Income',
                'color' => 'success',
                'icon'  => 'heroicon-o-arrow-trending-up',
            ],
            self::CATEGORY_EXPENSE => [
                'label' => 'Expense',
                'color' => 'danger',
                'icon'  => 'heroicon-o-arrow-trending-down',
            ],
            self::CATEGORY_IP_ASSETS => [
                'label' => 'IP Assets',
                'color' => 'primary',
                'icon'  => 'heroicon-o-server',
            ],
            self::CATEGORY_WORKFLOWS => [
                'label' => 'Workflows',
                'color' => 'warning',
                'icon'  => 'heroicon-o-clipboard-document-check',
            ],
            self::CATEGORY_DOCUMENTS => [
                'label' => 'Documents',
                'color' => 'gray',
                'icon'  => 'heroicon-o-document',
            ],
            self::CATEGORY_SYSTEM => [
                'label' => 'System',
                'color' => 'gray',
                'icon'  => 'heroicon-o-cog-6-tooth',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Public helpers derived from the registry — DO NOT edit these methods.
    // -------------------------------------------------------------------------

    /** All category keys => labels, used by filters, select inputs, etc. */
    public static function getCategoryOptions(): array
    {
        return array_map(fn ($c) => $c['label'], self::categoryRegistry());
    }

    /** Filament badge color for a category. */
    public static function getCategoryColor(string $category): string
    {
        return self::categoryRegistry()[$category]['color'] ?? 'gray';
    }

    /** Heroicon name for a category (used in Tabs and UI icons). */
    public static function getCategoryIcon(string $category): string
    {
        return self::categoryRegistry()[$category]['icon'] ?? 'heroicon-o-tag';
    }

    // =========================================================================
    // Category auto-detection
    //
    // Called by ActivityLogger and the Loggable trait when no explicit category
    // is passed.  Priority: action-pattern > model-class > 'system'.
    //
    // When adding a new module:
    //   • Action-based: add a prefix check (str_starts_with) or exact match.
    //   • Model-based:  add the class basename to the match at the bottom.
    // =========================================================================
    public static function detectCategory(string $action, ?string $modelType = null): string
    {
        // --- Action-based detection (highest priority) ---
        if (in_array($action, ['login', 'logout'])) {
            return self::CATEGORY_AUTH;
        }
        if (in_array($action, ['payment_recorded', 'invoice_updated', 'payment_waived', 'payment_reset'])) {
            return self::CATEGORY_INCOME;
        }
        if (str_starts_with($action, 'expense_'))   return self::CATEGORY_EXPENSE;
        if (str_starts_with($action, 'workflow_'))  return self::CATEGORY_WORKFLOWS;
        if (str_starts_with($action, 'document_'))  return self::CATEGORY_DOCUMENTS;
        if (str_starts_with($action, 'ip_asset_'))  return self::CATEGORY_IP_ASSETS;

        // --- Model-class-based detection ---
        if (!$modelType) {
            return self::CATEGORY_SYSTEM;
        }

        return match (class_basename($modelType)) {
            'CustomerBillingPayment',
            'BillingPaymentRecord',
            'BillingOtherItem',
            'IncomeOtherItem'       => self::CATEGORY_INCOME,

            'ProviderExpensePayment',
            'ExpensePaymentRecord'  => self::CATEGORY_EXPENSE,

            'IpAsset',
            'Device',
            'Location',
            'GeoFeedLocation'       => self::CATEGORY_IP_ASSETS,

            'Workflow',
            'WorkflowUpdate'        => self::CATEGORY_WORKFLOWS,

            'Document'              => self::CATEGORY_DOCUMENTS,

            default                 => self::CATEGORY_SYSTEM,
        };
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getUserNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name ?? $this->user->email;
        }
        return 'System';
    }
}
