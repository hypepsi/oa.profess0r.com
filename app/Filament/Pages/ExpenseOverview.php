<?php

namespace App\Filament\Pages;

use App\Services\ExpenseCalculator;
use Carbon\Carbon;
use Filament\Pages\Page;

class ExpenseOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'expense/overview';

    protected static string $view = 'filament.pages.expense-overview';

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
        $this->summary = ExpenseCalculator::getOverviewForMonth($current);
        $this->previousSummary = ExpenseCalculator::getOverviewForMonth($previous);
    }
}

