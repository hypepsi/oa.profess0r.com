<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $periodLabel = $snapshot['period']->format('F Y');
        $now = \Carbon\Carbon::now('Asia/Shanghai');
        $periodDay20 = $snapshot['period']->copy()->day(20);
        $isPast20th = $now->greaterThan($periodDay20);
        $isCurrentMonth = $snapshot['period']->format('Y-m') === $now->format('Y-m');
        $isOverdue = ($isCurrentMonth && $isPast20th && !$payment->is_paid && !$payment->is_waived) 
            || ($snapshot['period']->lessThan($now->startOfMonth()) && !$payment->is_paid && !$payment->is_waived);
    @endphp

    <div class="mb-6">
        <x-filament::button color="gray" icon="heroicon-o-arrow-left" href="/admin/billing/customer?customer={{ $customer->id }}">
            Back to billing
        </x-filament::button>
    </div>

    <div class="grid gap-4 mb-8 md:grid-cols-3">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-emerald-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Expected total</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($snapshot['expected_total']) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-rectangle-stack" class="w-6 h-6 text-blue-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Subnets</p>
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
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Add-ons</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($snapshot['other_total']) }}
                    </p>
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
        <x-slot name="heading">Payment Status</x-slot>

        <div class="space-y-6">
            <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Current Status</p>
                @php
                    $statusColor = $payment->is_paid ? 'emerald' : ($payment->is_waived ? 'amber' : ($isOverdue ? 'rose' : 'gray'));
                    $statusLabel = $payment->is_paid ? 'Paid' : ($payment->is_waived ? 'Waived' : ($isOverdue ? 'Overdue' : 'Pending'));
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
                    {{ $statusLabel }}
                </span>
                @if($isOverdue)
                    <p class="mt-2 text-sm font-medium text-rose-600">Action needed</p>
                @elseif($isCurrentMonth && !$isPast20th)
                    <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-400">All good</p>
                @endif
            </div>

            @if ($payment->is_paid)
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Received Amount</p>
                    <p class="text-xl font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ $formatCurrency($payment->actual_amount) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Paid on {{ optional($payment->paid_at)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i') }}
                    </p>
                    @if ($payment->notes)
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Notes</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $payment->notes }}</p>
                        </div>
                    @endif
                </div>
            @elseif ($payment->is_waived)
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Waived</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Waived on {{ optional($payment->waived_at)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i') }}
                    </p>
                    @if ($payment->notes)
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Notes</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $payment->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif

            @if (!$payment->is_paid && !$payment->is_waived)
                <div class="space-y-4">
                    <form wire:submit.prevent="recordPayment" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Amount Received
                            </label>
                            <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                <span class="text-gray-500">$</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
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

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes (optional)
                            </label>
                            <textarea
                                rows="3"
                                wire:model="paymentNote"
                                placeholder="Add any notes about this payment..."
                                class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                            ></textarea>
                        </div>

                        <div class="flex gap-3">
                            <x-filament::button type="submit" color="primary">
                                Record Payment
                            </x-filament::button>
                            <x-filament::button type="button" color="warning" wire:click="waivePayment" wire:confirm="Are you sure you want to waive this payment?">
                                Waive
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
