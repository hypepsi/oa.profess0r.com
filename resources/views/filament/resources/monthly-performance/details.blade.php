<div class="p-6 space-y-6 bg-white dark:bg-gray-900">
    {{-- Header Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl border border-green-200 dark:border-green-800">
            <div class="text-sm font-medium text-green-700 dark:text-green-300 mb-2">Revenue</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-100">${{ number_format($record->total_revenue, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl border border-red-200 dark:border-red-800">
            <div class="text-sm font-medium text-red-700 dark:text-red-300 mb-2">Cost</div>
            <div class="text-2xl font-bold text-red-900 dark:text-red-100">${{ number_format($record->total_cost, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl border border-blue-200 dark:border-blue-800">
            <div class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">Net Profit</div>
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">${{ number_format($record->net_profit, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-xl border border-amber-200 dark:border-amber-800">
            <div class="text-sm font-medium text-amber-700 dark:text-amber-300 mb-2">Total Salary</div>
            <div class="text-2xl font-bold text-amber-900 dark:text-amber-100">${{ number_format($record->total_compensation, 2) }}</div>
        </div>
    </div>

    {{-- Breakdown Tables --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Revenue --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b-2 border-gray-300 dark:border-gray-600">Revenue Breakdown</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700 dark:text-gray-300">IP Assets</span>
                    <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">${{ number_format($record->ip_asset_revenue, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700 dark:text-gray-300">Other Income</span>
                    <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">${{ number_format($record->other_income, 2) }}</span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t-2 border-green-300 dark:border-green-700 text-base">
                    <span class="font-bold text-gray-900 dark:text-gray-100">Total</span>
                    <span class="font-mono font-bold text-green-700 dark:text-green-300">${{ number_format($record->total_revenue, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Cost --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b-2 border-gray-300 dark:border-gray-600">Cost Breakdown</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700 dark:text-gray-300">IP Direct Cost</span>
                    <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">${{ number_format($record->ip_direct_cost, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <div class="flex flex-col">
                        <span class="text-gray-700 dark:text-gray-300">Shared Cost</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ number_format($record->shared_cost_ratio * 100, 1) }}%: {{ $record->active_subnet_count }}/{{ $record->total_subnet_count }} subnets)</span>
                    </div>
                    <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">${{ number_format($record->shared_cost, 2) }}</span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t-2 border-red-300 dark:border-red-700 text-base">
                    <span class="font-bold text-gray-900 dark:text-gray-100">Total</span>
                    <span class="font-mono font-bold text-red-700 dark:text-red-300">${{ number_format($record->total_cost, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Salary Calculation --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b-2 border-gray-300 dark:border-gray-600">Salary Calculation</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-700 dark:text-gray-300">Net Profit</span>
                <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">${{ number_format($record->net_profit, 2) }}</span>
            </div>
            @if($record->workflow_deductions > 0)
            <div class="flex justify-between items-center text-sm">
                <div class="flex flex-col">
                    <span class="text-gray-700 dark:text-gray-300">Workflow Deductions</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ $record->overdue_workflow_count }} overdue workflows)</span>
                </div>
                <span class="font-mono font-semibold text-red-700 dark:text-red-300">-${{ number_format($record->workflow_deductions, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-700 dark:text-gray-300">Commission ({{ number_format($record->commission_rate * 100, 1) }}%)</span>
                <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">${{ number_format($record->commission_amount, 2) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-700 dark:text-gray-300">Base Salary</span>
                <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">${{ number_format($record->base_salary, 2) }}</span>
            </div>
            <div class="flex justify-between items-center pt-3 border-t-2 border-amber-300 dark:border-amber-700 text-base">
                <span class="font-bold text-gray-900 dark:text-gray-100">Total Compensation</span>
                <span class="font-mono font-bold text-amber-700 dark:text-amber-300">${{ number_format($record->total_compensation, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b-2 border-gray-300 dark:border-gray-600">Statistics</h3>
        <div class="grid grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Active Subnets</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $record->active_subnet_count }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">/ {{ $record->total_subnet_count }} total</div>
            </div>
            <div class="text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Active Customers</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $record->active_customer_count }}</div>
            </div>
            <div class="text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Subnet Ratio</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $record->total_subnet_count > 0 ? number_format($record->active_subnet_count / $record->total_subnet_count * 100, 1) : 0 }}%</div>
            </div>
        </div>
    </div>

    @if($record->notes)
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-300 dark:border-amber-700">
        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-2">Notes</h3>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $record->notes }}</p>
    </div>
    @endif

    <div class="text-xs text-center text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
        Calculated: {{ $record->calculated_at?->format('Y-m-d H:i:s') }}
    </div>
</div>
