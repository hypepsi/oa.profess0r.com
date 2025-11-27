<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\IptProvider;
use App\Models\DatacenterProvider;
use App\Models\ProviderExpensePayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExpenseCalculator
{
    protected const HISTORY_MONTHS = 6;

    /**
     * 确保 Provider 有当前月的支出记录
     */
    public static function ensureProviderMonths($provider): void
    {
        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        $providerType = get_class($provider);
        
        $defaultExpected = 0;
        
        // 对于DatacenterProvider，使用hosting_fee + other_fee作为默认预期金额
        if ($provider instanceof DatacenterProvider) {
            $defaultExpected = (float) $provider->monthly_total_fee;
        }
        
        ProviderExpensePayment::firstOrCreate(
            [
                'provider_type' => $providerType,
                'provider_id' => $provider->id,
                'expense_year' => (int) $current->year,
                'expense_month' => (int) $current->month,
            ],
            [
                'expected_amount' => $defaultExpected,
                'is_paid' => false,
            ]
        );
    }

    /**
     * 获取 Provider 的月度快照
     */
    public static function getProviderMonthlySnapshots($provider): Collection
    {
        self::ensureProviderMonths($provider);

        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        $cutoff = $current->copy()->subMonths(self::HISTORY_MONTHS);
        $providerType = get_class($provider);

        $payments = ProviderExpensePayment::query()
            ->where('provider_type', $providerType)
            ->where('provider_id', $provider->id)
            ->where(function ($query) use ($current, $cutoff) {
                // Current month
                $query->where(function ($q) use ($current) {
                    $q->where('expense_year', $current->year)
                        ->where('expense_month', $current->month);
                })
                // Historical months
                ->orWhere(function ($q) use ($cutoff, $current) {
                    $q->where(function ($sub) use ($cutoff) {
                        $sub->where('expense_year', '>', $cutoff->year)
                            ->orWhere(function ($s) use ($cutoff) {
                                $s->where('expense_year', $cutoff->year)
                                    ->where('expense_month', '>=', $cutoff->month);
                            });
                    })
                    ->where(function ($sub) use ($current) {
                        $sub->where('expense_year', '<', $current->year)
                            ->orWhere(function ($s) use ($current) {
                                $s->where('expense_year', $current->year)
                                    ->where('expense_month', '<', $current->month);
                            });
                    });
                });
            })
            ->orderByDesc('expense_year')
            ->orderByDesc('expense_month')
            ->get();

        return $payments
            ->map(function (ProviderExpensePayment $payment) use ($provider) {
                $period = self::makePeriodCarbon($payment->expense_year, $payment->expense_month);
                $expected = self::getProviderExpectedAmountForMonth($provider, $period);

                return [
                    'payment' => $payment,
                    'period' => $period,
                    'expected_total' => $expected,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['period']->format('Y-m'))
            ->values();
    }

    /**
     * 获取 Provider 某月的预期支出金额
     */
    public static function getProviderExpectedAmountForMonth($provider, Carbon $period): float
    {
        $providerType = get_class($provider);
        $payment = ProviderExpensePayment::query()
            ->where('provider_type', $providerType)
            ->where('provider_id', $provider->id)
            ->where('expense_year', $period->year)
            ->where('expense_month', $period->month)
            ->first();
        
        // 如果已经有记录的预期金额，使用记录的值
        if ($payment && $payment->expected_amount !== null) {
            return (float) $payment->expected_amount;
        }
        
        // 对于DatacenterProvider，使用hosting_fee + other_fee作为默认预期金额
        if ($provider instanceof DatacenterProvider) {
            return (float) $provider->monthly_total_fee;
        }
        
        // 其他Provider类型，根据实际业务逻辑计算
        // 例如：基于 IP Assets 的成本、带宽费用等
        return (float) ($payment?->expected_amount ?? 0);
    }

    /**
     * 获取所有 Provider 的支出概览
     */
    public static function getOverviewForMonth(Carbon $period): array
    {
        $providers = Provider::all();
        $iptProviders = IptProvider::all();
        $datacenterProviders = DatacenterProvider::where('active', true)->get();
        $allProviders = collect($providers)
            ->merge($iptProviders)
            ->merge($datacenterProviders);

        $expectedTotal = 0.0;
        $paidTotal = 0.0;
        $overdueAmount = 0.0;
        $providersDue = 0;
        $topProviders = [];
        $overdueList = [];

        foreach ($allProviders as $provider) {
            $providerType = get_class($provider);
            
            // 确保有当前月的记录
            self::ensureProviderMonths($provider);
            
            $payment = ProviderExpensePayment::query()
                ->where('provider_type', $providerType)
                ->where('provider_id', $provider->id)
                ->where('expense_year', $period->year)
                ->where('expense_month', $period->month)
                ->first();

            if (!$payment) {
                continue;
            }

            $expected = (float) ($payment->expected_amount ?? 0);
            // 使用访问器获取总付款金额
            $paid = (float) $payment->total_paid;
            $invoiced = (float) ($payment->invoiced_amount ?? $expected);
            $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);
            $adjustedExpected = $expected - $waivedAmount;

            $expectedTotal += $expected;
            $paidTotal += $paid;

            if ($expected > 0) {
                $providersDue++;
            }

            // Check if overdue
            $now = Carbon::now('Asia/Shanghai');
            $periodDay20 = $period->copy()->day(20);
            $isPast20th = $now->greaterThan($periodDay20);
            $isPaid = $paid >= $invoiced;
            
            if ($isPast20th && !$isPaid && !$payment->is_waived && $adjustedExpected > 0) {
                $overdueAmount += ($adjustedExpected - $paid);
                $overdueList[] = [
                    'provider' => $provider,
                    'provider_type' => $providerType,
                    'amount' => $adjustedExpected - $paid,
                ];
            }

            if ($expected > 0) {
                $topProviders[] = [
                    'provider' => $provider,
                    'provider_type' => $providerType,
                    'amount' => $expected,
                ];
            }
        }

        // Sort top providers
        usort($topProviders, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $topProviders = array_slice($topProviders, 0, 3);

        return [
            'providers_due' => $providersDue,
            'expected_total' => $expectedTotal,
            'paid_total' => $paidTotal,
            'overdue_amount_total' => $overdueAmount,
            'top_providers' => $topProviders,
            'overdue' => $overdueList,
        ];
    }

    /**
     * 创建日期对象
     */
    protected static function makePeriodCarbon(int $year, int $month): Carbon
    {
        return Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Shanghai')->startOfMonth();
    }
}

