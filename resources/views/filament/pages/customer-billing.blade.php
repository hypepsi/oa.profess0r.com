<x-filament-panels::page>
    @php
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $nowMonth = \Carbon\Carbon::now('Asia/Shanghai')->format('F Y');
    @endphp

    <div class="grid gap-4 mb-8 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-emerald-500" />
                <div>
                    <p class="text-sm text-gray-500">Expected ({{ $nowMonth }})</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($stats['current_expected'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-check-badge" class="w-6 h-6 text-sky-500" />
                <div>
                    <p class="text-sm text-gray-500">Confirmed received</p>
                    <p class="mt-1 text-3xl font-semibold text-sky-600 dark:text-sky-400">
                        {{ $formatCurrency($stats['current_received'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-hand-raised" class="w-6 h-6 text-amber-500" />
                <div>
                    <p class="text-sm text-gray-500">Waived totals</p>
                    <p class="mt-1 text-3xl font-semibold text-amber-600 dark:text-amber-400">
                        {{ $formatCurrency($stats['waived_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card :class="($stats['has_overdue'] ?? false) ? 'border border-rose-200 dark:border-rose-500 bg-rose-50 dark:bg-rose-950/40' : ''">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 {{ ($stats['has_overdue'] ?? false) ? 'text-rose-600' : 'text-gray-400' }}" />
                <div>
                    <p class="text-sm text-gray-500">Overdue alert</p>
                    <p class="mt-1 text-2xl font-semibold {{ ($stats['has_overdue'] ?? false) ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ ($stats['has_overdue'] ?? false) ? 'Action needed' : 'All clear' }}
                    </p>
                    @if(($stats['overdue_count'] ?? 0) > 0)
                        <p class="text-xs text-rose-600">{{ $stats['overdue_count'] }} month(s) pending</p>
                    @endif
                </div>
            </div>
        </x-filament::card>
    </div>

    <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-200">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Month</th>
                    <th class="px-4 py-3 text-center">Subnets</th>
                    <th class="px-4 py-3">Subnet total</th>
                    <th class="px-4 py-3">Add-ons</th>
                    <th class="px-4 py-3">Expected</th>
                    <th class="px-4 py-3">Received / Notes</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 w-64">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($snapshots as $snapshot)
                    @php
                        /** @var \App\Models\CustomerBillingPayment $payment */
                        $payment = $snapshot['payment'];
                        $periodLabel = $snapshot['period']->format('F Y');
                        $isOverdue = $snapshot['period']->lessThan(\Carbon\Carbon::now('Asia/Shanghai')->startOfMonth()) && !$payment->is_paid && !$payment->is_waived;
                        $rowClasses = $isOverdue ? 'bg-rose-50/60 dark:bg-rose-950/30' : 'bg-white dark:bg-gray-900';
                    @endphp
                    <tr class="{{ $rowClasses }}">
                        <td class="px-4 py-4">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $periodLabel }}</div>
                            <div class="text-xs text-gray-500">{{ $snapshot['period']->format('D, M d') }}</div>
                        </td>
                        <td class="px-4 py-4 text-center">{{ $snapshot['subnet_count'] }}</td>
                        <td class="px-4 py-4">{{ $formatCurrency($snapshot['subnet_total']) }}</td>
                        <td class="px-4 py-4">{{ $formatCurrency($snapshot['other_total']) }}</td>
                        <td class="px-4 py-4 font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($snapshot['expected_total']) }}</td>
                        <td class="px-4 py-4">
                            @if ($payment->is_paid)
                                <div class="space-y-1">
                                    <p class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $formatCurrency($payment->actual_amount) }}</p>
                                    <p class="text-xs text-gray-500">Paid {{ optional($payment->paid_at)->setTimezone('Asia/Shanghai')->format('Y-m-d') }}</p>
                                </div>
                            @elseif($payment->is_waived)
                                <div class="space-y-1">
                                    <p class="font-semibold text-amber-600">Waived</p>
                                    <p class="text-xs text-gray-500">on {{ optional($payment->waived_at)->setTimezone('Asia/Shanghai')->format('Y-m-d') }}</p>
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $statusColor = $payment->is_paid ? 'emerald' : ($payment->is_waived ? 'amber' : ($isOverdue ? 'rose' : 'gray'));
                                $statusLabel = $payment->is_paid ? 'Paid' : ($payment->is_waived ? 'Waived' : ($isOverdue ? 'Overdue' : 'Pending'));
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/40 dark:text-{{ $statusColor }}-300">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                @if (!$payment->is_paid && !$payment->is_waived)
                                    <x-filament::button size="xs" color="primary" wire:click="startEditing({{ $payment->id }})">
                                        Record payment
                                    </x-filament::button>
                                    <x-filament::button size="xs" color="warning" wire:click="waivePayment({{ $payment->id }})">
                                        Waive
                                    </x-filament::button>
                                @else
                                    <x-filament::button size="xs" color="gray" wire:click="resetPayment({{ $payment->id }})">
                                        Reopen
                                    </x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="secondary" wire:click="toggleDetails({{ $payment->id }})">
                                    Details
                                </x-filament::button>
                            </div>
                        </td>
                    </tr>
                    @if ($detailsPaymentId === $payment->id)
                        <tr class="bg-gray-50 dark:bg-gray-800/60">
                            <td colspan="8" class="px-6 pb-6">
                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold text-gray-500 uppercase">Monthly breakdown</p>
                                        <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1">
                                            <li class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-1">
                                                <span>Subnets</span>
                                                <span>{{ $formatCurrency($snapshot['subnet_total']) }}</span>
                                            </li>
                                            <li class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-1">
                                                <span>Add-ons</span>
                                                <span>{{ $formatCurrency($snapshot['other_total']) }}</span>
                                            </li>
                                            <li class="flex justify-between font-semibold">
                                                <span>Total</span>
                                                <span>{{ $formatCurrency($snapshot['expected_total']) }}</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="space-y-3">
                                        <p class="text-xs font-semibold text-gray-500 uppercase">Notes</p>
                                        @if (($payment->notes ?? null) !== null)
                                            <div class="p-3 text-sm bg-white border rounded-lg shadow-sm dark:bg-gray-900 dark:border-gray-700">
                                                {{ $payment->notes }}
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-400">No notes yet.</p>
                                        @endif
                                    </div>

                                    <div class="space-y-3">
                                        @if ($editingPaymentId === $payment->id && !$payment->is_paid && !$payment->is_waived)
                                            <p class="text-xs font-semibold text-gray-500 uppercase">Record payment</p>
                                            <form wire:submit.prevent="recordPayment({{ $payment->id }})" class="space-y-2">
                                                <div class="fi-input flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-900 dark:border-gray-700">
                                                    <span class="text-gray-500">$</span>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        wire:model.defer="paymentInputs.{{ $payment->id }}"
                                                        placeholder="Amount"
                                                        class="flex-1 bg-transparent border-0 focus:ring-0 text-sm text-gray-900 dark:text-gray-100"
                                                    />
                                                </div>
                                                @error('paymentInputs.' . $payment->id)
                                                    <span class="text-xs text-rose-600">{{ $message }}</span>
                                                @enderror
                                                <textarea
                                                    rows="2"
                                                    wire:model.defer="paymentNotes.{{ $payment->id }}"
                                                    placeholder="Notes (optional)"
                                                    class="fi-input block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                                ></textarea>
                                                <div class="flex gap-2">
                                                    <x-filament::button type="submit" color="primary" size="sm">
                                                        Save
                                                    </x-filament::button>
                                                    <x-filament::button type="button" color="gray" size="sm" wire:click="cancelEditing">
                                                        Cancel
                                                    </x-filament::button>
                                                </div>
                                            </form>
                                        @elseif(!$payment->is_paid && !$payment->is_waived)
                                            <p class="text-xs font-semibold text-gray-500 uppercase">Quick actions</p>
                                            <div class="flex gap-2">
                                                <x-filament::button size="sm" color="primary" wire:click="startEditing({{ $payment->id }})">
                                                    Record payment
                                                </x-filament::button>
                                                <x-filament::button size="sm" color="warning" wire:click="waivePayment({{ $payment->id }})">
                                                    Waive
                                                </x-filament::button>
                                            </div>
                                        @else
                                            <p class="text-xs font-semibold text-gray-500 uppercase">Status</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-200">
                                                {{ $payment->is_paid ? 'Payment confirmed' : 'Waived intentionally' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">No billing history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
