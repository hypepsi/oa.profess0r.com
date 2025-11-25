<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        ActivityLogger::log('created', $model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        // 只记录有实际变更的情况
        if ($model->wasChanged()) {
            ActivityLogger::log('updated', $model);
        }
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        ActivityLogger::log('deleted', $model);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        ActivityLogger::log('restored', $model);
    }

    /**
     * Handle the Model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        ActivityLogger::log('force_deleted', $model);
    }
}
