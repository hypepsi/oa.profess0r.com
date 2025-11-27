<?php

namespace App\Providers\Filament;

use App\Models\Customer;
use App\Models\Provider;
use App\Models\IptProvider;
use App\Models\DatacenterProvider;
use App\Models\Workflow;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Support\Carbon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Schema;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Bunny Communications OA')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class, // Replace default dashboard with custom one
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Metadata')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Income')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Expense')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Workflows'),
            ])
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.workflow-table-scripts')
            )
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.sidebar-scroll-preserve')
            )
            ->navigationItems(array_merge(
                $this->getBillingNavigationItems(),
                $this->getWorkflowNavigationItems()
            ))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected function getWorkflowNavigationItems(): array
    {
        $now = Carbon::now('Asia/Shanghai')->startOfMonth();
        
        // 获取所有有workflow数据的月份（只统计有实际数据的月份）
        $workflowMonths = Workflow::query()
            ->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->setTimezone('Asia/Shanghai')->startOfMonth())
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->filter(fn (Carbon $date) => $date->lessThanOrEqualTo($now)) // 只显示当前月及之前的月份
            ->sortByDesc(fn (Carbon $date) => $date->format('Y-m'))
            ->values();

        // 确保当前月存在（即使没有数据也要显示）
        $currentMonthExists = $workflowMonths->contains(fn (Carbon $date) => $date->equalTo($now));
        if (!$currentMonthExists) {
            $workflowMonths->prepend($now);
        }

        // 保留最近6个月（只包括当前月及历史月份，不包含下个月）
        $orderedMonths = $workflowMonths
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->sortByDesc(fn (Carbon $date) => $date->format('Y-m'))
            ->take(6)
            ->values();

        // 重新排序：当前月在前，历史月份在后
        $currentMonth = $orderedMonths->filter(fn (Carbon $date) => 
            $date->equalTo($now)
        );

        $historical = $orderedMonths->filter(fn (Carbon $date) => 
            $date->lessThan($now)
        )->sortByDesc(fn (Carbon $date) => $date->format('Y-m'));

        $finalMonths = $currentMonth->merge($historical);

        return $finalMonths
            ->map(function (Carbon $date, int $index) {
                $year = $date->format('Y');
                $month = $date->format('n');

                return NavigationItem::make("workflows-{$year}-{$month}")
                    ->label($date->format('F Y'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->group('Workflows')
                    ->sort(200 + $index)
                    ->url("/admin/workflows/month/{$year}/{$month}");
            })
            ->toArray();
    }

    protected function getBillingNavigationItems(): array
    {
        $items = [];

        // Income group items
        $items[] = NavigationItem::make('income-overview')
            ->label('Income Overview')
            ->icon('heroicon-o-banknotes')
            ->group('Income')
            ->sort(10)
            ->url('/admin/billing/overview');

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        foreach ($customers as $index => $customer) {
            $items[] = NavigationItem::make("income-customer-{$customer->id}")
                ->label($customer->name)
                ->icon('heroicon-o-user-circle')
                ->group('Income')
                ->sort(20 + $index)
                ->url('/admin/billing/customer?customer=' . $customer->id);
        }

        $items[] = NavigationItem::make('income-add-ons')
            ->label('Add-ons')
            ->icon('heroicon-o-plus-circle')
            ->group('Income')
            ->sort(900)
            ->url('/admin/billing-other-items');

        // Expense group items
        $items[] = NavigationItem::make('expense-overview')
            ->label('Expense Overview')
            ->icon('heroicon-o-arrow-trending-down')
            ->group('Expense')
            ->sort(10)
            ->url('/admin/expense/overview');

        // 动态添加 IP Providers
        $providers = Provider::query()
            ->orderBy('name')
            ->get();

        foreach ($providers as $index => $provider) {
            $items[] = NavigationItem::make("expense-ip-provider-{$provider->id}")
                ->label($provider->name)
                ->icon('heroicon-o-building-office-2')
                ->group('Expense')
                ->sort(20 + $index)
                ->url('/admin/expense/provider?provider=' . $provider->id . '&type=ip');
        }

        // 动态添加 IPT Providers
        $iptProviders = IptProvider::query()
            ->orderBy('name')
            ->get();

        foreach ($iptProviders as $index => $iptProvider) {
            $items[] = NavigationItem::make("expense-ipt-provider-{$iptProvider->id}")
                ->label($iptProvider->name)
                ->icon('heroicon-o-server-stack')
                ->group('Expense')
                ->sort(100 + $index)
                ->url('/admin/expense/provider?provider=' . $iptProvider->id . '&type=ipt');
        }

        // 动态添加 Datacenter Providers
        $datacenterProviders = collect([]);
        if (Schema::hasTable('datacenter_providers')) {
            try {
                $datacenterProviders = DatacenterProvider::query()
                    ->where('active', true)
                    ->orderBy('name')
                    ->get();
            } catch (\Exception $e) {
                // 如果查询失败，返回空集合
                $datacenterProviders = collect([]);
            }
        }

        foreach ($datacenterProviders as $index => $datacenterProvider) {
            // 组合显示：Name + Location，如 "Equinix-HK2"
            $label = $datacenterProvider->name;
            if (!empty($datacenterProvider->location)) {
                $label = $datacenterProvider->name . '-' . $datacenterProvider->location;
            }
            
            $items[] = NavigationItem::make("expense-datacenter-provider-{$datacenterProvider->id}")
                ->label($label)
                ->icon('heroicon-o-building-office')
                ->group('Expense')
                ->sort(200 + $index)
                ->url('/admin/expense/provider?provider=' . $datacenterProvider->id . '&type=datacenter');
        }

        return $items;
    }
}
