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
    protected const HISTORY_MONTHS = 6;

    public static function ensureCustomerMonths(Customer $customer): void
    {
        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        // Only generate current month's billing
        CustomerBillingPayment::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'billing_year' => (int) $current->year,
                'billing_month' => (int) $current->month,
            ],
            [
                'is_paid' => false,
            ]
        );
    }

    public static function getCustomerMonthlySnapshots(Customer $customer): Collection
    {
        self::ensureCustomerMonths($customer);

        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        $cutoff = $current->copy()->subMonths(self::HISTORY_MONTHS);

        // Get current month + historical months (up to 6 months back, but only existing records)
        $payments = CustomerBillingPayment::query()
            ->where('customer_id', $customer->id)
            ->where(function ($query) use ($current, $cutoff) {
                // Current month
                $query->where(function ($q) use ($current) {
                    $q->where('billing_year', $current->year)
                        ->where('billing_month', $current->month);
                })
                // Historical months (only existing records, not auto-generated)
                ->orWhere(function ($q) use ($cutoff, $current) {
                    $q->where(function ($sub) use ($cutoff) {
                        $sub->where('billing_year', '>', $cutoff->year)
                            ->orWhere(function ($s) use ($cutoff) {
                                $s->where('billing_year', $cutoff->year)
                                    ->where('billing_month', '>=', $cutoff->month);
                            });
                    })
                    ->where(function ($sub) use ($current) {
                        $sub->where('billing_year', '<', $current->year)
                            ->orWhere(function ($s) use ($current) {
                                $s->where('billing_year', $current->year)
                                    ->where('billing_month', '<', $current->month);
                            });
                    });
                });
            })
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get();

        return $payments
            ->map(function (CustomerBillingPayment $payment) use ($customer) {
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
            })
            ->sortByDesc(fn (array $row) => $row['period']->format('Y-m'))
            ->values();
    }

    public static function getCustomerExpectedTotalsForMonth(Customer $customer, Carbon $period): array
    {
        $subnetCount = (int) $customer->ipAssets()->count();
        $subnetTotal = (float) $customer->ipAssets()->sum('price');

        $otherTotal = BillingOtherItem::query()
            ->where('customer_id', $customer->id)
            ->get()
            ->reduce(function (float $carry, BillingOtherItem $item) use ($period) {
                $start = $item->starts_on
                    ? $item->starts_on->copy()->startOfMonth()
                    : Carbon::create($item->billing_year, $item->billing_month, $item->billing_day ?? 1, 'Asia/Shanghai')->startOfMonth();

                if ($period->lt($start)) {
                    return $carry;
                }

                $releaseStart = $item->releaseStartDate();

                if ($item->status === 'released') {
                    if (!$releaseStart || $period->greaterThanOrEqualTo($releaseStart)) {
                        return $carry;
                    }
                }

                return $carry + (float) $item->amount;
            }, 0.0);

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
        $now = Carbon::now('Asia/Shanghai');
        $isCurrentMonth = $period->format('Y-m') === $now->format('Y-m');

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

            // Calculate overdue: include current month if past 20th and not paid/waived
            $overdueAmount = 0.0;
            
            // Check current month: if past 20th and not paid/waived, it's overdue
            if ($isCurrentMonth && $payment) {
                $periodDay20 = $period->copy()->day(20);
                $isPast20th = $now->greaterThan($periodDay20);
                
                if ($isPast20th && !$payment->is_paid && !$payment->is_waived) {
                    $overdueAmount = $expected['expected_total'];
                }
            }
            
            // Add historical overdue amounts
            $overdueAmount += self::calculateOverdueAmount($customer, $period);
            
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

        $summary['overdue_amount_total'] = collect($summary['overdue'])
            ->sum('amount');

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
            if ($payment->is_paid || $payment->is_waived) {
                continue;
            }

            $period = self::makePeriodCarbon($payment->billing_year, $payment->billing_month);
            $expected = self::getCustomerExpectedTotalsForMonth($customer, $period);
            $overdueTotal += $expected['expected_total'];
        }

        return $overdueTotal;
    }

    protected static function makePeriodCarbon(int $year, int $month): Carbon
    {
        return Carbon::createFromDate($year, $month, 1, 'Asia/Shanghai')->startOfMonth();
    }
}

