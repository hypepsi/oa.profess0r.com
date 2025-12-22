<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $nowMonth = $now->format('F Y');
    @endphp

    <div class="grid gap-4 mb-8 md:grid-cols-3">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-6 h-6 text-rose-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($stats['current_expected'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-check-badge" class="w-6 h-6" style="color: rgb(16 185 129);" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirmed Paid</p>
                    <p class="mt-1 text-2xl font-semibold" style="color: rgb(5 150 105);">
                        {{ $formatCurrency($stats['current_paid'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card style="{{ ($stats['has_overdue'] ?? false) ? 'border: 2px solid rgb(251 113 133); background-color: rgb(255 241 242);' : '' }}">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6" style="color: {{ ($stats['has_overdue'] ?? false) ? 'rgb(225 29 72)' : 'rgb(156 163 175)' }};" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue Alert</p>
                    <p class="mt-1 text-2xl font-semibold" style="color: {{ ($stats['has_overdue'] ?? false) ? 'rgb(225 29 72)' : 'rgb(17 24 39)' }};">
                        {{ $stats['overdue_message'] ?? 'All good' }}
                    </p>
                </div>
            </div>
        </x-filament::card>
    </div>

    @php
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $currentDay = (int) $now->day;
    @endphp

    @if($currentMonthSnapshots->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Current Month</x-slot>

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                    <thead class="text-sm font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Month</th>
                            <th class="px-4 py-3">Expected</th>
                            <th class="px-4 py-3">Invoiced</th>
                            <th class="px-4 py-3">Confirmed Paid</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($currentMonthSnapshots as $snapshot)
                            @php
                                /** @var \App\Models\ProviderExpensePayment $payment */
                                $payment = $snapshot['payment'];
                                $periodLabel = $snapshot['period']->format('F Y');
                                $periodDay20 = $snapshot['period']->copy()->day(20);
                                $isPast20th = $now->greaterThan($periodDay20);
                                $isCurrentMonth = true;
                                
                                // Calculate payment status
                                $totalPaid = (float) $payment->paymentRecords()->sum('amount');
                                $invoicedAmount = $payment->invoiced_amount ?? $snapshot['expected_total'];
                                $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);
                                $adjustedExpected = $snapshot['expected_total'] - $waivedAmount;
                                
                                $paymentStatus = 'pending';
                                if ($payment->is_waived) {
                                    $paymentStatus = 'waived';
                                } elseif ($totalPaid >= $invoicedAmount) {
                                    $paymentStatus = 'paid';
                                } elseif ($totalPaid > 0) {
                                    $difference = $adjustedExpected - $totalPaid;
                                    $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
                                    $paymentStatus = $differencePercent < 10 ? 'paid' : 'partial_paid';
                                }
                                
                                $isOverdue = ($isCurrentMonth && $isPast20th && $paymentStatus !== 'paid' && !$payment->is_waived) 
                                    || ($snapshot['period']->lessThan($now->startOfMonth()) && $paymentStatus !== 'paid' && !$payment->is_waived);
                                
                                $rowClasses = $isOverdue ? 'bg-rose-50/60 dark:bg-rose-950/30' : 'bg-white dark:bg-gray-900';
                                
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
                            <tr class="{{ $rowClasses }} hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $periodLabel }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4 text-sm font-semibold style="color: rgb(5 150 105);"">{{ $formatCurrency($totalPaid) }}</td>
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
            <x-slot name="heading">Historical Expenses</x-slot>

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                    <thead class="text-sm font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Month</th>
                            <th class="px-4 py-3">Expected</th>
                            <th class="px-4 py-3">Invoiced</th>
                            <th class="px-4 py-3">Confirmed Paid</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($historicalSnapshots as $snapshot)
                            @php
                                /** @var \App\Models\ProviderExpensePayment $payment */
                                $payment = $snapshot['payment'];
                                $periodLabel = $snapshot['period']->format('F Y');
                                
                                // Calculate payment status
                                $totalPaid = (float) $payment->paymentRecords()->sum('amount');
                                $invoicedAmount = $payment->invoiced_amount ?? $snapshot['expected_total'];
                                $waivedAmount = (float) ($payment->meta['waived_amount'] ?? 0);
                                $adjustedExpected = $snapshot['expected_total'] - $waivedAmount;
                                
                                $paymentStatus = 'pending';
                                if ($payment->is_waived) {
                                    $paymentStatus = 'waived';
                                } elseif ($totalPaid >= $invoicedAmount) {
                                    $paymentStatus = 'paid';
                                } elseif ($totalPaid > 0) {
                                    $difference = $adjustedExpected - $totalPaid;
                                    $differencePercent = $adjustedExpected > 0 ? ($difference / $adjustedExpected) * 100 : 0;
                                    $paymentStatus = $differencePercent < 10 ? 'paid' : 'partial_paid';
                                }
                                
                                $isOverdue = ($snapshot['period']->lessThan($now->startOfMonth()) && $paymentStatus !== 'paid' && !$payment->is_waived);
                                
                                $rowClasses = $isOverdue ? 'bg-rose-50/60 dark:bg-rose-950/30' : 'bg-white dark:bg-gray-900';
                                
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
                            <tr class="{{ $rowClasses }} hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $periodLabel }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4 text-sm font-semibold style="color: rgb(5 150 105);"">{{ $formatCurrency($totalPaid) }}</td>
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

    @if($currentMonthSnapshots->isEmpty() && $historicalSnapshots->isEmpty())
        <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
            <div class="px-4 py-6 text-center text-sm font-medium text-gray-500 dark:text-gray-400">No expense history yet.</div>
        </div>
    @endif
</x-filament-panels::page>

