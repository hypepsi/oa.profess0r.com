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

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <x-filament::card>
            <p class="text-sm text-gray-500">Customers to bill</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $summary['customers_due'] ?? 0 }}
            </p>
        </x-filament::card>

        <x-filament::card>
            <p class="text-sm text-gray-500">Expected revenue</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">
                {{ $formatCurrency($summary['expected_total'] ?? 0) }}
            </p>
        </x-filament::card>

        <x-filament::card>
            <p class="text-sm text-gray-500">Received (confirmed)</p>
            <p class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-400">
                {{ $formatCurrency($summary['received_total'] ?? 0) }}
            </p>
        </x-filament::card>

        <x-filament::card>
            <p class="text-sm text-gray-500">Customers with overdue</p>
            <p class="mt-2 text-3xl font-semibold {{ count($overdueList) ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">
                {{ count($overdueList) }}
            </p>
        </x-filament::card>
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top 3 customers (amount)</x-slot>
            <x-slot name="description">Highest billing amounts this month</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($topCustomers as $row)
                    <li class="flex items-center justify-between py-3">
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $row['customer']->name }}
                        </span>
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">
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
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $row['customer']->name }}</p>
                            <p class="text-sm text-gray-500">Needs follow-up</p>
                        </div>
                        <span class="text-sm font-semibold text-rose-600 dark:text-rose-400">
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
