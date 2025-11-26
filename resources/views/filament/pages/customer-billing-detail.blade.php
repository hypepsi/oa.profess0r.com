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
    </div>

    {{-- Add-ons Details 单独一行，占满整行，高度与上面卡片一致 --}}
    @if($addOnsItems->isNotEmpty())
        <x-filament::section class="mb-6 [&>div]:p-4">
            <x-slot name="heading">Add-ons Details</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-900 dark:text-gray-100">
                    <thead class="text-sm font-semibold uppercase bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2">Title</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($addOnsItems as $item)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <a 
                                        href="/admin/billing-other-items/{{ $item->id }}/edit" 
                                        class="text-gray-900 dark:text-gray-100 hover:text-gray-700 dark:hover:text-gray-300 hover:underline"
                                    >
                                        {{ $item->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $item->effectiveStartDate()->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($item->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Invoiced Amount 单独一行，与 Add-ons Details 对齐，高度一致 --}}
    <x-filament::section class="mb-8 [&>div]:p-4">
        <x-slot name="heading">Invoiced Amount</x-slot>
        <form wire:submit.prevent="updateInvoicedAmount">
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                        Amount
                    </label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 text-sm font-medium">$</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="invoicedAmount"
                            placeholder="Enter amount"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700"
                        />
                    </div>
                    @error('invoicedAmount')
                        <span class="text-xs text-rose-600 mt-0.5 block">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Current: {{ $formatCurrency($payment->invoiced_amount ?? $snapshot['expected_total']) }}
                    </p>
                </div>
                <div class="flex items-center">
                    <x-filament::button type="submit" color="primary" size="sm">
                        Update
                    </x-filament::button>
                </div>
            </div>
        </form>
    </x-filament::section>

    {{-- 区块C：Payment Records + Record New Payment --}}
    <x-filament::section>
        <x-slot name="heading">Payment Records</x-slot>
        <div class="grid gap-6 {{ !$payment->is_waived ? 'md:grid-cols-2 md:items-start' : '' }}">
            <div>
                <div id="payment-records-table">
                    {{ $this->table }}
                </div>
                @if($paymentRecords->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Total Received</span>
                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $formatCurrency($totalReceived) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            @if(!$payment->is_waived)
                <div class="flex flex-col">
                    <p class="text-sm font-semibold uppercase text-gray-700 dark:text-gray-300 mb-3 px-4 py-3 bg-gray-50 dark:bg-gray-800">Record New Payment</p>
                    <form wire:submit.prevent="recordPayment" class="flex flex-col space-y-3" x-data="{ adjustHeight() { const table = document.getElementById('payment-records-table'); const form = $el; if (table && form) { const tableHeight = table.offsetHeight; const formHeader = form.previousElementSibling; const formHeaderHeight = formHeader ? formHeader.offsetHeight : 0; const amountInputHeight = form.querySelector('input[type=\"number\"]').offsetHeight + 24; const availableHeight = tableHeight - amountInputHeight; const textarea = form.querySelector('textarea'); if (textarea) { textarea.style.height = availableHeight + 'px'; } } } }" x-init="setTimeout(adjustHeight, 100); $watch('$wire.paymentRecords', () => setTimeout(adjustHeight, 100))">
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model="paymentInput.amount"
                                    placeholder="Amount"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700"
                                    required
                                />
                                @error('paymentInput.amount')
                                    <span class="text-xs text-rose-600 mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="flex items-center">
                                <x-filament::button type="submit" color="primary" size="sm">
                                    Record
                                </x-filament::button>
                            </div>
                        </div>
                        <div>
                            <textarea
                                wire:model="paymentNote"
                                placeholder="Optional notes..."
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 resize-none"
                            ></textarea>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- 区块D：Waive Options --}}
    @if(!$payment->is_waived)
        <x-filament::section class="[&>div]:p-4">
            <x-slot name="heading">Waive Options</x-slot>
            <form wire:submit.prevent="partialWaive">
                <div class="flex gap-3">
                    <div class="flex-1 space-y-3">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                    Partial Waive Amount
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ $snapshot['expected_total'] - 0.01 }}"
                                    wire:model="partialWaiveAmount"
                                    placeholder="Amount to waive, max={{ $formatCurrency($snapshot['expected_total'] - 0.01) }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 h-[42px]"
                                />
                                @error('partialWaiveAmount')
                                    <span class="text-xs text-rose-600 mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="flex items-center">
                                <x-filament::button type="submit" color="primary" size="md" class="h-[42px]">
                                    Partial Waive
                                </x-filament::button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                Notes
                            </label>
                            <textarea
                                rows="1"
                                wire:model="waiveNote"
                                placeholder="Optional notes..."
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </x-filament::section>
    @endif

</x-filament-panels::page>
