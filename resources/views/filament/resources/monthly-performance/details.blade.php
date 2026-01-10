<div class="p-4 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Revenue Card --}}
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-green-800 dark:text-green-200 mb-3">💰 Revenue</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">IP Assets:</span>
                    <span class="font-semibold">${{ number_format($record->ip_asset_revenue, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Other Income:</span>
                    <span class="font-semibold">${{ number_format($record->other_income, 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-green-200 dark:border-green-700">
                    <span class="font-bold">Total:</span>
                    <span class="font-bold text-green-700 dark:text-green-300">${{ number_format($record->total_revenue, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Cost Card --}}
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-3">💸 Cost</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">IP Direct Cost:</span>
                    <span class="font-semibold">${{ number_format($record->ip_direct_cost, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Shared Cost:</span>
                    <span class="font-semibold">${{ number_format($record->shared_cost, 2) }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 pl-4">
                    ({{ number_format($record->shared_cost_ratio * 100, 2) }}% of total)
                </div>
                <div class="flex justify-between pt-2 border-t border-red-200 dark:border-red-700">
                    <span class="font-bold">Total:</span>
                    <span class="font-bold text-red-700 dark:text-red-300">${{ number_format($record->total_cost, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Profit & Salary Card --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-3">💵 Compensation</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Net Profit:</span>
                    <span class="font-semibold {{ $record->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ${{ number_format($record->net_profit, 2) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Commission ({{ number_format($record->commission_rate * 100, 1) }}%):</span>
                    <span class="font-semibold">${{ number_format($record->commission_amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Base Salary:</span>
                    <span class="font-semibold">${{ number_format($record->base_salary, 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-blue-200 dark:border-blue-700">
                    <span class="font-bold">Total Salary:</span>
                    <span class="font-bold text-blue-700 dark:text-blue-300">${{ number_format($record->total_compensation, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-semibold mb-3">📊 Statistics</h3>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <div class="text-gray-600 dark:text-gray-400">Active Subnets</div>
                <div class="text-2xl font-bold">{{ $record->active_subnet_count }}</div>
                <div class="text-xs text-gray-500">out of {{ $record->total_subnet_count }} total</div>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400">Active Customers</div>
                <div class="text-2xl font-bold">{{ $record->active_customer_count }}</div>
            </div>
            <div>
                <div class="text-gray-600 dark:text-gray-400">Subnet Ratio</div>
                <div class="text-2xl font-bold">{{ $record->total_subnet_count > 0 ? number_format($record->active_subnet_count / $record->total_subnet_count * 100, 1) : 0 }}%</div>
            </div>
        </div>
    </div>

    {{-- Calculation Formula --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">📐 Calculation Formula</h3>
        <div class="text-xs space-y-1 font-mono">
            <div>Total Revenue = IP Asset Revenue (${{ number_format($record->ip_asset_revenue, 2) }}) + Other Income (${{ number_format($record->other_income, 2) }}) = <span class="font-bold">${{ number_format($record->total_revenue, 2) }}</span></div>
            <div>Total Cost = IP Direct Cost (${{ number_format($record->ip_direct_cost, 2) }}) + Shared Cost (${{ number_format($record->shared_cost, 2) }}) = <span class="font-bold">${{ number_format($record->total_cost, 2) }}</span></div>
            <div>Net Profit = Total Revenue - Total Cost = <span class="font-bold {{ $record->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">${{ number_format($record->net_profit, 2) }}</span></div>
            <div>Commission = Net Profit × {{ number_format($record->commission_rate * 100, 1) }}% = <span class="font-bold">${{ number_format($record->commission_amount, 2) }}</span></div>
            <div>Total Compensation = Base Salary (${{ number_format($record->base_salary, 2) }}) + Commission (${{ number_format($record->commission_amount, 2) }}) = <span class="font-bold">${{ number_format($record->total_compensation, 2) }}</span></div>
        </div>
    </div>

    @if($record->notes)
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-semibold mb-2">📝 Notes</h3>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $record->notes }}</p>
    </div>
    @endif

    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        Calculated at: {{ $record->calculated_at?->format('Y-m-d H:i:s') }}
    </div>
</div>
