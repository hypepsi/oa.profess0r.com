<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $previousSummary = $previousSummary ?? [];
        $topCustomers = $summary['top_customers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];

        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp

    <div class="mb-6">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Month</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $periodLabel }}</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-users" class="w-6 h-6 text-indigo-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customers to Bill</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $summary['customers_due'] ?? 0 }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6" style="color: rgb(16 185 129);" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Revenue</p>
                    <p class="mt-1 text-2xl font-semibold" style="color: rgb(5 150 105);">
                        {{ $formatCurrency($summary['expected_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-circle-stack" class="w-6 h-6" style="color: rgb(16 185 129);" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Received (Confirmed)</p>
                    <p class="mt-1 text-2xl font-semibold" style="color: rgb(5 150 105);">
                        {{ $formatCurrency($summary['received_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card style="{{ count($overdueList) ? 'border: 2px solid rgb(251 113 133); background-color: rgb(255 241 242);' : '' }}">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6" style="color: {{ count($overdueList) ? 'rgb(225 29 72)' : 'rgb(156 163 175)' }};" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue Amount</p>
                    <p class="mt-1 text-2xl font-semibold" style="color: {{ count($overdueList) ? 'rgb(225 29 72)' : 'rgb(17 24 39)' }};">
                        {{ $formatCurrency($summary['overdue_amount_total'] ?? 0) }}
                    </p>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ count($overdueList) }} customer(s)</p>
                </div>
            </div>
        </x-filament::card>
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top 3 Customers (Amount)</x-slot>
            <x-slot name="description">Highest billing amounts this month</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($topCustomers as $row)
                    <li class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $row['customer']->name }}</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Recurring charges</p>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-sm font-medium text-gray-500 dark:text-gray-400">No billing data for this month.</li>
                @endforelse
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Overdue Customers</x-slot>
            <x-slot name="description">Unpaid months before {{ $periodLabel }}</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($overdueList as $row)
                    <li class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $row['customer']->name }}</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Follow up required</p>
                        </div>
                        <span class="text-sm font-semibold" style="color: rgb(225 29 72);">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-sm font-medium text-gray-500 dark:text-gray-400">No overdue records 🎉</li>
                @endforelse
            </ul>
        </x-filament::section>
    </div>

    @if (!empty($previousSummary))
        <x-filament::section class="mt-6">
            <x-slot name="heading">{{ $previousPeriodLabel }} Summary</x-slot>
            <x-slot name="description">Previous month overview (for reference and updates)</x-slot>

            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Customers</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $previousSummary['customers_due'] ?? 0 }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Expected</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($previousSummary['expected_total'] ?? 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Received</p>
                    <p class="text-lg font-semibold" style="color: rgb(5 150 105);">
                        {{ $formatCurrency($previousSummary['received_total'] ?? 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Overdue</p>
                    <p class="text-lg font-semibold" style="color: {{ ($previousSummary['overdue_amount_total'] ?? 0) > 0 ? 'rgb(225 29 72)' : 'rgb(17 24 39)' }};">
                        {{ $formatCurrency($previousSummary['overdue_amount_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
