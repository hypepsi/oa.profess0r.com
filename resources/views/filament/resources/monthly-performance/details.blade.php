<div class="p-6 space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
            <div class="text-xs font-medium text-green-600 dark:text-green-400 mb-1">💰 Total Revenue</div>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">${{ number_format($record->total_revenue, 2) }}</div>
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
            <div class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">💸 Total Cost</div>
            <div class="text-2xl font-bold text-red-700 dark:text-red-300">${{ number_format($record->total_cost, 2) }}</div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <div class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-1">📊 Net Profit</div>
            <div class="text-2xl font-bold {{ $record->net_profit >= 0 ? 'text-blue-700 dark:text-blue-300' : 'text-red-700 dark:text-red-300' }}">${{ number_format($record->net_profit, 2) }}</div>
        </div>

        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1">💵 Total Salary</div>
            <div class="text-2xl font-bold text-amber-700 dark:text-amber-300">${{ number_format($record->total_compensation, 2) }}</div>
        </div>
    </div>

    {{-- Detailed Breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Revenue Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Revenue Breakdown</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">IP Assets</span>
                    <span class="font-mono font-medium">${{ number_format($record->ip_asset_revenue, 2) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Other Income</span>
                    <span class="font-mono font-medium">${{ number_format($record->other_income, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 pt-3 border-t-2 border-green-200 dark:border-green-800">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">Total</span>
                    <span class="font-mono font-bold text-green-600 dark:text-green-400">${{ number_format($record->total_revenue, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Cost Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Cost Breakdown</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">IP Direct Cost</span>
                    <span class="font-mono font-medium">${{ number_format($record->ip_direct_cost, 2) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Shared Cost</span>
                    <span class="font-mono font-medium">${{ number_format($record->shared_cost, 2) }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 pl-4 py-1">
                    ({{ number_format($record->shared_cost_ratio * 100, 2) }}% = {{ $record->active_subnet_count }}/{{ $record->total_subnet_count }} subnets)
                </div>
                <div class="flex justify-between py-2 pt-3 border-t-2 border-red-200 dark:border-red-800">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">Total</span>
                    <span class="font-mono font-bold text-red-600 dark:text-red-400">${{ number_format($record->total_cost, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Salary Calculation --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Salary Calculation</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Net Profit</span>
                <span class="font-mono font-medium">${{ number_format($record->net_profit, 2) }}</span>
            </div>
            @if($record->workflow_deductions > 0)
            <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Workflow Deductions <span class="text-xs">({{ $record->overdue_workflow_count }} overdue)</span></span>
                <span class="font-mono font-medium text-red-600">-${{ number_format($record->workflow_deductions, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Commission ({{ number_format($record->commission_rate * 100, 1) }}%)</span>
                <span class="font-mono font-medium">${{ number_format($record->commission_amount, 2) }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Base Salary</span>
                <span class="font-mono font-medium">${{ number_format($record->base_salary, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 pt-3 border-t-2 border-amber-200 dark:border-amber-800">
                <span class="font-semibold text-gray-900 dark:text-gray-100">Total Compensation</span>
                <span class="font-mono font-bold text-amber-600 dark:text-amber-400">${{ number_format($record->total_compensation, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Statistics</h3>
        <div class="grid grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Active Subnets</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $record->active_subnet_count }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">/ {{ $record->total_subnet_count }} total</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Active Customers</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $record->active_customer_count }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Subnet Ratio</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $record->total_subnet_count > 0 ? number_format($record->active_subnet_count / $record->total_subnet_count * 100, 1) : 0 }}%</div>
            </div>
        </div>
    </div>

    @if($record->notes)
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 p-4">
        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-2">📝 Notes</h3>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $record->notes }}</p>
    </div>
    @endif

    <div class="text-xs text-center text-gray-500 dark:text-gray-400 pt-2">
        Calculated at: {{ $record->calculated_at?->format('Y-m-d H:i:s') }}
    </div>
</div>
