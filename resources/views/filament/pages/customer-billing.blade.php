<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $nowMonth = \Carbon\Carbon::now('Asia/Shanghai')->format('F Y');
    @endphp

    <div class="grid gap-4 mb-6 md:grid-cols-3">
        <x-filament::card>
            <p class="text-sm text-gray-500">Expected ({{ $nowMonth }})</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $formatCurrency($stats['current_expected'] ?? 0) }}
            </p>
        </x-filament::card>

        <x-filament::card>
            <p class="text-sm text-gray-500">Confirmed received</p>
            <p class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-400">
                {{ $formatCurrency($stats['current_received'] ?? 0) }}
            </p>
        </x-filament::card>

        <x-filament::card :class="($stats['has_overdue'] ?? false) ? 'border border-rose-300 dark:border-rose-500' : ''">
            <p class="text-sm text-gray-500">Overdue alert</p>
            <p class="mt-2 text-3xl font-semibold {{ ($stats['has_overdue'] ?? false) ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">
                {{ ($stats['has_overdue'] ?? false) ? 'Action needed' : 'All good' }}
            </p>
        </x-filament::card>
    </div>

    <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-200">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Month</th>
                    <th class="px-4 py-3">Subnets</th>
                    <th class="px-4 py-3">Subnet total</th>
                    <th class="px-4 py-3">Other items</th>
                    <th class="px-4 py-3">Expected total</th>
                    <th class="px-4 py-3">Received</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 w-72">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($snapshots as $snapshot)
                    @php
                        /** @var \App\Models\CustomerBillingPayment $payment */
                        $payment = $snapshot['payment'];
                        $periodLabel = $snapshot['period']->format('F Y');
                        $isOverdue = $snapshot['period']->lessThan(\Carbon\Carbon::now('Asia/Shanghai')->startOfMonth()) && !$payment->is_paid;
                    @endphp
                    <tr class="{{ $isOverdue ? 'bg-rose-50/60 dark:bg-rose-950/30' : 'bg-white dark:bg-gray-900' }}">
                        <td class="px-4 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $periodLabel }}</td>
                        <td class="px-4 py-4">{{ $snapshot['subnet_count'] }}</td>
                        <td class="px-4 py-4">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                        <td class="px-4 py-4">{{ $formatCurrency($snapshot['other_total']) }}</td>
                        <td class="px-4 py-4 font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                        <td class="px-4 py-4">
                            @if ($payment->is_paid)
                                <div class="flex flex-col">
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $formatCurrency($payment->actual_amount) }}</span>
                                    <span class="text-xs text-gray-500">at {{ optional($payment->paid_at)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i') }}</span>
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $payment->is_paid ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                {{ $payment->is_paid ? 'Paid' : 'Pending' }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            @if ($payment->is_paid)
                                <div class="flex flex-wrap gap-2">
                                    <x-filament::button color="gray" wire:click="resetPayment({{ $payment->id }})">
                                        Reopen
                                    </x-filament::button>
                                </div>
                            @else
                                <div class="flex flex-col gap-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        placeholder="Amount"
                                        class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                        wire:model.defer="paymentInputs.{{ $payment->id }}"
                                    />
                                    @error('paymentInputs.' . $payment->id)
                                        <span class="text-xs text-rose-600">{{ $message }}</span>
                                    @enderror
                                    <textarea
                                        rows="2"
                                        placeholder="Notes (optional)"
                                        class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                        wire:model.defer="paymentNotes.{{ $payment->id }}"
                                    ></textarea>
                                    <x-filament::button color="primary" wire:click="recordPayment({{ $payment->id }})">
                                        Mark as paid
                                    </x-filament::button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">No billing history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
