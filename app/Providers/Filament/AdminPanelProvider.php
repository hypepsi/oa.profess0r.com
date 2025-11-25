<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
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
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.workflow-table-scripts')
            )
            ->navigationItems($this->getWorkflowNavigationItems())
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
        $items = [];
        $now = Carbon::now('Asia/Shanghai');
        
        // Generate navigation items for current month and next 2 months (3 months total)
        for ($i = 0; $i < 3; $i++) {
            $date = $now->copy()->addMonths($i);
            $month = $date->format('n'); // 1-12
            $year = $date->format('Y');
            $monthName = $date->format('F Y'); // e.g., "November 2025"
            
            $items[] = NavigationItem::make("workflows-{$year}-{$month}")
                ->label($monthName)
                ->icon('heroicon-o-clipboard-document-check')
                ->group('Workflows')
                ->sort(100 + $i) // Keep workflows together
                ->url("/admin/workflows/month/{$year}/{$month}");
        }
        
        return $items;
    }
}
