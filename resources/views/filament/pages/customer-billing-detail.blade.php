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

    {{-- 区块A：统计卡片一行 --}}
    <div class="grid gap-4 mb-8" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
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
                    @endif
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

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-rectangle-stack" class="w-6 h-6 text-blue-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Subnets</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $snapshot['subnet_count'] }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $formatCurrency($snapshot['subnet_total']) }}</p>
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
    </div>

    {{-- 账单详情区域 --}}
    <div class="grid gap-6 mb-8 md:grid-cols-2">
        @if($addOnsItems->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Add-ons Details</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                        <thead class="text-sm font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($addOnsItems as $item)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->title }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($item->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">Invoiced Amount</x-slot>
            <form wire:submit.prevent="updateInvoicedAmount">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                            Amount
                        </label>
                        <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                            <span class="text-gray-500">$</span>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model="invoicedAmount"
                                placeholder="Enter amount"
                                class="flex-1 bg-transparent border-0 focus:ring-0 text-sm text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        @error('invoicedAmount')
                            <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Current: {{ $formatCurrency($payment->invoiced_amount ?? $snapshot['expected_total']) }}
                        </p>
                    </div>
                    <x-filament::button type="submit" color="primary">
                        Update
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>

    {{-- 区块C：Payment Records + Record New Payment --}}
    <x-filament::section>
        <x-slot name="heading">Payment Records</x-slot>
        <div class="grid gap-6 {{ !$payment->is_waived ? 'md:grid-cols-2' : '' }}">
            <div>
                @if($paymentRecords->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                            <thead class="text-sm font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Amount</th>
                                    <th class="px-4 py-3">Recorded By</th>
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
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100" colspan="2">
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
            </div>

            @if(!$payment->is_waived)
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Record New Payment</p>
                    <form wire:submit.prevent="recordPayment" class="space-y-3">
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                    Amount
                                </label>
                                <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-2 py-1.5 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                    <span class="text-gray-500 text-sm">$</span>
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
                                    <span class="text-xs text-rose-600 mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <x-filament::button type="submit" color="primary" size="sm">
                                Record
                            </x-filament::button>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                Notes
                            </label>
                            <textarea
                                rows="1"
                                wire:model="paymentNote"
                                placeholder="Optional notes..."
                                class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700 text-sm"
                            ></textarea>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- 区块D：Waive Options --}}
    @if(!$payment->is_waived)
        <x-filament::section>
            <x-slot name="heading">Waive Options</x-slot>
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <form wire:submit.prevent="partialWaive" class="space-y-3">
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                    Partial Waive Amount
                                </label>
                                <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-2 py-1.5 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                    <span class="text-gray-500 text-sm">$</span>
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
                                    <span class="text-xs text-rose-600 mt-0.5 block">{{ $message }}</span>
                                @enderror
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Max: {{ $formatCurrency($snapshot['expected_total'] - 0.01) }}
                                </p>
                            </div>
                            <x-filament::button type="submit" color="warning" size="sm">
                                Partial Waive
                            </x-filament::button>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                Notes
                            </label>
                            <textarea
                                rows="1"
                                wire:model="waiveNote"
                                placeholder="Optional notes..."
                                class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700 text-sm"
                            ></textarea>
                        </div>
                    </form>
                </div>
                <div class="flex items-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Use the "Full Waive" button in the page header to fully waive this payment.
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
