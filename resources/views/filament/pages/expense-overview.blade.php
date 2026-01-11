<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $previousSummary = $previousSummary ?? [];
        $topProviders = $summary['top_providers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];

        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp


    {{-- Stats are now rendered by the ExpenseOverviewStats widget via getHeaderWidgets() --}}
    
    <div class="mb-6">
        <h2 class="oa-card-value">{{ $periodLabel }}</h2>
        <p class="oa-subheading">Expense overview and provider payment status</p>
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top 3 Providers (Amount)</x-slot>
            <x-slot name="description">Highest expense amounts this month</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($topProviders as $row)
                    <li class="flex items-center justify-between py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="oa-list-primary">{{ $row['provider']->name }}</p>
                            <p class="oa-list-secondary">
                                {{ $row['provider_type'] === 'App\\Models\\Provider' ? 'IP Provider' : 'IPT Provider' }}
                            </p>
                        </div>
                        <span class="oa-list-value shrink-0">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-4 oa-list-secondary text-center">No expense data for this month.</li>
                @endforelse
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Overdue Providers</x-slot>
            <x-slot name="description">Unpaid months before {{ $periodLabel }}</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($overdueList as $row)
                    <li class="flex items-center justify-between py-4 border-l-4 border-rose-500 pl-4 bg-rose-50/50 dark:bg-rose-950/20 hover:bg-rose-100 dark:hover:bg-rose-950/30 transition-colors">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="oa-list-primary">{{ $row['provider']->name }}</p>
                            <p class="oa-list-secondary" style="color: rgb(220 38 38); font-weight: 700;">⚠️ Follow up required</p>
                        </div>
                        <span class="oa-list-value shrink-0" style="color: rgb(220 38 38); font-weight: 700;">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-4 oa-list-secondary text-center text-emerald-600 dark:text-emerald-400">✓ No overdue records</li>
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
                    <p class="oa-card-label">Providers</p>
                    <p class="oa-card-value">
                        {{ $previousSummary['providers_due'] ?? 0 }}
                    </p>
                </div>
                <div>
                    <p class="oa-card-label">Expected</p>
                    <p class="oa-card-value">
                        {{ $formatCurrency($previousSummary['expected_total'] ?? 0) }}
                    </p>
                </div>
                <div>
                    <p class="oa-card-label">Paid</p>
                    <p class="oa-card-value" style="color: rgb(22 101 52);">
                        {{ $formatCurrency($previousSummary['paid_total'] ?? 0) }}
                    </p>
                </div>
                <div>
                    <p class="oa-card-label">Overdue</p>
                    <p class="oa-card-value" style="color: {{ ($previousSummary['overdue_amount_total'] ?? 0) > 0 ? 'rgb(220 38 38)' : 'rgb(17 24 39)' }};">
                        {{ $formatCurrency($previousSummary['overdue_amount_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

