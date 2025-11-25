<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogUserActivity
{
    /**
     * Handle the login event.
     */
    public function handleLogin(Login $event): void
    {
        ActivityLogger::logLogin($event->user);
    }

    /**
     * Handle the logout event.
     */
    public function handleLogout(Logout $event): void
    {
        ActivityLogger::logLogout($event->user);
    }
}
