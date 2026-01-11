<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $previousSummary = $previousSummary ?? [];
        $topCustomers = $summary['top_customers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];
        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp

    {{-- Stats are now rendered by the IncomeOverviewStats widget via getHeaderWidgets() --}}
    
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $periodLabel }}</h1>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Income overview and customer billing status</p>
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
