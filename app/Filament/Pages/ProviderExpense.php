<?php

namespace App\Filament\Pages;

use App\Models\Provider;
use App\Models\IptProvider;
use App\Models\ProviderExpensePayment;
use App\Services\ExpenseCalculator;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProviderExpense extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'expense/provider';

    protected static string $view = 'filament.pages.provider-expense';

    public $provider;
    public string $providerType = '';

    public Collection $snapshots;
    public Collection $currentMonthSnapshots;
    public Collection $historicalSnapshots;

    public array $stats = [];

    public function mount(): void
    {
        $providerId = request()->query('provider');
        $providerType = request()->query('type');
        
        abort_unless($providerId && $providerType, 404, 'Provider is required');

        $this->providerType = $providerType;
        
        if ($providerType === 'ip') {
            $this->provider = Provider::findOrFail((int) $providerId);
        } elseif ($providerType === 'ipt') {
            $this->provider = IptProvider::findOrFail((int) $providerId);
        } else {
            abort(404, 'Invalid provider type');
        }

        ExpenseCalculator::ensureProviderMonths($this->provider);
        $this->loadSnapshots();
    }

    public function getHeading(): string
    {
        return 'Expense • ' . $this->provider->name;
    }

    protected function loadSnapshots(): void
    {
        $snapshots = ExpenseCalculator::getProviderMonthlySnapshots($this->provider);

        $currentKey = Carbon::now('Asia/Shanghai')->format('Y-m');
        
        // Separate current month and historical months
        $this->currentMonthSnapshots = $snapshots->filter(fn ($snapshot) => $snapshot['period']->format('Y-m') === $currentKey)->values();
        $this->historicalSnapshots = $snapshots->filter(fn ($snapshot) => $snapshot['period']->format('Y-m') !== $currentKey)->values();
        
        // Keep snapshots for backward compatibility
        $this->snapshots = $this->currentMonthSnapshots->merge($this->historicalSnapshots);
        $this->stats = $this->buildStats();
    }

    protected function buildStats(): array
    {
        $currentMonthKey = Carbon::now('Asia/Shanghai')->format('Y-m');
        $now = Carbon::now('Asia/Shanghai');
        $currentExpected = 0.0;
        $currentPaid = 0.0;
        $overdueFlag = false;
        $waivedTotal = 0.0;

        // Only check current month for overdue
        $currentSnapshot = $this->snapshots->firstWhere(fn ($snapshot) => $snapshot['period']->format('Y-m') === $currentMonthKey);
        
        if ($currentSnapshot) {
            $currentExpected = $currentSnapshot['expected_total'];
            $payment = $currentSnapshot['payment'];
            $payment->load('paymentRecords');
            $totalPaid = (float) $payment->paymentRecords->sum('amount');
            $currentPaid = $totalPaid;

            // Calculate payment status for overdue check
            $invoicedAmount = $payment->invoiced_amount ?? $currentExpected;
            $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);
            $adjustedExpected = $currentExpected - $waivedAmount;
            
            $isPaid = false;
            if ($totalPaid >= $invoicedAmount) {
                $isPaid = true;
            } elseif ($totalPaid > 0) {
                $difference = $adjustedExpected - $totalPaid;
                $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
                $isPaid = $differencePercent < 10;
            }

            // Overdue logic: only for current month, after 20th, if not paid and not waived
            $periodDay20 = $currentSnapshot['period']->copy()->day(20);
            $isPast20th = $now->greaterThan($periodDay20);
            
            if (
                $isPast20th
                && !$isPaid
                && !$payment->is_waived
            ) {
                $overdueFlag = true;
            }
        }

        // Calculate waived total from all snapshots
        foreach ($this->snapshots as $snapshot) {
            if ($snapshot['payment']->is_waived) {
                $waivedTotal += (float) $snapshot['expected_total'];
            }
        }

        return [
            'current_expected' => $currentExpected,
            'current_paid' => $currentPaid,
            'has_overdue' => $overdueFlag,
            'waived_total' => $waivedTotal,
            'overdue_message' => $overdueFlag ? 'Action needed' : 'All good',
        ];
    }
}

