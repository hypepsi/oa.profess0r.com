<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Observers\ActivityLogObserver;

/**
 * Loggable — plug-in trait for automatic activity logging.
 *
 * Usage (new models only — existing models are registered in AppServiceProvider):
 *
 *   class MyNewModel extends Model
 *   {
 *       use Loggable;
 *
 *       // Optional: override the category for this model.
 *       public function getActivityLogCategory(): string
 *       {
 *           return ActivityLog::CATEGORY_WORKFLOWS; // default: auto-detected
 *       }
 *
 *       // Optional: override the human-readable identifier shown in log entries.
 *       public function getActivityLogIdentifier(): string
 *       {
 *           return $this->ticket_number ?? "ID: {$this->id}"; // default: name/title/id
 *       }
 *   }
 *
 * What this trait does automatically:
 *   • Registers ActivityLogObserver on the model (no AppServiceProvider change needed).
 *   • Provides default implementations of getActivityLogCategory() and
 *     getActivityLogIdentifier() that can be overridden per model.
 *
 * IMPORTANT: Do NOT add this trait to models that are already listed in
 * AppServiceProvider::registerActivityLogObservers() — that would register
 * the observer twice and cause duplicate log entries.
 */
trait Loggable
{
    /**
     * Auto-register the ActivityLogObserver when this model class is booted.
     * Called once per request per model class by Eloquent's boot mechanism.
     */
    public static function bootLoggable(): void
    {
        static::observe(ActivityLogObserver::class);
    }

    /**
     * Returns the log category for this model.
     * Override in your model to set a specific category.
     */
    public function getActivityLogCategory(): string
    {
        return ActivityLog::detectCategory('', static::class);
    }

    /**
     * Returns a short, human-readable identifier used in log entry descriptions.
     * Override in your model to return a meaningful value (e.g. ticket number, name).
     */
    public function getActivityLogIdentifier(): string
    {
        return $this->name
            ?? $this->title
            ?? $this->cidr
            ?? $this->number
            ?? "ID: {$this->id}";
    }
}
