<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function ($schedule): void {
        // 每天凌晨2点清理90天前的活动日志
        $schedule->command('activity-logs:clean --days=90')
            ->dailyAt('02:00')
            ->timezone('Asia/Shanghai')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->create();
