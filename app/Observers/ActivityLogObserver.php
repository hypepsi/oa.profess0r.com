<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        ActivityLogger::log('created', $model, null, $this->resolveCategory($model, 'created'));
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged()) {
            ActivityLogger::log('updated', $model, null, $this->resolveCategory($model, 'updated'));
        }
    }

    public function deleted(Model $model): void
    {
        ActivityLogger::log('deleted', $model, null, $this->resolveCategory($model, 'deleted'));
    }

    public function restored(Model $model): void
    {
        ActivityLogger::log('restored', $model, null, $this->resolveCategory($model, 'restored'));
    }

    public function forceDeleted(Model $model): void
    {
        ActivityLogger::log('force_deleted', $model, null, $this->resolveCategory($model, 'force_deleted'));
    }

    // -------------------------------------------------------------------------
    // Category resolution
    //
    // Models using the Loggable trait can declare their own category via
    // getActivityLogCategory(). All other models fall back to auto-detection.
    // -------------------------------------------------------------------------

    private function resolveCategory(Model $model, string $action): ?string
    {
        if (method_exists($model, 'getActivityLogCategory')) {
            return $model->getActivityLogCategory();
        }

        // Returning null lets ActivityLogger::log() call detectCategory() itself.
        return null;
    }
}
