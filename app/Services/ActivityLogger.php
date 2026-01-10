<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * 记录模型操作
     */
    public static function log(string $action, Model $model, ?array $properties = null): void
    {
        $user = Auth::user();
        
        // 生成描述
        $description = self::generateDescription($action, $model);
        
        ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'description' => $description,
            'properties' => $properties ?? self::getModelProperties($model, $action),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * 记录登录
     */
    public static function logLogin($user): void
    {
        // 防止重复记录：检查最近1秒内是否有相同的登录记录
        $recentLogin = ActivityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('ip_address', Request::ip())
            ->where('created_at', '>=', now()->subSecond())
            ->first();

        if ($recentLogin) {
            return; // 如果1秒内有相同记录，跳过
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => null,
            'model_id' => null,
            'description' => "User {$user->name} ({$user->email}) logged in",
            'properties' => null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * 记录登出
     */
    public static function logLogout($user): void
    {
        // 防止重复记录：检查最近1秒内是否有相同的登出记录
        $recentLogout = ActivityLog::where('user_id', $user->id)
            ->where('action', 'logout')
            ->where('ip_address', Request::ip())
            ->where('created_at', '>=', now()->subSecond())
            ->first();

        if ($recentLogout) {
            return; // 如果1秒内有相同记录，跳过
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'logout',
            'model_type' => null,
            'model_id' => null,
            'description' => "User {$user->name} ({$user->email}) logged out",
            'properties' => null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * 生成操作描述
     */
    protected static function generateDescription(string $action, Model $model): string
    {
        $modelName = class_basename($model);
        $actionText = match($action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default => ucfirst($action),
        };

        // 根据模型类型生成更具体的描述
        $identifier = self::getModelIdentifier($model);
        
        return "{$actionText} {$modelName}: {$identifier}";
    }

    /**
     * 获取模型的标识符（用于描述）
     */
    protected static function getModelIdentifier(Model $model): string
    {
        // 根据模型类型返回合适的标识符
        return match(class_basename($model)) {
            'IpAsset' => $model->cidr ?? "ID: {$model->id}",
            'Device' => $model->name ?? "ID: {$model->id}",
            'Location' => $model->name ?? "ID: {$model->id}",
            'Customer' => $model->name ?? "ID: {$model->id}",
            'Provider' => $model->name ?? "ID: {$model->id}",
            'IptProvider' => $model->name ?? "ID: {$model->id}",
            'Employee' => $model->name ?? "ID: {$model->id}",
            'Workflow' => $model->title ?? "ID: {$model->id}",
            'WorkflowUpdate' => "Update #{$model->id}",
            'BillingOtherItem' => $model->title ?? "ID: {$model->id}",
            'CustomerBillingPayment' => "Payment {$model->billing_year}-{$model->billing_month}",
            'BillingPaymentRecord' => "Record #{$model->id}",
            'Document' => $model->title ?? "ID: {$model->id}",
            default => "ID: {$model->id}",
        };
    }

    /**
     * 获取模型属性（用于记录变更）
     */
    protected static function getModelProperties(Model $model, string $action): ?array
    {
        if ($action === 'updated' && method_exists($model, 'getChanges')) {
            return [
                'changes' => $model->getChanges(),
                'original' => $model->getOriginal(),
            ];
        }

        if ($action === 'created' || $action === 'deleted') {
            return $model->toArray();
        }

        return null;
    }
}

