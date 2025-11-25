<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\CustomerBillingPayment;
use App\Services\BillingCalculator;
use Carbon\Carbon;
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
    public array $paymentInput = [];
    public string $paymentNote = '';
    public \Illuminate\Support\Collection $addOnsItems;

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

        if ($this->payment->is_paid) {
            $this->paymentInput = ['amount' => (string) ($this->payment->actual_amount ?? '')];
            $this->paymentNote = $this->payment->notes ?? '';
        }
    }

    public function getHeading(): string
    {
        $periodLabel = $this->snapshot['period']->format('F Y');
        return 'Billing • ' . $this->customer->name . ' • ' . $periodLabel;
    }

    public function recordPayment(): void
    {
        $amount = $this->paymentInput['amount'] ?? null;

        if ($amount === null || $amount === '') {
            $this->addError('paymentInput.amount', 'Amount is required');
            return;
        }

        $amountValue = (float) $amount;
        if ($amountValue < 0) {
            $this->addError('paymentInput.amount', 'Amount must be positive');
            return;
        }

        $this->payment->update([
            'actual_amount' => $amountValue,
            'is_paid' => true,
            'is_waived' => false,
            'paid_at' => Carbon::now('Asia/Shanghai'),
            'recorded_by_user_id' => Auth::id(),
            'waived_at' => null,
            'waived_by_user_id' => null,
            'notes' => $this->paymentNote,
        ]);

        Notification::make()
            ->title('Payment recorded')
            ->success()
            ->send();

        $this->redirect('/admin/billing/customer?customer=' . $this->customer->id);
    }

    public function waivePayment(): void
    {
        if ($this->payment->is_paid) {
            Notification::make()
                ->title('This month is already marked as paid.')
                ->danger()
                ->send();
            return;
        }

        $this->payment->update([
            'is_paid' => false,
            'actual_amount' => null,
            'is_waived' => true,
            'waived_at' => Carbon::now('Asia/Shanghai'),
            'waived_by_user_id' => Auth::id(),
            'notes' => $this->paymentNote,
        ]);

        Notification::make()
            ->title('Marked as waived')
            ->warning()
            ->send();

        $this->redirect('/admin/billing/customer?customer=' . $this->customer->id);
    }

    public function resetPayment(): void
    {
        $this->payment->update([
            'actual_amount' => null,
            'is_paid' => false,
            'is_waived' => false,
            'paid_at' => null,
            'waived_at' => null,
            'waived_by_user_id' => null,
            'notes' => null,
        ]);

        $this->paymentInput = [];
        $this->paymentNote = '';

        Notification::make()
            ->title('Payment reset')
            ->warning()
            ->send();
    }
}
