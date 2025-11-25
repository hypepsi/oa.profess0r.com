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

    public array $paymentInputs = [];

    public array $paymentNotes = [];

    public array $stats = [];

    public ?int $editingPaymentId = null;

    public ?int $detailsPaymentId = null;

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

    public function recordPayment(int $paymentId): void
    {
        $amount = $this->paymentInputs[$paymentId] ?? null;

        if ($amount === null || $amount === '') {
            $this->addError("paymentInputs.{$paymentId}", 'Amount is required');
            return;
        }

        $amountValue = (float) $amount;
        if ($amountValue < 0) {
            $this->addError("paymentInputs.{$paymentId}", 'Amount must be positive');
            return;
        }

        $payment = CustomerBillingPayment::findOrFail($paymentId);
        $payment->update([
            'actual_amount' => $amountValue,
            'is_paid' => true,
            'is_waived' => false,
            'paid_at' => Carbon::now('Asia/Shanghai'),
            'recorded_by_user_id' => Auth::id(),
            'waived_at' => null,
            'waived_by_user_id' => null,
            'notes' => $this->paymentNotes[$paymentId] ?? null,
        ]);

        unset($this->paymentInputs[$paymentId], $this->paymentNotes[$paymentId]);
        $this->editingPaymentId = null;

        Notification::make()
            ->title('Payment recorded')
            ->success()
            ->send();

        $this->loadSnapshots();
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
        $ordered = collect();

        $current = $snapshots->firstWhere(fn ($snapshot) => $snapshot['period']->format('Y-m') === $currentKey);
        if ($current) {
            $ordered->push($current);
        }

        $remaining = $snapshots->filter(fn ($snapshot) => $snapshot !== $current);
        $this->snapshots = $ordered->merge($remaining)->values();
        $this->stats = $this->buildStats();
    }

    protected function buildStats(): array
    {
        $currentMonthKey = Carbon::now('Asia/Shanghai')->format('Y-m');
        $currentExpected = 0.0;
        $currentReceived = 0.0;
        $overdueFlag = false;
        $overdueCount = 0;
        $waivedTotal = 0.0;

        foreach ($this->snapshots as $snapshot) {
            /** @var Carbon $period */
            $period = $snapshot['period'];
            $key = $period->format('Y-m');

            if ($key === $currentMonthKey) {
                $currentExpected = $snapshot['expected_total'];
                if ($snapshot['payment']->is_paid) {
                    $currentReceived = (float) ($snapshot['payment']->actual_amount ?? 0);
                }
            }

            if ($snapshot['payment']->is_waived) {
                $waivedTotal += (float) $snapshot['expected_total'];
            }

            if (
                $period->lessThan(Carbon::now('Asia/Shanghai')->startOfMonth())
                && !$snapshot['payment']->is_paid
                && !$snapshot['payment']->is_waived
            ) {
                $overdueFlag = true;
                $overdueCount++;
            }
        }

        return [
            'current_expected' => $currentExpected,
            'current_received' => $currentReceived,
            'has_overdue' => $overdueFlag,
            'overdue_count' => $overdueCount,
            'waived_total' => $waivedTotal,
        ];
    }

    public function toggleDetails(int $paymentId): void
    {
        $this->detailsPaymentId = $this->detailsPaymentId === $paymentId ? null : $paymentId;
    }

    public function startEditing(int $paymentId): void
    {
        $this->editingPaymentId = $paymentId;
        $payment = CustomerBillingPayment::findOrFail($paymentId);

        if ($payment->is_paid || $payment->is_waived) {
            $this->editingPaymentId = null;
            Notification::make()
                ->title('This month is already closed.')
                ->warning()
                ->send();
            return;
        }

        $this->paymentInputs[$paymentId] = $payment->actual_amount ?? null;
        $this->paymentNotes[$paymentId] = $payment->notes ?? null;
    }

    public function cancelEditing(): void
    {
        $this->editingPaymentId = null;
    }
}
