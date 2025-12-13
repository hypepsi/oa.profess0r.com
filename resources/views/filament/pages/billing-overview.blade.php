<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $previousSummary = $previousSummary ?? [];
        $topCustomers = $summary['top_customers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];

        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp

    {{-- Stats are now rendered by the BillingOverviewStats widget via getHeaderWidgets() --}}
    
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $periodLabel }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Income overview and customer billing status</p>
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top 3 Customers (Amount)</x-slot>
            <x-slot name="description">Highest billing amounts this month</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($topCustomers as $row)
                    <li class="flex items-center justify-between py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="oa-list-primary">{{ $row['customer']->name }}</p>
                            <p class="oa-list-secondary">Recurring charges</p>
                        </div>
                        <span class="oa-list-value text-gray-900 dark:text-gray-100 shrink-0">
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
                    <li class="flex items-center justify-between py-4 border-l-4 border-rose-500 pl-4 bg-rose-50/50 dark:bg-rose-950/20 hover:bg-rose-100 dark:hover:bg-rose-950/30 transition-colors">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="oa-list-primary">{{ $row['customer']->name }}</p>
                            <p class="oa-list-secondary text-rose-600 dark:text-rose-400">⚠️ Follow up required</p>
                        </div>
                        <span class="oa-list-value text-rose-600 dark:text-rose-400 shrink-0">
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
                    <p class="text-lg font-semibold text-sky-600 dark:text-sky-400">
                        {{ $formatCurrency($previousSummary['received_total'] ?? 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Overdue</p>
                    <p class="text-lg font-semibold {{ ($previousSummary['overdue_amount_total'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ $formatCurrency($previousSummary['overdue_amount_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
