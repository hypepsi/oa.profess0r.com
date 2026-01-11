<div class="p-6 space-y-4">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border-2 border-green-200 dark:border-green-700">
            <div class="oa-card-label mb-1 text-green-700 dark:text-green-300">Revenue</div>
            <div class="oa-card-value-lg text-green-900 dark:text-green-100">${{ number_format($record->total_revenue, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border-2 border-red-200 dark:border-red-700">
            <div class="oa-card-label mb-1 text-red-700 dark:text-red-300">Cost</div>
            <div class="oa-card-value-lg text-red-900 dark:text-red-100">${{ number_format($record->total_cost, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-200 dark:border-blue-700">
            <div class="oa-card-label mb-1 text-blue-700 dark:text-blue-300">Profit</div>
            <div class="oa-card-value-lg text-blue-900 dark:text-blue-100">${{ number_format($record->net_profit, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border-2 border-amber-200 dark:border-amber-700">
            <div class="oa-card-label mb-1 text-amber-700 dark:text-amber-300">Salary</div>
            <div class="oa-card-value-lg text-amber-900 dark:text-amber-100">${{ number_format($record->total_compensation, 2) }}</div>
        </div>
    </div>

    {{-- Breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="oa-section-heading mb-3 pb-2 border-b">Revenue Breakdown</div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="oa-body">IP Assets</span>
                    <span class="oa-detail-value">${{ number_format($record->ip_asset_revenue, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="oa-body">Other</span>
                    <span class="oa-detail-value">${{ number_format($record->other_income, 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t">
                    <span class="oa-body font-semibold">Total</span>
                    <span class="oa-detail-value text-green-600 dark:text-green-400">${{ number_format($record->total_revenue, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Cost --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="oa-section-heading mb-3 pb-2 border-b">Cost Breakdown</div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="oa-body">IP Direct Cost</span>
                    <span class="oa-detail-value">${{ number_format($record->ip_direct_cost, 2) }}</span>
                </div>
                <div>
                    <div class="flex justify-between">
                        <span class="oa-body">Shared Cost</span>
                        <span class="oa-detail-value">${{ number_format($record->shared_cost, 2) }}</span>
                    </div>
                    <div class="oa-helper pl-2 mt-1">
                        {{ number_format($record->shared_cost_ratio * 100, 1) }}% ({{ $record->active_subnet_count }}/{{ $record->total_subnet_count }} subnets)
                    </div>
                </div>
                <div class="flex justify-between pt-2 border-t">
                    <span class="oa-body font-semibold">Total</span>
                    <span class="oa-detail-value text-red-600 dark:text-red-400">${{ number_format($record->total_cost, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Salary --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="oa-section-heading mb-3 pb-2 border-b">Salary Calculation</div>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="oa-body">Profit</span>
                <span class="oa-detail-value">${{ number_format($record->net_profit, 2) }}</span>
            </div>
            @if($record->workflow_deductions > 0)
            <div>
                <div class="flex justify-between">
                    <span class="oa-body">Deduction</span>
                    <span class="oa-detail-value text-red-600 dark:text-red-400">-${{ number_format($record->workflow_deductions, 2) }}</span>
                </div>
                <div class="oa-helper pl-2 mt-1">
                    {{ $record->overdue_workflow_count }} overdue tasks
                </div>
            </div>
            @endif
            <div class="flex justify-between">
                <span class="oa-body">Commission ({{ number_format($record->commission_rate * 100, 1) }}%)</span>
                <span class="oa-detail-value">${{ number_format($record->commission_amount, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="oa-body">Base Salary</span>
                <span class="oa-detail-value">${{ number_format($record->base_salary, 2) }}</span>
            </div>
            <div class="flex justify-between pt-2 border-t">
                <span class="oa-body font-semibold">Total Salary</span>
                <span class="oa-detail-value text-amber-600 dark:text-amber-400">${{ number_format($record->total_compensation, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="oa-section-heading mb-3 pb-2 border-b">Statistics</div>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="oa-card-label mb-1">Active Subnets</div>
                <div class="oa-card-value-lg">{{ $record->active_subnet_count }}</div>
                <div class="oa-helper">/ {{ $record->total_subnet_count }}</div>
            </div>
            <div>
                <div class="oa-card-label mb-1">Customers</div>
                <div class="oa-card-value-lg">{{ $record->active_customer_count }}</div>
            </div>
            <div>
                <div class="oa-card-label mb-1">Subnet Ratio</div>
                <div class="oa-card-value-lg">{{ $record->total_subnet_count > 0 ? number_format($record->active_subnet_count / $record->total_subnet_count * 100, 1) : 0 }}%</div>
            </div>
        </div>
    </div>

    @if($record->notes)
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-200 dark:border-blue-700">
        <div class="oa-subheading mb-1 text-blue-900 dark:text-blue-100">Notes</div>
        <div class="oa-description">{{ $record->notes }}</div>
    </div>
    @endif

    <div class="oa-footer pt-2 border-t">
        Calculated at: {{ $record->calculated_at?->format('Y-m-d H:i:s') }}
    </div>
</div>
