<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $previousSummary = $previousSummary ?? [];
        $topCustomers = $summary['top_customers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp

    {{-- Page Header --}}
    <div class="mb-6">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Current Month</p>
        <h1 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $periodLabel }}</h1>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-filament::card class="p-4">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-users" class="mt-0.5 h-5 w-5 text-primary-500" />
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Customers to Bill</p>
                    <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $summary['customers_due'] ?? 0 }}</p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card class="p-4">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="mt-0.5 h-5 w-5 text-success-500" />
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Expected Revenue</p>
                    <p class="mt-1 text-xl font-semibold text-success-600 dark:text-success-400">{{ $formatCurrency($summary['expected_total'] ?? 0) }}</p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card class="p-4">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-check-circle" class="mt-0.5 h-5 w-5 text-success-500" />
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Received</p>
                    <p class="mt-1 text-xl font-semibold text-success-600 dark:text-success-400">{{ $formatCurrency($summary['received_total'] ?? 0) }}</p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card class="p-4 {{ count($overdueList) ? 'ring-2 ring-danger-500' : '' }}">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 {{ count($overdueList) ? 'text-danger-500' : 'text-gray-400' }}" />
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Overdue Amount</p>
                    <p class="mt-1 text-xl font-semibold {{ count($overdueList) ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white' }}">
                        {{ $formatCurrency($summary['overdue_amount_total'] ?? 0) }}
                    </p>
                    @if(count($overdueList) > 0)
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ count($overdueList) }} customer(s)</p>
                    @endif
                </div>
            </div>
        </x-filament::card>
    </div>

    {{-- Top Customers & Overdue --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">
                <span class="text-sm font-medium">Top 3 Customers</span>
            </x-slot>
            <x-slot name="description">
                <span class="text-xs">Highest billing amounts this month</span>
            </x-slot>

            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($topCustomers as $row)
                    <li class="flex items-center justify-between py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['customer']->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Recurring charges</p>
                        </div>
                        <span class="ml-4 text-sm font-medium tabular-nums text-gray-900 dark:text-white">{{ $formatCurrency($row['amount']) }}</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">No billing data for this month.</li>
                @endforelse
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <span class="text-sm font-medium">Overdue Customers</span>
            </x-slot>
            <x-slot name="description">
                <span class="text-xs">Unpaid months before {{ $periodLabel }}</span>
            </x-slot>

            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($overdueList as $row)
                    <li class="flex items-center justify-between py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['customer']->name }}</p>
                            <p class="text-xs text-danger-600 dark:text-danger-400">Follow up required</p>
                        </div>
                        <span class="ml-4 text-sm font-medium tabular-nums text-danger-600 dark:text-danger-400">{{ $formatCurrency($row['amount']) }}</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">No overdue records 🎉</li>
                @endforelse
            </ul>
        </x-filament::section>
    </div>

    {{-- Previous Month Summary --}}
    @if (!empty($previousSummary))
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <span class="text-sm font-medium">{{ $previousPeriodLabel }} Summary</span>
            </x-slot>
            <x-slot name="description">
                <span class="text-xs">Previous month overview</span>
            </x-slot>

            <dl class="grid gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Customers</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $previousSummary['customers_due'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Expected</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $formatCurrency($previousSummary['expected_total'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Received</dt>
                    <dd class="mt-1 text-lg font-semibold text-success-600 dark:text-success-400">{{ $formatCurrency($previousSummary['received_total'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Overdue</dt>
                    <dd class="mt-1 text-lg font-semibold {{ ($previousSummary['overdue_amount_total'] ?? 0) > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white' }}">
                        {{ $formatCurrency($previousSummary['overdue_amount_total'] ?? 0) }}
                    </dd>
                </div>
            </dl>
        </x-filament::section>
    @endif
</x-filament-panels::page>
