<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $periodLabel = $snapshot['period']->format('F Y');
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $periodDay20 = $snapshot['period']->copy()->day(20);
        $isPast20th = $now->greaterThan($periodDay20);
        $isCurrentMonth = $snapshot['period']->format('Y-m') === $now->format('Y-m');
        
        $totalReceived = $this->getTotalReceived();
        $waivedAmount = $this->getWaivedAmount();
        $adjustedExpected = $this->getAdjustedExpected();
        $paymentStatus = $this->getPaymentStatus();
        
        $isOverdue = ($isCurrentMonth && $isPast20th && $paymentStatus !== 'paid' && !$payment->is_waived) 
            || ($snapshot['period']->lessThan($now->startOfMonth()) && $paymentStatus !== 'paid' && !$payment->is_waived);
    @endphp

    <div class="mb-6">
        <x-filament::button 
            color="gray" 
            icon="heroicon-o-arrow-left" 
            tag="a"
            href="/admin/billing/customer?customer={{ $customer->id }}"
        >
            Back to Billing
        </x-filament::button>
    </div>

    <div class="grid gap-4 mb-8 md:grid-cols-4">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-emerald-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Total</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($snapshot['expected_total']) }}
                    </p>
                    @if($waivedAmount > 0)
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                            -{{ $formatCurrency($waivedAmount) }} waived
                        </p>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mt-1">
                            Adjusted: {{ $formatCurrency($adjustedExpected) }}
                        </p>
                    @endif
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-rectangle-stack" class="w-6 h-6 text-blue-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Subnets</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $snapshot['subnet_count'] }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $formatCurrency($snapshot['subnet_total']) }}</p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-plus-circle" class="w-6 h-6 text-purple-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Add-ons</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($snapshot['other_total']) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-currency-dollar" class="w-6 h-6 text-indigo-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Received</p>
                    <p class="mt-1 text-2xl font-semibold {{ $totalReceived > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ $formatCurrency($totalReceived) }}
                    </p>
                    @if($paymentRecords->isNotEmpty())
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $paymentRecords->count() }} payment(s)
                        </p>
                    @endif
                </div>
            </div>
        </x-filament::card>
    </div>

    @if($addOnsItems->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Add-ons Details</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                    <thead class="text-sm font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($addOnsItems as $item)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->title }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $item->category ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($item->amount) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item->description ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">Invoiced Amount</x-slot>
        <x-slot name="description">The actual amount sent to the customer in the invoice</x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="updateInvoicedAmount" class="space-y-4">
                <div class="max-w-md">
                    <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                        Invoiced Amount
                    </label>
                    <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                        <span class="text-gray-500">$</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="invoicedAmount"
                            placeholder="Enter invoiced amount"
                            class="flex-1 bg-transparent border-0 focus:ring-0 text-sm text-gray-900 dark:text-gray-100"
                        />
                    </div>
                    @error('invoicedAmount')
                        <span class="text-xs text-rose-600 mt-1">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Current: {{ $formatCurrency($payment->invoiced_amount ?? $snapshot['expected_total']) }}
                    </p>
                </div>

                <x-filament::button type="submit" color="primary" size="sm">
                    Update Invoiced Amount
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Payment Records</x-slot>
        <x-slot name="description">All payment records for this billing period</x-slot>

        <div class="space-y-6">
            @if($paymentRecords->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                        <thead class="text-sm font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Recorded By</th>
                                <th class="px-4 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($paymentRecords as $record)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ optional($record->paid_at)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $formatCurrency($record->amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $record->recordedBy->name ?? 'Unknown' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $record->notes ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100" colspan="3">
                                    Total Received
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $formatCurrency($totalReceived) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No payment records yet.</p>
            @endif

            @if(!$payment->is_waived)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Record New Payment</p>
                    <form wire:submit.prevent="recordPayment" class="space-y-4">
                        <div class="max-w-md">
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Amount Received
                            </label>
                            <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                <span class="text-gray-500">$</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model="paymentInput.amount"
                                    placeholder="Amount"
                                    class="flex-1 bg-transparent border-0 focus:ring-0 text-sm text-gray-900 dark:text-gray-100"
                                    required
                                />
                            </div>
                            @error('paymentInput.amount')
                                <span class="text-xs text-rose-600 mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="max-w-md">
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Notes (Optional)
                            </label>
                            <textarea
                                rows="3"
                                wire:model="paymentNote"
                                placeholder="Add any notes about this payment..."
                                class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                            ></textarea>
                        </div>

                        <x-filament::button type="submit" color="primary">
                            Record Payment
                        </x-filament::button>
                    </form>
                </div>
            @endif
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Payment Status</x-slot>

        <div class="space-y-6">
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Current Status</p>
                @php
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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
                    {{ $statusLabel }}
                </span>
                @if($isOverdue)
                    <p class="mt-2 text-sm font-medium text-rose-600 dark:text-rose-400">Action needed</p>
                @elseif($isCurrentMonth && !$isPast20th)
                    <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">All good</p>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Expected Total</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($snapshot['expected_total']) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Invoiced Amount</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($payment->invoiced_amount ?? $snapshot['expected_total']) }}
                    </p>
                </div>
                @if($waivedAmount > 0)
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Waived Amount</p>
                        <p class="text-lg font-semibold text-amber-600 dark:text-amber-400">
                            -{{ $formatCurrency($waivedAmount) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Adjusted Expected</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $formatCurrency($adjustedExpected) }}
                        </p>
                    </div>
                @endif
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Received</p>
                    <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ $formatCurrency($totalReceived) }}
                    </p>
                </div>
            </div>

            @if($payment->notes)
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">Notes</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $payment->notes }}</p>
                </div>
            @endif

            @if(!$payment->is_waived)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Waive Options</p>
                    <div class="space-y-4">
                        <form wire:submit.prevent="partialWaive" class="space-y-4">
                            <div class="max-w-md">
                                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    Partial Waive Amount
                                </label>
                                <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                    <span class="text-gray-500">$</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        max="{{ $snapshot['expected_total'] - 0.01 }}"
                                        wire:model="partialWaiveAmount"
                                        placeholder="Amount to waive"
                                        class="flex-1 bg-transparent border-0 focus:ring-0 text-sm text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                @error('partialWaiveAmount')
                                    <span class="text-xs text-rose-600 mt-1">{{ $message }}</span>
                                @enderror
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Maximum: {{ $formatCurrency($snapshot['expected_total'] - 0.01) }}
                                </p>
                            </div>

                            <div class="max-w-md">
                                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    Notes (Optional)
                                </label>
                                <textarea
                                    rows="3"
                                    wire:model="waiveNote"
                                    placeholder="Add any notes about this waiver..."
                                    class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                ></textarea>
                            </div>

                            <x-filament::button type="submit" color="warning" size="sm">
                                Partial Waive
                            </x-filament::button>
                        </form>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="max-w-md mb-4">
                                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    Notes (Optional)
                                </label>
                                <textarea
                                    rows="3"
                                    wire:model="waiveNote"
                                    placeholder="Add any notes about this full waiver..."
                                    class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                ></textarea>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                Use the "Full Waive" button in the page header to fully waive this payment.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-lg">
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-1">Fully Waived</p>
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        Waived on {{ optional($payment->waived_at)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i') }}
                        @if($payment->waivedBy)
                            by {{ $payment->waivedBy->name }}
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
