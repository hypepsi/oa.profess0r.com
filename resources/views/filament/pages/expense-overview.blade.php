<x-filament-panels::page>
    @php
        $summary = $summary ?? [];
        $previousSummary = $previousSummary ?? [];
        $topProviders = $summary['top_providers'] ?? [];
        $overdueList = $summary['overdue'] ?? [];

        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp

    <script>
        // 保存和恢复左侧导航栏（sidebar）的滚动位置
        (function() {
            const scrollKey = 'filament-sidebar-scroll';
            let isRestoring = false;
            
            // 获取左侧导航栏元素
            function getSidebarElement() {
                return document.querySelector('.fi-sidebar-nav') || 
                       document.querySelector('[data-sidebar]') ||
                       document.querySelector('.fi-sidebar') ||
                       document.querySelector('aside');
            }
            
            // 保存滚动位置
            function saveScrollPosition() {
                if (isRestoring) return;
                const sidebar = getSidebarElement();
                if (sidebar) {
                    const scrollTop = sidebar.scrollTop;
                    sessionStorage.setItem(scrollKey, scrollTop.toString());
                }
            }
            
            // 恢复滚动位置
            function restoreScrollPosition() {
                const sidebar = getSidebarElement();
                if (!sidebar) return;
                
                const savedScroll = sessionStorage.getItem(scrollKey);
                if (savedScroll !== null) {
                    isRestoring = true;
                    const scrollTop = parseInt(savedScroll, 10);
                    
                    // 使用 requestAnimationFrame 确保在渲染后执行
                    requestAnimationFrame(() => {
                        sidebar.scrollTop = scrollTop;
                        setTimeout(() => {
                            isRestoring = false;
                        }, 100);
                    });
                }
            }
            
            // 监听左侧导航栏的滚动事件
            function setupSidebarScrollListener() {
                const sidebar = getSidebarElement();
                if (!sidebar) {
                    // 如果还没找到，延迟重试
                    setTimeout(setupSidebarScrollListener, 100);
                    return;
                }
                
                let scrollTimeout;
                sidebar.addEventListener('scroll', function() {
                    if (isRestoring) return;
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(saveScrollPosition, 200);
                }, { passive: true });
            }
            
            // 初始化
            function initScrollRestore() {
                setupSidebarScrollListener();
                
                // 延迟恢复，确保导航栏已完全渲染
                setTimeout(() => {
                    restoreScrollPosition();
                }, 400);
                
                // Livewire 更新后也恢复
                if (window.Livewire) {
                    window.Livewire.hook('morph.updated', () => {
                        setTimeout(restoreScrollPosition, 200);
                    });
                }
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initScrollRestore);
            } else {
                initScrollRestore();
            }
            
            // 页面卸载前保存
            window.addEventListener('beforeunload', saveScrollPosition);
            
            // 页面可见性变化时保存
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    saveScrollPosition();
                }
            });
        })();
    </script>

    <div class="mb-6">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Month</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $periodLabel }}</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-building-office-2" class="w-6 h-6 text-indigo-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Providers to Pay</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $summary['providers_due'] ?? 0 }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-6 h-6 text-rose-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Expense</p>
                    <p class="mt-1 text-2xl font-semibold text-rose-600 dark:text-rose-400">
                        {{ $formatCurrency($summary['expected_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-circle-stack" class="w-6 h-6 text-sky-500" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid (Confirmed)</p>
                    <p class="mt-1 text-2xl font-semibold text-sky-600 dark:text-sky-400">
                        {{ $formatCurrency($summary['paid_total'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card :class="count($overdueList) ? 'border border-rose-200 dark:border-rose-500 bg-rose-50 dark:bg-rose-950/40' : ''">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 {{ count($overdueList) ? 'text-rose-600' : 'text-gray-400' }}" />
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue Amount</p>
                    <p class="mt-1 text-2xl font-semibold {{ count($overdueList) ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ $formatCurrency($summary['overdue_amount_total'] ?? 0) }}
                    </p>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ count($overdueList) }} provider(s)</p>
                </div>
            </div>
        </x-filament::card>
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top 3 Providers (Amount)</x-slot>
            <x-slot name="description">Highest expense amounts this month</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($topProviders as $row)
                    <li class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $row['provider']->name }}</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $row['provider_type'] === 'App\\Models\\Provider' ? 'IP Provider' : 'IPT Provider' }}
                            </p>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ $formatCurrency($row['amount']) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-sm font-medium text-gray-500 dark:text-gray-400">No expense data for this month.</li>
                @endforelse
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Overdue Providers</x-slot>
            <x-slot name="description">Unpaid months before {{ $periodLabel }}</x-slot>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($overdueList as $row)
                    <li class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $row['provider']->name }}</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Follow up required</p>
                        </div>
                        <span class="text-sm font-semibold text-rose-600 dark:text-rose-400">
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
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Providers</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $previousSummary['providers_due'] ?? 0 }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Expected</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $formatCurrency($previousSummary['expected_total'] ?? 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Paid</p>
                    <p class="text-lg font-semibold text-sky-600 dark:text-sky-400">
                        {{ $formatCurrency($previousSummary['paid_total'] ?? 0) }}
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

