<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $nowMonth = $now->format('F Y');
    @endphp

    {{-- Stats are now rendered by the CustomerBillingStats widget via getHeaderWidgets() --}}

    @php
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $currentDay = (int) $now->day;
    @endphp

    @if($currentMonthSnapshots->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Current Month</x-slot>

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                    <thead class="text-xs font-bold uppercase tracking-wider bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Month</th>
                            <th class="px-4 py-3 text-center">Subnets</th>
                            <th class="px-4 py-3">Subnet Total</th>
                            <th class="px-4 py-3">Add-ons</th>
                            <th class="px-4 py-3">Expected</th>
                            <th class="px-4 py-3">Invoiced</th>
                            <th class="px-4 py-3">Confirmed Received</th>
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
                                $isCurrentMonth = true; // This is always true in currentMonthSnapshots loop
                                
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
                            <tr class="{{ $rowClasses }} hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer" onclick="window.location.href='{{ $detailUrl }}'">
                                <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $periodLabel }}</td>
                                <td class="px-4 py-4 text-sm text-center text-gray-900 dark:text-gray-100">{{ $snapshot['subnet_count'] }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['other_total']) }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-sky-600 dark:text-sky-400">{{ $formatCurrency($totalReceived) }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
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

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                    <thead class="text-xs font-bold uppercase tracking-wider bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Month</th>
                            <th class="px-4 py-3 text-center">Subnets</th>
                            <th class="px-4 py-3">Subnet Total</th>
                            <th class="px-4 py-3">Add-ons</th>
                            <th class="px-4 py-3">Expected</th>
                            <th class="px-4 py-3">Invoiced</th>
                            <th class="px-4 py-3">Confirmed Received</th>
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
                            <tr class="{{ $rowClasses }} hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors {{ $isOverdue ? 'border-l-4 border-rose-500' : '' }}" onclick="window.location.href='{{ $detailUrl }}'">
                                <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $periodLabel }}</td>
                                <td class="px-4 py-4 text-sm text-center font-medium text-gray-700 dark:text-gray-300">{{ $snapshot['subnet_count'] }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $formatCurrency($snapshot['other_total']) }}</td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4 text-sm font-bold text-sky-600 dark:text-sky-400">{{ $formatCurrency($totalReceived) }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
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
        <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
            <div class="px-4 py-6 text-center text-sm font-medium text-gray-500 dark:text-gray-400">No billing history yet.</div>
        </div>
    @endif
</x-filament-panels::page>
