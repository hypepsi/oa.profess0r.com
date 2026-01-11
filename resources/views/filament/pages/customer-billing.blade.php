<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $nowMonth = $now->format('F Y');
    @endphp

    @php
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $currentDay = (int) $now->day;
    @endphp

    @if($currentMonthSnapshots->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Current Month ({{ $nowMonth }})</x-slot>

            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Month</th>
                            <th class="px-4 py-3 text-center">Subnets</th>
                            <th class="px-4 py-3">Subnet Total</th>
                            <th class="px-4 py-3">Add-ons</th>
                            <th class="px-4 py-3">Expected</th>
                            <th class="px-4 py-3">Invoiced</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($currentMonthSnapshots as $snapshot)
                            @php
                                /** @var \App\Models\CustomerBillingPayment $payment */
                                $payment = $snapshot['payment'];
                                $periodLabel = $snapshot['period']->format('F Y');
                                $periodDay20 = $snapshot['period']->copy()->day(20);
                                $isPast20th = $now->greaterThan($periodDay20);
                                $isCurrentMonth = $snapshot['period']->isSameMonth($now);
                                
                                // Calculate payment status
                                $totalReceived = (float) $payment->paymentRecords()->sum('amount');
                                $invoicedAmount = $payment->invoiced_amount ?? $snapshot['expected_total'];
                                $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);
                                $adjustedExpected = $snapshot['expected_total'] - $waivedAmount;
                                
                                $paymentStatus = 'pending';
                                if ($payment->is_waived) {
                                    $paymentStatus = 'waived';
                                } elseif ($totalReceived >= $invoicedAmount) {
                                    $paymentStatus = 'paid';
                                } elseif ($totalReceived > 0) {
                                    $difference = $adjustedExpected - $totalReceived;
                                    $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
                                    $paymentStatus = $differencePercent < 10 ? 'paid' : 'partial_paid';
                                }
                                
                                $isOverdue = ($isCurrentMonth && $isPast20th && $paymentStatus !== 'paid' && !$payment->is_waived) 
                                    || ($snapshot['period']->lessThan($now->startOfMonth()) && $paymentStatus !== 'paid' && !$payment->is_waived);
                                
                                $rowClasses = $isOverdue ? 'bg-rose-50/60 dark:bg-rose-950/30' : 'bg-white dark:bg-gray-900';
                                $detailUrl = '/admin/billing/customer/' . $customer->id . '/month/' . $snapshot['period']->year . '/' . $snapshot['period']->month;
                                
                                $statusConfig = match($paymentStatus) {
                                    'paid' => ['color' => 'emerald', 'label' => 'Paid'],
                                    'partial_paid' => ['color' => 'blue', 'label' => 'Partial Paid'],
                                    'waived' => ['color' => 'amber', 'label' => 'Waived'],
                                    'pending' => ['color' => 'gray', 'label' => 'Pending'],
                                    default => ['color' => 'gray', 'label' => 'Pending'],
                                };
                                $statusColor = $isOverdue ? 'rose' : $statusConfig['color'];
                                $statusLabel = $isOverdue ? 'Overdue' : $statusConfig['label'];
                            @endphp
                            <tr class="{{ $rowClasses }} cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50" onclick="window.location.href='{{ $detailUrl }}'">
                                <td class="px-4 py-4 font-medium text-gray-950 dark:text-white">{{ $periodLabel }}</td>
                                <td class="px-4 py-4 text-center text-gray-700 dark:text-gray-300">{{ $snapshot['subnet_count'] }}</td>
                                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $formatCurrency($snapshot['other_total']) }}</td>
                                <td class="px-4 py-4 font-medium text-gray-950 dark:text-white">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    @if($historicalSnapshots->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Historical Bills</x-slot>

            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Month</th>
                            <th class="px-4 py-3 text-center">Subnets</th>
                            <th class="px-4 py-3">Subnet Total</th>
                            <th class="px-4 py-3">Add-ons</th>
                            <th class="px-4 py-3">Expected</th>
                            <th class="px-4 py-3">Invoiced</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($historicalSnapshots as $snapshot)
                            @php
                                /** @var \App\Models\CustomerBillingPayment $payment */
                                $payment = $snapshot['payment'];
                                $periodLabel = $snapshot['period']->format('F Y');
                                
                                // Calculate payment status
                                $totalReceived = (float) $payment->paymentRecords()->sum('amount');
                                $invoicedAmount = $payment->invoiced_amount ?? $snapshot['expected_total'];
                                $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);
                                $adjustedExpected = $snapshot['expected_total'] - $waivedAmount;
                                
                                $paymentStatus = 'pending';
                                if ($payment->is_waived) {
                                    $paymentStatus = 'waived';
                                } elseif ($totalReceived >= $invoicedAmount) {
                                    $paymentStatus = 'paid';
                                } elseif ($totalReceived > 0) {
                                    $difference = $adjustedExpected - $totalReceived;
                                    $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
                                    $paymentStatus = $differencePercent < 10 ? 'paid' : 'partial_paid';
                                }
                                
                                $isOverdue = ($snapshot['period']->lessThan($now->startOfMonth()) && $paymentStatus !== 'paid' && !$payment->is_waived);
                                
                                $rowClasses = $isOverdue ? 'bg-rose-50/60 dark:bg-rose-950/30' : 'bg-white dark:bg-gray-900';
                                $detailUrl = '/admin/billing/customer/' . $customer->id . '/month/' . $snapshot['period']->year . '/' . $snapshot['period']->month;
                                
                                $statusConfig = match($paymentStatus) {
                                    'paid' => ['color' => 'emerald', 'label' => 'Paid'],
                                    'partial_paid' => ['color' => 'blue', 'label' => 'Partial Paid'],
                                    'waived' => ['color' => 'amber', 'label' => 'Waived'],
                                    'pending' => ['color' => 'gray', 'label' => 'Pending'],
                                    default => ['color' => 'gray', 'label' => 'Pending'],
                                };
                                $statusColor = $isOverdue ? 'rose' : $statusConfig['color'];
                                $statusLabel = $isOverdue ? 'Overdue' : $statusConfig['label'];
                            @endphp
                            <tr class="{{ $rowClasses }} cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50" onclick="window.location.href='{{ $detailUrl }}'">
                                <td class="px-4 py-4 font-medium text-gray-950 dark:text-white">{{ $periodLabel }}</td>
                                <td class="px-4 py-4 text-center text-gray-700 dark:text-gray-300">{{ $snapshot['subnet_count'] }}</td>
                                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $formatCurrency($snapshot['other_total']) }}</td>
                                <td class="px-4 py-4 font-medium text-gray-950 dark:text-white">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    @if($currentMonthSnapshots->isEmpty() && $historicalSnapshots->isEmpty())
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No billing history yet.</div>
        </div>
    @endif
</x-filament-panels::page>
