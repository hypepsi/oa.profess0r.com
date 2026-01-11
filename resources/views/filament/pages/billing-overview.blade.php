<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $previousSummary = $previousSummary ?? [];
        $topCustomers = $summary['top_customers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];

        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp

    <div class="mb-6">
        <p class="oa-subheading">Current Month</p>
        <p class="oa-page-title">{{ $periodLabel }}</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-users" class="w-6 h-6 text-indigo-500" />
                <div>
                    <p class="oa-subheading">Customers to Bill</p>
                    <p class="oa-card-value-lg mt-1">
                        {{ $summary['customers_due'] ?? 0 }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6" style="color: rgb(22 101 52);" />
                <div>
                    <p class="oa-subheading">Expected Revenue</p>
                    <p class="oa-card-value-lg mt-1" style="color: rgb(22 101 52);">
                        {{ $formatCurrency($summary['expected_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-circle-stack" class="w-6 h-6" style="color: rgb(22 101 52);" />
                <div>
                    <p class="oa-subheading">Received (Confirmed)</p>
                    <p class="oa-card-value-lg mt-1" style="color: rgb(22 101 52);">
                        {{ $formatCurrency($summary['received_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card style="{{ count($overdueList) ? 'border: 3px solid rgb(220 38 38); background-color: rgb(254 226 226);' : '' }}">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6" style="color: {{ count($overdueList) ? 'rgb(220 38 38)' : 'rgb(156 163 175)' }};" />
                <div>
                    <p class="oa-subheading">Overdue Amount</p>
                    <p class="oa-card-value-lg mt-1" style="color: {{ count($overdueList) ? 'rgb(220 38 38)' : 'rgb(17 24 39)' }};">
                        {{ $formatCurrency($summary['overdue_amount_total'] ?? 0) }}
                    </p>
                    <p class="oa-helper">{{ count($overdueList) }} customer(s)</p>
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
                            <p class="oa-list-primary">{{ $row['customer']->name }}</p>
                            <p class="oa-list-secondary">Recurring charges</p>
                        </div>
                        <span class="oa-list-value">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 oa-body">No billing data for this month.</li>
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
                            <p class="oa-list-primary">{{ $row['customer']->name }}</p>
                            <p class="oa-list-secondary">Follow up required</p>
                        </div>
                        <span class="oa-list-value" style="color: rgb(220 38 38);">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 oa-body">No overdue records 🎉</li>
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
                    <p class="oa-card-label">Customers</p>
                    <p class="oa-card-value">
                        {{ $previousSummary['customers_due'] ?? 0 }}
                    </p>
                </div>
                <div>
                    <p class="oa-card-label">Expected</p>
                    <p class="oa-card-value">
                        {{ $formatCurrency($previousSummary['expected_total'] ?? 0) }}
                    </p>
                </div>
                <div>
                    <p class="oa-card-label">Received</p>
                    <p class="oa-card-value" style="color: rgb(22 101 52);">
                        {{ $formatCurrency($previousSummary['received_total'] ?? 0) }}
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
