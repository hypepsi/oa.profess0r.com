<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\CustomerBillingPayment;
use App\Services\BillingCalculator;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CustomerBilling extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'billing/customer';

    protected static string $view = 'filament.pages.customer-billing';

    public Customer $customer;

    public Collection $snapshots;
    public Collection $currentMonthSnapshots;
    public Collection $historicalSnapshots;

    public array $stats = [];

    public function mount(): void
    {
        $customerId = request()->integer('customer');
        abort_unless($customerId, 404, 'Customer is required');

        $this->customer = Customer::findOrFail($customerId);
        BillingCalculator::ensureCustomerMonths($this->customer);
        $this->loadSnapshots();
    }

    public function getHeading(): string
    {
        return 'Billing • ' . $this->customer->name;
    }


    public function waivePayment(int $paymentId): void
    {
        $payment = CustomerBillingPayment::findOrFail($paymentId);
        if ($payment->is_paid) {
            Notification::make()
                ->title('This month is already marked as paid.')
                ->danger()
                ->send();
            return;
        }

        $payment->update([
            'is_paid' => false,
            'actual_amount' => null,
            'is_waived' => true,
            'waived_at' => Carbon::now('Asia/Shanghai'),
            'waived_by_user_id' => Auth::id(),
            'notes' => $this->paymentNotes[$paymentId] ?? $payment->notes,
        ]);

        $this->editingPaymentId = null;

        Notification::make()
            ->title('Marked as waived')
            ->warning()
            ->send();

        $this->loadSnapshots();
    }

    public function resetPayment(int $paymentId): void
    {
        $payment = CustomerBillingPayment::findOrFail($paymentId);
        $payment->update([
            'actual_amount' => null,
            'is_paid' => false,
            'is_waived' => false,
            'paid_at' => null,
            'waived_at' => null,
            'waived_by_user_id' => null,
            'notes' => null,
        ]);

        unset($this->paymentInputs[$paymentId], $this->paymentNotes[$paymentId]);
        $this->editingPaymentId = null;

        Notification::make()
            ->title('Payment reset')
            ->warning()
            ->send();

        $this->loadSnapshots();
    }

    protected function loadSnapshots(): void
    {
        $snapshots = BillingCalculator::getCustomerMonthlySnapshots($this->customer);

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
        $currentReceived = 0.0;
        $overdueFlag = false;
        $waivedTotal = 0.0;

        // Only check current month for overdue
        $currentSnapshot = $this->snapshots->firstWhere(fn ($snapshot) => $snapshot['period']->format('Y-m') === $currentMonthKey);
        
        if ($currentSnapshot) {
            $currentExpected = $currentSnapshot['expected_total'];
            if ($currentSnapshot['payment']->is_paid) {
                $currentReceived = (float) ($currentSnapshot['payment']->actual_amount ?? 0);
            }

            // Overdue logic: only for current month, after 20th, if not paid and not waived
            $periodDay20 = $currentSnapshot['period']->copy()->day(20);
            $isPast20th = $now->greaterThan($periodDay20);
            
            if (
                $isPast20th
                && !$currentSnapshot['payment']->is_paid
                && !$currentSnapshot['payment']->is_waived
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
            'current_received' => $currentReceived,
            'has_overdue' => $overdueFlag,
            'waived_total' => $waivedTotal,
            'overdue_message' => $overdueFlag ? 'Action needed' : 'All good',
        ];
    }

}
