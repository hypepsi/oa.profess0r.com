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
    
    protected static ?string $title = 'Income Overview';

    protected static string $view = 'filament.pages.billing-overview';

    public array $summary = [];
    public array $previousSummary = [];

    public string $periodLabel = '';
    public string $previousPeriodLabel = '';

    public function mount(): void
    {
        $this->loadSummary();
    }

    public function loadSummary(): void
    {
        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        $previous = $current->copy()->subMonth();

        $this->periodLabel = $current->format('F Y');
        $this->previousPeriodLabel = $previous->format('F Y');
        $this->summary = BillingCalculator::getOverviewForMonth($current);
        $this->previousSummary = BillingCalculator::getOverviewForMonth($previous);
    }

}
