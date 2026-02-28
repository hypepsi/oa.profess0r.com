<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Record a model-level operation.
     *
     * @param  string       $action     e.g. 'created', 'updated', 'deleted'
     * @param  Model        $model      The model being acted upon
     * @param  array|null   $properties Optional override for properties stored
     * @param  string|null  $category   Optional override; auto-detected if null
     */
    public static function log(
        string $action,
        Model $model,
        ?array $properties = null,
        ?string $category = null
    ): void {
        $user = Auth::user();
        $description = self::generateDescription($action, $model);
        $category ??= ActivityLog::detectCategory($action, get_class($model));

        ActivityLog::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'category'    => $category,
            'model_type'  => get_class($model),
            'model_id'    => $model->id,
            'description' => $description,
            'properties'  => $properties ?? self::getModelProperties($model, $action),
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }

    /**
     * Record a login event (deduplication: ignores same user+IP within 1 second).
     */
    public static function logLogin($user): void
    {
        $recentLogin = ActivityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('ip_address', Request::ip())
            ->where('created_at', '>=', now()->subSecond())
            ->exists();

        if ($recentLogin) {
            return;
        }

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'login',
            'category'    => ActivityLog::CATEGORY_AUTH,
            'model_type'  => null,
            'model_id'    => null,
            'description' => "User {$user->name} ({$user->email}) logged in",
            'properties'  => null,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }

    /**
     * Record a logout event (deduplication: ignores same user+IP within 1 second).
     */
    public static function logLogout($user): void
    {
        $recentLogout = ActivityLog::where('user_id', $user->id)
            ->where('action', 'logout')
            ->where('ip_address', Request::ip())
            ->where('created_at', '>=', now()->subSecond())
            ->exists();

        if ($recentLogout) {
            return;
        }

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'logout',
            'category'    => ActivityLog::CATEGORY_AUTH,
            'model_type'  => null,
            'model_id'    => null,
            'description' => "User {$user->name} ({$user->email}) logged out",
            'properties'  => null,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    protected static function generateDescription(string $action, Model $model): string
    {
        $modelName  = class_basename($model);
        $actionText = match($action) {
            'created'      => 'Created',
            'updated'      => 'Updated',
            'deleted'      => 'Deleted',
            'force_deleted' => 'Force deleted',
            'restored'     => 'Restored',
            default        => ucfirst($action),
        };

        $identifier = self::getModelIdentifier($model);
        return "{$actionText} {$modelName}: {$identifier}";
    }

    protected static function getModelIdentifier(Model $model): string
    {
        // Models using the Loggable trait can declare their own identifier.
        if (method_exists($model, 'getActivityLogIdentifier')) {
            return $model->getActivityLogIdentifier();
        }

        // Fallback map for existing models registered via AppServiceProvider.
        return match(class_basename($model)) {
            'IpAsset'                => $model->cidr ?? "ID: {$model->id}",
            'Device'                 => $model->name ?? "ID: {$model->id}",
            'Location'               => $model->name ?? "ID: {$model->id}",
            'Customer'               => $model->name ?? "ID: {$model->id}",
            'Provider'               => $model->name ?? "ID: {$model->id}",
            'IptProvider'            => $model->name ?? "ID: {$model->id}",
            'DatacenterProvider'     => $model->name ?? "ID: {$model->id}",
            'Employee'               => $model->name ?? "ID: {$model->id}",
            'Workflow'               => $model->title ?? "ID: {$model->id}",
            'WorkflowUpdate'         => "Update #{$model->id}",
            'BillingOtherItem'       => $model->title ?? "ID: {$model->id}",
            'CustomerBillingPayment' => "Payment {$model->billing_year}-{$model->billing_month}",
            'BillingPaymentRecord'   => "Record #{$model->id}",
            'Document'               => $model->title ?? "ID: {$model->id}",
            default                  => "ID: {$model->id}",
        };
    }

    protected static function getModelProperties(Model $model, string $action): ?array
    {
        if ($action === 'updated' && method_exists($model, 'getChanges')) {
            return [
                'changes'  => $model->getChanges(),
                'original' => $model->getOriginal(),
            ];
        }

        if (in_array($action, ['created', 'deleted', 'force_deleted'])) {
            return $model->toArray();
        }

        return null;
    }
}
