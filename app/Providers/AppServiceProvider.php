<?php

namespace App\Providers;

use App\Models\BillingOtherItem;
use App\Models\BillingPaymentRecord;
use App\Models\Customer;
use App\Models\CustomerBillingPayment;
use App\Models\Device;
use App\Models\Employee;
use App\Models\IpAsset;
use App\Models\IptProvider;
use App\Models\Location;
use App\Models\Provider;
use App\Models\Workflow;
use App\Models\WorkflowUpdate;
use App\Observers\ActivityLogObserver;
use App\Listeners\LogUserActivity;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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

        // 注册活动日志 Observer
        $this->registerActivityLogObservers();

        // 注册登录/登出事件监听
        Event::listen(Login::class, [LogUserActivity::class, 'handleLogin']);
        Event::listen(Logout::class, [LogUserActivity::class, 'handleLogout']);
    }

    /**
     * 注册需要记录活动日志的模型 Observer
     */
    protected function registerActivityLogObservers(): void
    {
        $models = [
            IpAsset::class,
            Device::class,
            Location::class,
            Customer::class,
            Provider::class,
            IptProvider::class,
            Employee::class,
            Workflow::class,
            WorkflowUpdate::class,
            BillingOtherItem::class,
            BillingPaymentRecord::class,
            CustomerBillingPayment::class,
        ];

        foreach ($models as $model) {
            $model::observe(ActivityLogObserver::class);
        }
    }
}
