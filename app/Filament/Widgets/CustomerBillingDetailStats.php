<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Services\BillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Route;

class CustomerBillingDetailStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Get customer and period from route parameters
        $routeParams = Route::current()?->parameters() ?? [];
        $customerId = $routeParams['customer'] ?? null;
        $year = $routeParams['year'] ?? null;
        $month = $routeParams['month'] ?? null;

        if (!$customerId || !$year || !$month) {
            return [];
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return [];
        }

        $period = Carbon::createFromDate($year, $month, 1, 'Asia/Shanghai')->startOfMonth();
        $expected = BillingCalculator::getCustomerExpectedTotalsForMonth($customer, $period);

        $payment = \App\Models\CustomerBillingPayment::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'billing_year' => $year,
                'billing_month' => $month,
            ],
            ['is_paid' => false]
        );
        $payment->load('paymentRecords');

        $totalReceived = (float) $payment->paymentRecords->sum('amount');
        $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);

        return [
            Stat::make('Expected', '$' . number_format($expected['expected_total'], 2))
                ->description($waivedAmount > 0 ? '-$' . number_format($waivedAmount, 2) . ' waived' : 'Total billable')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Received', '$' . number_format($totalReceived, 2))
                ->description($totalReceived > 0 ? 'Payments confirmed' : 'No payments yet')
                ->descriptionIcon($totalReceived > 0 ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                ->color($totalReceived > 0 ? 'success' : 'gray'),

            Stat::make('Subnets', number_format($expected['subnet_count']))
                ->description('$' . number_format($expected['subnet_total'], 2))
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('info'),

            Stat::make('Add-ons', '$' . number_format($expected['other_total'], 2))
                ->description('Extra charges')
                ->descriptionIcon('heroicon-o-plus-circle')
                ->color('info'),
        ];
    }
}

