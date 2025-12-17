<?php

namespace App\Filament\Pages;

use App\Models\BillingPaymentRecord;
use App\Models\Customer;
use App\Models\CustomerBillingPayment;
use App\Services\BillingCalculator;
use App\Services\BillingActivityLogger;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CustomerBillingDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'billing/customer/{customer}/month/{year}/{month}';

    protected static string $view = 'filament.pages.customer-billing-detail';

    public Customer $customer;
    public CustomerBillingPayment $payment;
    public array $snapshot = [];
    public array $paymentInput = ['amount' => ''];
    public string $paymentNote = '';
    public ?float $invoicedAmount = null;
    public ?float $partialWaiveAmount = null;
    public string $waiveNote = '';
    public \Illuminate\Support\Collection $addOnsItems;
    public \Illuminate\Support\Collection $paymentRecords;

    public function mount(int|Customer $customer, int $year, int $month): void
    {
        $this->customer = $customer instanceof Customer ? $customer : Customer::findOrFail($customer);
        
        $this->payment = CustomerBillingPayment::firstOrCreate(
            [
                'customer_id' => $this->customer->id,
                'billing_year' => $year,
                'billing_month' => $month,
            ],
            [
                'is_paid' => false,
            ]
        );

        $period = Carbon::createFromDate($year, $month, 1, 'Asia/Shanghai')->startOfMonth();
        $expected = BillingCalculator::getCustomerExpectedTotalsForMonth($this->customer, $period);

        $this->snapshot = [
            'period' => $period,
            'subnet_count' => $expected['subnet_count'],
            'subnet_total' => $expected['subnet_total'],
            'other_total' => $expected['other_total'],
            'expected_total' => $expected['expected_total'],
        ];

        // Get add-ons items for this month
        $this->addOnsItems = \App\Models\BillingOtherItem::query()
            ->where('customer_id', $this->customer->id)
            ->get()
            ->filter(function ($item) use ($period) {
                $start = $item->starts_on
                    ? $item->starts_on->copy()->startOfMonth()
                    : Carbon::create($item->billing_year, $item->billing_month, $item->billing_day ?? 1, 'Asia/Shanghai')->startOfMonth();

                if ($period->lt($start)) {
                    return false;
                }

                $releaseStart = $item->releaseStartDate();

                if ($item->status === 'released') {
                    if (!$releaseStart || $period->greaterThanOrEqualTo($releaseStart)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        // Load payment records
        $this->payment->load('paymentRecords.recordedBy');
        $this->paymentRecords = $this->payment->paymentRecords;

        // Load invoiced amount
        $this->invoicedAmount = $this->payment->invoiced_amount;
    }

    public function getHeading(): string
    {
        $periodLabel = $this->snapshot['period']->format('F Y');
        return 'Billing • ' . $this->customer->name . ' • ' . $periodLabel;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reopen')
                ->label('Reopen Payment')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->payment->is_paid || $this->payment->is_waived || $this->paymentRecords->isNotEmpty())
                ->requiresConfirmation()
                ->modalHeading('Reopen Payment')
                ->modalDescription('Are you sure you want to reopen this payment? This will reset all payment records, waivers, and status.')
                ->modalSubmitActionLabel('Yes, reopen')
                ->modalCancelActionLabel('Cancel')
                ->action(function () {
                    $this->resetPayment();
                }),
            Action::make('fullWaive')
                ->label('Full Waive')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !$this->payment->is_waived)
                ->requiresConfirmation()
                ->modalHeading('Full Waive Payment')
                ->modalDescription('Are you sure you want to fully waive this payment? This will clear all payment records and mark the entire amount as waived.')
                ->modalSubmitActionLabel('Yes, waive')
                ->modalCancelActionLabel('Cancel')
                ->action(function () {
                    $this->fullWaive();
                }),
        ];
    }

    public function updateInvoicedAmount(): void
    {
        $this->validate([
            'invoicedAmount' => 'nullable|numeric|min:0',
        ]);

        $oldAmount = $this->payment->invoiced_amount;
        
        $this->payment->update([
            'invoiced_amount' => $this->invoicedAmount,
        ]);

        // Log activity
        BillingActivityLogger::logInvoiceUpdated(
            $this->payment,
            $this->customer,
            $oldAmount,
            $this->invoicedAmount
        );

        Notification::make()
            ->title('Invoiced amount updated')
            ->success()
            ->send();

        $this->updatePaymentStatus();
    }

    public function recordPayment(): void
    {
        $this->validate([
            'paymentInput.amount' => 'required|numeric|min:0.01',
        ]);

        $amountValue = (float) $this->paymentInput['amount'];

        $record = BillingPaymentRecord::create([
            'customer_billing_payment_id' => $this->payment->id,
            'amount' => $amountValue,
            'paid_at' => Carbon::now('Asia/Shanghai'),
            'recorded_by_user_id' => Auth::id(),
            'notes' => $this->paymentNote,
        ]);

        // Log activity
        BillingActivityLogger::logPaymentRecorded($record, $this->payment, $this->customer);

        $this->paymentInput = ['amount' => ''];
        $this->paymentNote = '';

        $this->updatePaymentStatus();

        Notification::make()
            ->title('Payment recorded')
            ->success()
            ->send();

        $this->loadPaymentRecords();
    }

    public function partialWaive(): void
    {
        $this->validate([
            'partialWaiveAmount' => 'required|numeric|min:0.01',
        ]);

        $waiveAmount = (float) $this->partialWaiveAmount;
        $expectedTotal = $this->snapshot['expected_total'];

        if ($waiveAmount >= $expectedTotal) {
            $this->addError('partialWaiveAmount', 'Partial waive amount must be less than expected total. Use "Full Waive" for complete waiver.');
            return;
        }

        // Store waived amount in meta
        $meta = $this->payment->meta ?? [];
        $waivedAmount = ($meta['waived_amount'] ?? 0) + $waiveAmount;
        $meta['waived_amount'] = $waivedAmount;
        $meta['waive_records'][] = [
            'amount' => $waiveAmount,
            'waived_at' => Carbon::now('Asia/Shanghai')->toIso8601String(),
            'waived_by_user_id' => Auth::id(),
            'notes' => $this->waiveNote,
        ];

        $this->payment->update([
            'meta' => $meta,
            'notes' => $this->waiveNote ?: $this->payment->notes,
        ]);

        // Log activity
        BillingActivityLogger::logPaymentWaived($this->payment, $this->customer, $waiveAmount, false);

        $this->partialWaiveAmount = null;
        $this->waiveNote = '';

        Notification::make()
            ->title('Partially waived')
            ->success()
            ->send();

        $this->loadPaymentRecords();
    }

    public function fullWaive(): void
    {
        if ($this->payment->is_paid && $this->paymentRecords->isNotEmpty()) {
            Notification::make()
                ->title('Cannot waive paid payment')
                ->body('Please reopen the payment first before waiving.')
                ->danger()
                ->send();
            return;
        }

        $expectedTotal = $this->snapshot['expected_total'];
        
        $this->payment->update([
            'is_paid' => false,
            'actual_amount' => null,
            'is_waived' => true,
            'waived_at' => Carbon::now('Asia/Shanghai'),
            'waived_by_user_id' => Auth::id(),
            'notes' => $this->waiveNote ?: $this->payment->notes,
        ]);

        // Clear all payment records
        $this->payment->paymentRecords()->delete();

        // Log activity
        BillingActivityLogger::logPaymentWaived($this->payment, $this->customer, $expectedTotal, true);

        $this->waiveNote = '';

        Notification::make()
            ->title('Fully waived')
            ->warning()
            ->send();

        $this->loadPaymentRecords();
    }

    public function resetPayment(): void
    {
        $this->payment->update([
            'actual_amount' => null,
            'invoiced_amount' => null,
            'is_paid' => false,
            'is_waived' => false,
            'paid_at' => null,
            'waived_at' => null,
            'waived_by_user_id' => null,
            'notes' => null,
            'meta' => null,
        ]);

        // Delete all payment records
        $this->payment->paymentRecords()->delete();

        // Log activity
        BillingActivityLogger::logPaymentReset($this->payment, $this->customer);

        $this->paymentInput = ['amount' => ''];
        $this->paymentNote = '';
        $this->invoicedAmount = null;
        $this->partialWaiveAmount = null;
        $this->waiveNote = '';

        Notification::make()
            ->title('Payment reset')
            ->warning()
            ->send();

        $this->loadPaymentRecords();
    }

    protected function updatePaymentStatus(): void
    {
        $totalReceived = $this->payment->total_received;
        $invoicedAmount = $this->payment->invoiced_amount ?? $this->snapshot['expected_total'];
        $expectedTotal = $this->snapshot['expected_total'];
        $waivedAmount = ($this->payment->meta['waived_amount'] ?? 0);
        $adjustedExpected = $expectedTotal - $waivedAmount;

        // Calculate payment status
        $isPaid = false;
        $isPartialPaid = false;

        if ($totalReceived >= $invoicedAmount) {
            $isPaid = true;
        } elseif ($totalReceived > 0) {
            $difference = $adjustedExpected - $totalReceived;
            $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
            
            if ($differencePercent < 10) {
                $isPaid = true;
            } else {
                $isPartialPaid = true;
            }
        }

        $this->payment->update([
            'is_paid' => $isPaid,
            'actual_amount' => $totalReceived,
            'paid_at' => $isPaid && $totalReceived > 0 ? Carbon::now('Asia/Shanghai') : null,
        ]);
    }

    protected function loadPaymentRecords(): void
    {
        $this->payment->refresh();
        $this->payment->load('paymentRecords.recordedBy');
        $this->paymentRecords = $this->payment->paymentRecords;
    }

    public function getTotalReceived(): float
    {
        return $this->payment->total_received;
    }

    public function getWaivedAmount(): float
    {
        return (float) ($this->payment->meta['waived_amount'] ?? 0);
    }

    public function getAdjustedExpected(): float
    {
        return $this->snapshot['expected_total'] - $this->getWaivedAmount();
    }

    public function getPaymentStatus(): string
    {
        if ($this->payment->is_waived) {
            return 'waived';
        }

        $totalReceived = $this->getTotalReceived();
        $invoicedAmount = $this->payment->invoiced_amount ?? $this->snapshot['expected_total'];
        $adjustedExpected = $this->getAdjustedExpected();

        if ($totalReceived >= $invoicedAmount) {
            return 'paid';
        }

        if ($totalReceived > 0) {
            $difference = $adjustedExpected - $totalReceived;
            $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
            
            if ($differencePercent < 10) {
                return 'paid';
            }
            
            return 'partial_paid';
        }

        return 'pending';
    }
}
