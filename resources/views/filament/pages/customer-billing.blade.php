<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $nowMonth = $now->format('F Y');
    @endphp

    <div class="grid gap-4 mb-8 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-emerald-500" />
                <div>
                    <p class="oa-subheading">Expected ({{ $nowMonth }})</p>
                    <p class="oa-card-value-lg mt-1">
                        {{ $formatCurrency($stats['current_expected'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-check-badge" class="w-6 h-6" style="color: rgb(22 101 52);" />
                <div>
                    <p class="oa-subheading">Confirmed Received</p>
                    <p class="oa-card-value-lg mt-1" style="color: rgb(22 101 52);">
                        {{ $formatCurrency($stats['current_received'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-hand-raised" class="w-6 h-6 text-amber-500" />
                <div>
                    <p class="oa-subheading">Waived Totals</p>
                    <p class="oa-card-value-lg mt-1 text-amber-600 dark:text-amber-400">
                        {{ $formatCurrency($stats['waived_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card style="{{ ($stats['has_overdue'] ?? false) ? 'border: 3px solid rgb(220 38 38); background-color: rgb(254 226 226);' : '' }}">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6" style="color: {{ ($stats['has_overdue'] ?? false) ? 'rgb(220 38 38)' : 'rgb(156 163 175)' }};" />
                <div>
                    <p class="oa-subheading">Overdue Alert</p>
                    <p class="oa-card-value-lg mt-1" style="color: {{ ($stats['has_overdue'] ?? false) ? 'rgb(220 38 38)' : 'rgb(22 101 52)' }};">
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
            <x-slot name="heading">Current Month ({{ $nowMonth }})</x-slot>

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
                <table class="w-full text-left">
                    <thead class="oa-table-header bg-gray-50 dark:bg-gray-800">
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
                            <tr class="{{ $rowClasses }} hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer" onclick="window.location.href='{{ $detailUrl }}'">
                                <td class="oa-table-cell-emphasis px-4 py-4">{{ $periodLabel }}</td>
                                <td class="oa-table-cell px-4 py-4 text-center">{{ $snapshot['subnet_count'] }}</td>
                                <td class="oa-table-cell px-4 py-4">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                                <td class="oa-table-cell px-4 py-4">{{ $formatCurrency($snapshot['other_total']) }}</td>
                                <td class="oa-table-cell-emphasis px-4 py-4">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="oa-table-cell px-4 py-4">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4">
                                    <span class="oa-badge bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
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
                <table class="w-full text-left">
                    <thead class="oa-table-header bg-gray-50 dark:bg-gray-800">
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
                            <tr class="{{ $rowClasses }} hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer" onclick="window.location.href='{{ $detailUrl }}'">
                                <td class="oa-table-cell-emphasis px-4 py-4">{{ $periodLabel }}</td>
                                <td class="oa-table-cell px-4 py-4 text-center">{{ $snapshot['subnet_count'] }}</td>
                                <td class="oa-table-cell px-4 py-4">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                                <td class="oa-table-cell px-4 py-4">{{ $formatCurrency($snapshot['other_total']) }}</td>
                                <td class="oa-table-cell-emphasis px-4 py-4">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                                <td class="oa-table-cell px-4 py-4">{{ $formatCurrency($payment->invoiced_amount ?? '-') }}</td>
                                <td class="px-4 py-4">
                                    <span class="oa-badge bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
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
            <div class="px-4 py-6 text-center oa-body">No billing history yet.</div>
        </div>
    @endif
</x-filament-panels::page>
