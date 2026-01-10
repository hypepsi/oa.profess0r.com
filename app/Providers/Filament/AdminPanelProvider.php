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
                    ->label('Income')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Expense')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Workflows'),
                NavigationGroup::make()
                    ->label('Documents')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Metadata')
                    ->collapsed()
                    ->collapsible(true),
            ])
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.workflow-table-scripts')
            )
            ->navigationItems(array_merge(
                $this->getIncomeNavigationItems(),
                $this->getExpenseNavigationItems(),
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
        $months = collect([$now]);

        $workflowMonths = Workflow::query()
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->map(fn ($timestamp) => Carbon::parse($timestamp)->setTimezone('Asia/Shanghai')->startOfMonth())
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->values();

        $previousMonth = $now->copy()->subMonth();
        if ($workflowMonths->doesntContain(fn (Carbon $date) => $date->equalTo($previousMonth))) {
            $workflowMonths->push($previousMonth);
        }

        $historicalMonths = $workflowMonths
            ->filter(fn (Carbon $date) => $date->lessThanOrEqualTo($now))
            ->sortByDesc(fn (Carbon $date) => $date->format('Y-m'))
            ->take(12);

        $orderedMonths = $months
            ->merge($historicalMonths)
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->values();

        return $orderedMonths
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

    protected function getIncomeNavigationItems(): array
    {
        $items = [];

        // Income Overview
        $items[] = NavigationItem::make('income-overview')
            ->label('Overview')
            ->icon('heroicon-o-chart-bar')
            ->group('Income')
            ->sort(10)
            ->url('/admin/billing/overview');

        // Customer List
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

        // Add-ons
        $items[] = NavigationItem::make('income-add-ons')
            ->label('Add-ons')
            ->icon('heroicon-o-plus-circle')
            ->group('Income')
            ->sort(800)
            ->url('/admin/billing-other-items');

        // Other Income (should be registered by IncomeOtherItemResource, but we ensure it's at the end)

        return $items;
    }

    protected function getExpenseNavigationItems(): array
    {
        $items = [];

        // Expense Overview
        $items[] = NavigationItem::make('expense-overview')
            ->label('Expense Overview')
            ->icon('heroicon-o-arrow-trending-down')
            ->group('Expense')
            ->sort(10)
            ->url('/admin/expense/overview');

        // IP Providers
        $providers = Provider::query()
            ->orderBy('name')
            ->get();

        foreach ($providers as $index => $provider) {
            $items[] = NavigationItem::make("expense-provider-{$provider->id}")
                ->label($provider->name . ' (IP)')
                ->icon('heroicon-o-globe-alt')
                ->group('Expense')
                ->sort(20 + $index)
                ->url('/admin/expense/provider?provider=' . $provider->id . '&type=ip');
        }

        // IPT Providers
        $iptProviders = IptProvider::query()
            ->orderBy('name')
            ->get();

        $offset = count($providers);
        foreach ($iptProviders as $index => $iptProvider) {
            $items[] = NavigationItem::make("expense-ipt-{$iptProvider->id}")
                ->label($iptProvider->name . ' (IPT)')
                ->icon('heroicon-o-signal')
                ->group('Expense')
                ->sort(20 + $offset + $index)
                ->url('/admin/expense/provider?provider=' . $iptProvider->id . '&type=ipt');
        }

        // Datacenter Providers
        $datacenterProviders = DatacenterProvider::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $offset += count($iptProviders);
        foreach ($datacenterProviders as $index => $datacenter) {
            $items[] = NavigationItem::make("expense-datacenter-{$datacenter->id}")
                ->label($datacenter->name . ' (DC)')
                ->icon('heroicon-o-building-office-2')
                ->group('Expense')
                ->sort(20 + $offset + $index)
                ->url('/admin/expense/provider?provider=' . $datacenter->id . '&type=datacenter');
        }

        return $items;
    }
}
