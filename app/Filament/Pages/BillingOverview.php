<?php

namespace App\Filament\Pages;

use App\Services\BillingCalculator;
use Carbon\Carbon;
use Filament\Pages\Page;

class BillingOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'billing/overview';

    protected static string $view = 'filament.pages.billing-overview';

    public array $summary = [];

    public string $periodLabel = '';

    public function mount(): void
    {
        $this->loadSummary();
    }

    public function loadSummary(): void
    {
        $period = Carbon::now('Asia/Shanghai')->startOfMonth();
        $this->periodLabel = $period->format('F Y');
        $this->summary = BillingCalculator::getOverviewForMonth($period);
    }

    public function refreshSummary(): void
    {
        $this->loadSummary();
    }
}
