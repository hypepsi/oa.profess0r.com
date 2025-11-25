<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $topCustomers = $summary['top_customers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];

        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp

    <div class="flex flex-col gap-2 mb-6 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm text-gray-500">Natural month</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $periodLabel }}</p>
        </div>

        <x-filament::button icon="heroicon-o-arrow-path" wire:click="refreshSummary">
            Refresh
        </x-filament::button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-users" class="w-6 h-6 text-indigo-500" />
                <div>
                    <p class="text-sm text-gray-500">Customers to bill</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $summary['customers_due'] ?? 0 }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-emerald-500" />
                <div>
                    <p class="text-sm text-gray-500">Expected revenue</p>
                    <p class="mt-1 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ $formatCurrency($summary['expected_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-circle-stack" class="w-6 h-6 text-sky-500" />
                <div>
                    <p class="text-sm text-gray-500">Received (confirmed)</p>
                    <p class="mt-1 text-3xl font-semibold text-sky-600 dark:text-sky-400">
                        {{ $formatCurrency($summary['received_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card :class="count($overdueList) ? 'border border-rose-200 dark:border-rose-500 bg-rose-50 dark:bg-rose-950/40' : ''">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 {{ count($overdueList) ? 'text-rose-600' : 'text-gray-400' }}" />
                <div>
                    <p class="text-sm text-gray-500">Overdue amount</p>
                    <p class="mt-1 text-2xl font-semibold {{ count($overdueList) ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ $formatCurrency($summary['overdue_amount_total'] ?? 0) }}
                    </p>
                    <p class="text-xs text-gray-500">{{ count($overdueList) }} customer(s)</p>
                </div>
            </div>
        </x-filament::card>
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top 3 customers (amount)</x-slot>
            <x-slot name="description">Highest billing amounts this month</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($topCustomers as $row)
                    <li class="flex items-center justify-between py-4">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['customer']->name }}</p>
                            <p class="text-xs text-gray-500">Recurring charges</p>
                        </div>
                        <span class="text-base font-semibold text-gray-700 dark:text-gray-200">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-sm text-gray-500">No billing data for this month.</li>
                @endforelse
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Overdue customers</x-slot>
            <x-slot name="description">Unpaid months before {{ $periodLabel }}</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($overdueList as $row)
                    <li class="flex items-center justify-between py-4">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['customer']->name }}</p>
                            <p class="text-xs text-gray-500">Follow up required</p>
                        </div>
                        <span class="text-base font-semibold text-rose-600 dark:text-rose-400">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-sm text-gray-500">No overdue records 🎉</li>
                @endforelse
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
