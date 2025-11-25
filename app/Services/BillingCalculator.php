<?php

namespace App\Services;

use App\Models\BillingOtherItem;
use App\Models\Customer;
use App\Models\CustomerBillingPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class BillingCalculator
{
    public static function ensureCustomerMonths(Customer $customer): void
    {
        $start = self::resolveStartOfBilling($customer);
        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        $cursor = $start->copy();

        while ($cursor <= $current) {
            CustomerBillingPayment::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'billing_year' => (int) $cursor->year,
                    'billing_month' => (int) $cursor->month,
                ],
                [
                    'is_paid' => false,
                ]
            );

            $cursor->addMonth();
        }
    }

    public static function getCustomerMonthlySnapshots(Customer $customer): Collection
    {
        self::ensureCustomerMonths($customer);

        $payments = CustomerBillingPayment::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get();

        return $payments->map(function (CustomerBillingPayment $payment) use ($customer) {
            $period = self::makePeriodCarbon($payment->billing_year, $payment->billing_month);
            $expected = self::getCustomerExpectedTotalsForMonth($customer, $period);

            return [
                'payment' => $payment,
                'period' => $period,
                'subnet_count' => $expected['subnet_count'],
                'subnet_total' => $expected['subnet_total'],
                'other_total' => $expected['other_total'],
                'expected_total' => $expected['expected_total'],
            ];
        });
    }

    public static function getCustomerExpectedTotalsForMonth(Customer $customer, Carbon $period): array
    {
        $subnetCount = (int) $customer->ipAssets()->count();
        $subnetTotal = (float) $customer->ipAssets()->sum('price');

        $otherTotal = (float) BillingOtherItem::query()
            ->where('customer_id', $customer->id)
            ->where('billing_year', $period->year)
            ->where('billing_month', $period->month)
            ->sum('amount');

        return [
            'subnet_count' => $subnetCount,
            'subnet_total' => $subnetTotal,
            'other_total' => $otherTotal,
            'expected_total' => $subnetTotal + $otherTotal,
        ];
    }

    public static function getOverviewForMonth(Carbon $period): array
    {
        $customers = Customer::query()->with('ipAssets')->get();
        $summary = [
            'customers_due' => 0,
            'expected_total' => 0.0,
            'top_customers' => [],
            'received_total' => 0.0,
            'overdue' => [],
        ];

        $topCollection = collect();

        foreach ($customers as $customer) {
            self::ensureCustomerMonths($customer);
            $expected = self::getCustomerExpectedTotalsForMonth($customer, $period);

            if ($expected['expected_total'] <= 0) {
                continue;
            }

            $summary['customers_due']++;
            $summary['expected_total'] += $expected['expected_total'];

            $topCollection->push([
                'customer' => $customer,
                'amount' => $expected['expected_total'],
            ]);

            $payment = CustomerBillingPayment::query()
                ->where('customer_id', $customer->id)
                ->where('billing_year', $period->year)
                ->where('billing_month', $period->month)
                ->first();

            if ($payment?->is_paid) {
                $summary['received_total'] += (float) ($payment->actual_amount ?? 0);
            }

            $overdueAmount = self::calculateOverdueAmount($customer, $period);
            if ($overdueAmount > 0) {
                $summary['overdue'][] = [
                    'customer' => $customer,
                    'amount' => $overdueAmount,
                ];
            }
        }

        $summary['top_customers'] = $topCollection
            ->sortByDesc('amount')
            ->take(3)
            ->values()
            ->all();

        return $summary;
    }

    protected static function calculateOverdueAmount(Customer $customer, Carbon $currentPeriod): float
    {
        $overdueTotal = 0.0;
        $payments = CustomerBillingPayment::query()
            ->where('customer_id', $customer->id)
            ->where(function ($query) use ($currentPeriod) {
                $query->where('billing_year', '<', $currentPeriod->year)
                    ->orWhere(function ($sub) use ($currentPeriod) {
                        $sub->where('billing_year', $currentPeriod->year)
                            ->where('billing_month', '<', $currentPeriod->month);
                    });
            })
            ->get();

        foreach ($payments as $payment) {
            if ($payment->is_paid) {
                continue;
            }

            $period = self::makePeriodCarbon($payment->billing_year, $payment->billing_month);
            $expected = self::getCustomerExpectedTotalsForMonth($customer, $period);
            $overdueTotal += $expected['expected_total'];
        }

        return $overdueTotal;
    }

    protected static function resolveStartOfBilling(Customer $customer): Carbon
    {
        $dates = new Collection();

        $firstAsset = $customer->ipAssets()->orderBy('created_at')->value('created_at');
        if ($firstAsset) {
            $dates->push(Carbon::parse($firstAsset)->startOfMonth());
        }

        $firstOtherItem = BillingOtherItem::query()
            ->where('customer_id', $customer->id)
            ->orderBy('billing_year')
            ->orderBy('billing_month')
            ->first();
        if ($firstOtherItem) {
            $dates->push(self::makePeriodCarbon($firstOtherItem->billing_year, $firstOtherItem->billing_month));
        }

        $firstPayment = CustomerBillingPayment::query()
            ->where('customer_id', $customer->id)
            ->orderBy('billing_year')
            ->orderBy('billing_month')
            ->first();
        if ($firstPayment) {
            $dates->push(self::makePeriodCarbon($firstPayment->billing_year, $firstPayment->billing_month));
        }

        return $dates->min() ?? Carbon::now('Asia/Shanghai')->startOfMonth();
    }

    protected static function makePeriodCarbon(int $year, int $month): Carbon
    {
        return Carbon::createFromDate($year, $month, 1, 'Asia/Shanghai')->startOfMonth();
    }
}

