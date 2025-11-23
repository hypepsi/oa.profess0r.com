<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set MySQL timezone to Beijing time (UTC+8)
        \Illuminate\Support\Facades\DB::statement("SET time_zone = '+08:00'");
        
        // Set default Carbon timezone
        \Carbon\Carbon::setLocale('zh_CN');
        date_default_timezone_set('Asia/Shanghai');
    }
}
