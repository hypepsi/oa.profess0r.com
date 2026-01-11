<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Services\BillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerBillingStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Get customer from URL
        $customerId = request()->integer('customer');
        if (!$customerId) {
            return [];
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return [];
        }

        // Calculate stats
        $snapshots = BillingCalculator::getCustomerMonthlySnapshots($customer);
        $currentKey = Carbon::now('Asia/Shanghai')->format('Y-m');
        $now = Carbon::now('Asia/Shanghai');
        
        $currentExpected = 0.0;
        $currentReceived = 0.0;
        $overdueFlag = false;
        $waivedTotal = 0.0;

        $currentSnapshot = $snapshots->firstWhere(fn ($s) => $s['period']->format('Y-m') === $currentKey);
        
        if ($currentSnapshot) {
            $currentExpected = $currentSnapshot['expected_total'];
            $payment = $currentSnapshot['payment'];
            $payment->load('paymentRecords');
            $totalReceived = (float) $payment->paymentRecords->sum('amount');
            $currentReceived = $totalReceived;

            $invoicedAmount = $payment->invoiced_amount ?? $currentExpected;
            $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);
            $adjustedExpected = $currentExpected - $waivedAmount;
            
            $isPaid = $totalReceived >= $invoicedAmount;
            if (!$isPaid && $totalReceived > 0) {
                $difference = $adjustedExpected - $totalReceived;
                $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
                $isPaid = $differencePercent < 10;
            }

            $periodDay20 = $currentSnapshot['period']->copy()->day(20);
            if ($now->greaterThan($periodDay20) && !$isPaid && !$payment->is_waived) {
                $overdueFlag = true;
            }
        }

        foreach ($snapshots as $snapshot) {
            if ($snapshot['payment']->is_waived) {
                $waivedTotal += (float) $snapshot['expected_total'];
            }
        }

        return [
            Stat::make('Expected', '$' . number_format($currentExpected, 2))
                ->description('Current month billable')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Received', '$' . number_format($currentReceived, 2))
                ->description('Payments confirmed')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color($currentReceived > 0 ? 'success' : 'gray'),

            Stat::make('Waived', '$' . number_format($waivedTotal, 2))
                ->description('Waived amounts')
                ->descriptionIcon('heroicon-o-hand-raised')
                ->color($waivedTotal > 0 ? 'warning' : 'gray'),

            Stat::make('Status', $overdueFlag ? 'Action needed' : 'All good')
                ->description($overdueFlag ? 'Action required' : 'No overdue payments')
                ->descriptionIcon($overdueFlag ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($overdueFlag ? 'danger' : 'success'),
        ];
    }
}

