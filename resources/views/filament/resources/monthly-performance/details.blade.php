<div class="p-6 space-y-4">
    {{-- Top Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border-2 border-green-200 dark:border-green-700">
            <div class="text-sm text-green-700 dark:text-green-300 mb-1">Income</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-100">${{ number_format($record->total_revenue, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border-2 border-red-200 dark:border-red-700">
            <div class="text-sm text-red-700 dark:text-red-300 mb-1">Expense</div>
            <div class="text-2xl font-bold text-red-900 dark:text-red-100">${{ number_format($record->total_cost, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-200 dark:border-blue-700">
            <div class="text-sm text-blue-700 dark:text-blue-300 mb-1">Profit</div>
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">${{ number_format($record->net_profit, 2) }}</div>
        </div>

        <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border-2 border-amber-200 dark:border-amber-700">
            <div class="text-sm text-amber-700 dark:text-amber-300 mb-1">Salary</div>
            <div class="text-2xl font-bold text-amber-900 dark:text-amber-100">${{ number_format($record->total_compensation, 2) }}</div>
        </div>
    </div>

    {{-- Details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Income --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="font-semibold text-base mb-3 pb-2 border-b">Income Details</div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>IP Assets</span>
                    <span class="font-mono">${{ number_format($record->ip_asset_revenue, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Other Income</span>
                    <span class="font-mono">${{ number_format($record->other_income, 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t font-semibold">
                    <span>Total</span>
                    <span class="font-mono text-green-600">${{ number_format($record->total_revenue, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Expense --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="font-semibold text-base mb-3 pb-2 border-b">Expense Details</div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>IP Direct Cost</span>
                    <span class="font-mono">${{ number_format($record->ip_direct_cost, 2) }}</span>
                </div>
                <div>
                    <div class="flex justify-between">
                        <span>Shared Cost</span>
                        <span class="font-mono">${{ number_format($record->shared_cost, 2) }}</span>
                    </div>
                    <div class="text-xs text-gray-500 pl-2">
                        {{ number_format($record->shared_cost_ratio * 100, 1) }}% ({{ $record->active_subnet_count }}/{{ $record->total_subnet_count }} subnets)
                    </div>
                </div>
                <div class="flex justify-between pt-2 border-t font-semibold">
                    <span>Total</span>
                    <span class="font-mono text-red-600">${{ number_format($record->total_cost, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Salary --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="font-semibold text-base mb-3 pb-2 border-b">Salary Details</div>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span>Profit</span>
                <span class="font-mono">${{ number_format($record->net_profit, 2) }}</span>
            </div>
            @if($record->workflow_deductions > 0)
            <div>
                <div class="flex justify-between">
                    <span>Penalty</span>
                    <span class="font-mono text-red-600">-${{ number_format($record->workflow_deductions, 2) }}</span>
                </div>
                <div class="text-xs text-gray-500 pl-2">
                    {{ $record->overdue_workflow_count }} overdue tasks
                </div>
            </div>
            @endif
            <div class="flex justify-between">
                <span>Bonus ({{ number_format($record->commission_rate * 100, 1) }}%)</span>
                <span class="font-mono">${{ number_format($record->commission_amount, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Base Pay</span>
                <span class="font-mono">${{ number_format($record->base_salary, 2) }}</span>
            </div>
            <div class="flex justify-between pt-2 border-t font-semibold">
                <span>Total Pay</span>
                <span class="font-mono text-amber-600">${{ number_format($record->total_compensation, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="font-semibold text-base mb-3 pb-2 border-b">Stats</div>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Subnets</div>
                <div class="text-2xl font-bold">{{ $record->active_subnet_count }}</div>
                <div class="text-xs text-gray-500">/ {{ $record->total_subnet_count }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Clients</div>
                <div class="text-2xl font-bold">{{ $record->active_customer_count }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ratio</div>
                <div class="text-2xl font-bold">{{ $record->total_subnet_count > 0 ? number_format($record->active_subnet_count / $record->total_subnet_count * 100, 1) : 0 }}%</div>
            </div>
        </div>
    </div>

    @if($record->notes)
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-200 dark:border-blue-700">
        <div class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">Notes</div>
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->notes }}</div>
    </div>
    @endif

    <div class="text-xs text-center text-gray-500 pt-2 border-t">
        {{ $record->calculated_at?->format('Y-m-d H:i:s') }}
    </div>
</div>
