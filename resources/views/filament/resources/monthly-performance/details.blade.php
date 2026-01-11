<div class="space-y-6 p-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border-2 border-emerald-200 bg-emerald-50 p-4 text-center dark:border-emerald-800 dark:bg-emerald-950/20">
            <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Revenue</div>
            <div class="mt-2 text-base font-semibold text-emerald-900 dark:text-emerald-100">${{ number_format($record->total_revenue, 2) }}</div>
        </div>

        <div class="rounded-lg border-2 border-red-200 bg-red-50 p-4 text-center dark:border-red-800 dark:bg-red-950/20">
            <div class="text-xs font-medium uppercase tracking-wide text-red-700 dark:text-red-400">Cost</div>
            <div class="mt-2 text-base font-semibold text-red-900 dark:text-red-100">${{ number_format($record->total_cost, 2) }}</div>
        </div>

        <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4 text-center dark:border-blue-800 dark:bg-blue-950/20">
            <div class="text-xs font-medium uppercase tracking-wide text-blue-700 dark:text-blue-400">Profit</div>
            <div class="mt-2 text-base font-semibold text-blue-900 dark:text-blue-100">${{ number_format($record->net_profit, 2) }}</div>
        </div>

        <div class="rounded-lg border-2 border-amber-200 bg-amber-50 p-4 text-center dark:border-amber-800 dark:bg-amber-950/20">
            <div class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-400">Total Salary</div>
            <div class="mt-2 text-base font-semibold text-amber-900 dark:text-amber-100">${{ number_format($record->total_compensation, 2) }}</div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Revenue Breakdown --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="border-b border-gray-200 pb-3 text-sm font-medium text-gray-900 dark:border-gray-700 dark:text-white">Revenue Breakdown</h3>
            <dl class="mt-3 space-y-3">
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">IP Assets</dt>
                    <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">${{ number_format($record->ip_asset_revenue, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Other Income</dt>
                    <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">${{ number_format($record->other_income, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                    <dt class="text-sm font-medium text-gray-900 dark:text-white">Total</dt>
                    <dd class="text-sm font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">${{ number_format($record->total_revenue, 2) }}</dd>
                </div>
            </dl>
        </div>

        {{-- Cost Breakdown --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="border-b border-gray-200 pb-3 text-sm font-medium text-gray-900 dark:border-gray-700 dark:text-white">Cost Breakdown</h3>
            <dl class="mt-3 space-y-3">
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">IP Direct Cost</dt>
                    <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">${{ number_format($record->ip_direct_cost, 2) }}</dd>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Shared Cost</dt>
                        <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">${{ number_format($record->shared_cost, 2) }}</dd>
                    </div>
                    <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($record->shared_cost_ratio * 100, 1) }}% ratio · {{ $record->active_subnet_count }}/{{ $record->total_subnet_count }} subnets
                    </dd>
                </div>
                <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                    <dt class="text-sm font-medium text-gray-900 dark:text-white">Total</dt>
                    <dd class="text-sm font-semibold tabular-nums text-red-600 dark:text-red-400">${{ number_format($record->total_cost, 2) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Salary Calculation --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h3 class="border-b border-gray-200 pb-3 text-sm font-medium text-gray-900 dark:border-gray-700 dark:text-white">Salary Calculation</h3>
        <dl class="mt-3 space-y-3">
            <div class="flex items-center justify-between">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Net Profit</dt>
                <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">${{ number_format($record->net_profit, 2) }}</dd>
            </div>
            @if($record->workflow_deductions > 0)
            <div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Workflow Deductions</dt>
                    <dd class="text-sm font-medium tabular-nums text-red-600 dark:text-red-400">-${{ number_format($record->workflow_deductions, 2) }}</dd>
                </div>
                <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $record->overdue_workflow_count }} overdue task(s)</dd>
            </div>
            @endif
            <div class="flex items-center justify-between">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Base Salary</dt>
                <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">${{ number_format($record->base_salary, 2) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Commission ({{ number_format($record->commission_rate * 100, 1) }}%)</dt>
                <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">${{ number_format($record->commission_amount, 2) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                <dt class="text-sm font-medium text-gray-900 dark:text-white">Total Salary</dt>
                <dd class="text-sm font-semibold tabular-nums text-amber-600 dark:text-amber-400">${{ number_format($record->total_compensation, 2) }}</dd>
            </div>
        </dl>
    </div>

    {{-- Statistics --}}
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
        <h3 class="border-b border-gray-200 pb-3 text-sm font-medium text-gray-900 dark:border-gray-700 dark:text-white">Statistics</h3>
        <div class="mt-4 grid grid-cols-3 gap-4 text-center">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Active Subnets</dt>
                <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $record->active_subnet_count }}</dd>
                <dd class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">of {{ $record->total_subnet_count }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Active Customers</dt>
                <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $record->active_customer_count }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Subnet Ratio</dt>
                <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $record->total_subnet_count > 0 ? number_format($record->active_subnet_count / $record->total_subnet_count * 100, 1) : 0 }}%</dd>
            </div>
        </div>
    </div>

    @if($record->notes)
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950/20">
        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">Notes</h4>
        <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">{{ $record->notes }}</p>
    </div>
    @endif

    <div class="border-t border-gray-200 pt-3 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
        Calculated at {{ $record->calculated_at?->format('Y-m-d H:i:s') }}
    </div>
</div>
