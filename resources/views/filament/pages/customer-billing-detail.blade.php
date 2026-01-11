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

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span class="text-base font-medium">Status</span>
                @if($isOverdue)
                    <span class="ml-2 inline-flex items-center rounded-full bg-danger-100 px-2.5 py-0.5 text-xs font-medium text-danger-800 dark:bg-danger-900/40 dark:text-danger-300">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mr-1 h-4 w-4" />
                        Overdue
                    </span>
                @endif
            </div>
        </x-slot>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-2xl font-bold text-{{ $paymentStatus === 'paid' ? 'success' : ($isOverdue ? 'danger' : 'gray') }}-600 dark:text-{{ $paymentStatus === 'paid' ? 'success' : ($isOverdue ? 'danger' : 'gray') }}-400">
                {{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}
            </p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @if($paymentStatus === 'paid')
                    Payment completed successfully
                @elseif($paymentStatus === 'partial_paid')
                    Partial payment received
                @elseif($paymentStatus === 'waived')
                    Payment has been waived
                @elseif($isOverdue)
                    Payment is overdue
                @else
                    Awaiting payment
                @endif
            </p>
        </div>
    </x-filament::section>

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

    {{-- 账单详情区域 --}}
    <div class="grid gap-6 mb-6 md:grid-cols-2">
        @if($addOnsItems->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Add-ons Details</x-slot>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-left">
                        <thead class="text-xs font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3.5">Title</th>
                                <th class="px-4 py-3.5 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($addOnsItems as $item)
                                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="text-sm font-medium text-gray-900 dark:text-gray-100 px-4 py-3.5">{{ $item->title }}</td>
                                    <td class="text-sm font-medium text-gray-900 dark:text-gray-100 px-4 py-3.5 text-right whitespace-nowrap">{{ $formatCurrency($item->amount) }}</td>
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
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2 block">
                            Amount
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2.5 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                <span class="text-gray-500 text-sm">$</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model="invoicedAmount"
                                    placeholder="Enter amount"
                                    class="flex-1 bg-transparent border-0 focus:ring-0 text-sm"
                                />
                            </div>
                            <x-filament::button type="submit" color="primary" class="shrink-0">
                                Update
                            </x-filament::button>
                        </div>
                        @error('invoicedAmount')
                            <span class="text-xs text-rose-600 mt-1.5 block">{{ $message }}</span>
                        @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                            Current: {{ $formatCurrency($payment->invoiced_amount ?? $snapshot['expected_total']) }}
                        </p>
                    </div>
                </div>
            </form>
        </x-filament::section>
    </div>

    {{-- 区块C：Payment Records + Record New Payment --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Payment Records</x-slot>
        <div class="grid gap-6 {{ !$payment->is_waived ? 'md:grid-cols-2' : '' }}">
            <div>
                @if($paymentRecords->isNotEmpty())
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left">
                            <thead class="text-xs font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3.5">Date</th>
                                    <th class="px-4 py-3.5">Amount</th>
                                    <th class="px-4 py-3.5">Recorded By</th>
                                    <th class="px-4 py-3.5 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($paymentRecords as $record)
                                    <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="text-sm font-medium text-gray-900 dark:text-gray-100 px-4 py-3.5 whitespace-nowrap">
                                            {{ optional($record->paid_at)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="text-sm font-medium text-emerald-600 dark:text-emerald-400 px-4 py-3.5 whitespace-nowrap">
                                            {{ $formatCurrency($record->amount) }}
                                        </td>
                                        <td class="text-sm text-gray-600 dark:text-gray-400 px-4 py-3.5">
                                            {{ $record->recordedBy->name ?? 'Unknown' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <x-filament::button
                                                wire:click="deletePaymentRecord({{ $record->id }})"
                                                wire:confirm="Are you sure you want to delete this payment record of {{ $formatCurrency($record->amount) }}? This action cannot be undone."
                                                color="danger"
                                                size="xs"
                                                icon="heroicon-o-trash"
                                            >
                                                Delete
                                            </x-filament::button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-800 border-t-2 border-gray-200 dark:border-gray-700">
                                <tr>
                                    <td class="text-sm font-medium text-gray-900 dark:text-gray-100 px-4 py-3.5" colspan="3">
                                        Total Received
                                    </td>
                                    <td class="text-sm font-medium text-emerald-600 dark:text-emerald-400 px-4 py-3.5 text-center whitespace-nowrap">
                                        {{ $formatCurrency($totalReceived) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 px-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No payment records yet.</p>
                    </div>
                @endif
            </div>

            @if(!$payment->is_waived)
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Record New Payment</p>
                    <form wire:submit.prevent="recordPayment" class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2 block">
                                Amount
                            </label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2.5 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                    <span class="text-gray-500 text-sm">$</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        wire:model="paymentInput.amount"
                                        placeholder="Amount"
                                        class="flex-1 bg-transparent border-0 focus:ring-0 text-sm"
                                        required
                                    />
                                </div>
                                <x-filament::button type="submit" color="primary" class="shrink-0">
                                    Record
                                </x-filament::button>
                            </div>
                            @error('paymentInput.amount')
                                <span class="text-xs text-rose-600 mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Notes
                            </label>
                            <textarea
                                rows="2"
                                wire:model="paymentNote"
                                placeholder="Optional notes..."
                                class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700 text-sm px-3 py-2.5"
                            ></textarea>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- 区块D：Waive Options --}}
    @if(!$payment->is_waived)
        <x-filament::section class="mt-6">
            <x-slot name="heading">Waive Options</x-slot>
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <form wire:submit.prevent="partialWaive" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Partial Waive Amount
                            </label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2.5 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
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
                                <x-filament::button type="submit" color="warning" class="shrink-0">
                                    Partial Waive
                                </x-filament::button>
                            </div>
                            @error('partialWaiveAmount')
                                <span class="text-xs text-rose-600 mt-1.5 block">{{ $message }}</span>
                            @enderror
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                                Max: {{ $formatCurrency($snapshot['expected_total'] - 0.01) }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Notes
                            </label>
                            <textarea
                                rows="2"
                                wire:model="waiveNote"
                                placeholder="Optional notes..."
                                class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700 text-sm px-3 py-2.5"
                            ></textarea>
                        </div>
                    </form>
                </div>
                <div class="flex items-center justify-center p-6 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                        Use the "Full Waive" button in the page header to fully waive this payment.
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
