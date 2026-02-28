{{--
    Activity Log — Detail Modal
    Rendered via ViewAction::make()->modalContent(...)
    Supports light / dark mode via Tailwind dark: variants.
--}}
@php
    use App\Models\ActivityLog;

    $categoryColors = [
        'auth'      => 'bg-blue-100 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20',
        'income'    => 'bg-green-100 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20',
        'expense'   => 'bg-red-100 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20',
        'ip_assets' => 'bg-violet-100 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20',
        'workflows' => 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20',
        'documents' => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20',
        'system'    => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20',
    ];

    $actionColors = [
        'created'                  => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        'updated'                  => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'deleted'                  => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'force_deleted'            => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'login'                    => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
        'logout'                   => 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400',
        'payment_recorded'         => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        'payment_waived'           => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'payment_reset'            => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'expense_payment_recorded' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        'expense_waived'           => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'expense_reset'            => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];

    $catLabel   = ActivityLog::getCategoryOptions()[$record->category] ?? ucwords(str_replace('_', ' ', $record->category ?? ''));
    $catClass   = $categoryColors[$record->category] ?? $categoryColors['system'];
    $actClass   = $actionColors[$record->action] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400';
    $actLabel   = ucwords(str_replace('_', ' ', $record->action));

    // Fields to skip in the properties key-value display
    $skipFields = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];

    $isUpdate  = $record->action === 'updated'
                 && isset($record->properties['changes'])
                 && !empty($record->properties['changes']);
@endphp

<div class="divide-y divide-gray-100 dark:divide-white/5 -mx-6 -mb-6">

    {{-- ── Row 1: Timestamp / Category / Action ─────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-2 px-6 py-4 bg-gray-50/80 dark:bg-white/5">
        {{-- Timestamp --}}
        <span class="font-mono text-xs text-gray-500 dark:text-gray-400 mr-2">
            {{ $record->created_at->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
        </span>

        {{-- Category badge --}}
        @if($record->category)
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $catClass }}">
                {{ $catLabel }}
            </span>
        @endif

        {{-- Action badge --}}
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $actClass }} ring-current/20">
            {{ $actLabel }}
        </span>

        {{-- Log ID (subtle) --}}
        <span class="ml-auto font-mono text-xs text-gray-400 dark:text-gray-600">#{{ $record->id }}</span>
    </div>

    {{-- ── Row 2: Operator / Target ──────────────────────────────────────── --}}
    <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-white/5">
        {{-- Operator --}}
        <div class="px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5">
                Operator
            </p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ $record->user_name }}
            </p>
            @if($record->user?->email)
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $record->user->email }}
                </p>
            @endif
        </div>

        {{-- Target model --}}
        <div class="px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5">
                Target
            </p>
            @if($record->model_type)
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ class_basename($record->model_type) }}
                </p>
                @if($record->model_id)
                    <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">
                        ID: {{ $record->model_id }}
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-400 dark:text-gray-600">—</p>
            @endif
        </div>
    </div>

    {{-- ── Row 3: Description ─────────────────────────────────────────────── --}}
    <div class="px-6 py-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5">
            Description
        </p>
        <p class="text-sm leading-relaxed text-gray-900 dark:text-white">
            {{ $record->description }}
        </p>
    </div>

    {{-- ── Row 4: Changes / Properties ────────────────────────────────────── --}}
    @if($record->properties && is_array($record->properties) && !empty($record->properties))
        <div class="px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">
                {{ $isUpdate ? 'Field Changes' : 'Details' }}
            </p>

            @if($isUpdate)
                {{-- ── Diff table (updated action) ─────────────────────── --}}
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-1/3">
                                    Field
                                </th>
                                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider text-red-500 dark:text-red-400 w-1/3">
                                    Before
                                </th>
                                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider text-green-600 dark:text-green-400 w-1/3">
                                    After
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach($record->properties['changes'] as $field => $newValue)
                                @if(in_array($field, ['updated_at']))
                                    @continue
                                @endif
                                @php
                                    $oldValue = $record->properties['original'][$field] ?? null;
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                                    <td class="px-4 py-2.5 font-medium text-gray-700 dark:text-gray-300">
                                        {{ ucwords(str_replace('_', ' ', $field)) }}
                                    </td>
                                    <td class="px-4 py-2.5 font-mono text-red-600 dark:text-red-400">
                                        @if(is_null($oldValue))
                                            <em class="text-gray-400 dark:text-gray-600 not-italic">null</em>
                                        @elseif(is_array($oldValue))
                                            <span class="break-all">{{ json_encode($oldValue, JSON_UNESCAPED_UNICODE) }}</span>
                                        @else
                                            <span class="break-all">{{ $oldValue }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 font-mono text-green-600 dark:text-green-400">
                                        @if(is_null($newValue))
                                            <em class="text-gray-400 dark:text-gray-600 not-italic">null</em>
                                        @elseif(is_array($newValue))
                                            <span class="break-all">{{ json_encode($newValue, JSON_UNESCAPED_UNICODE) }}</span>
                                        @else
                                            <span class="break-all">{{ $newValue }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                {{-- ── Key-value list (created / deleted / custom) ──────── --}}
                @php
                    $filteredProps = array_filter(
                        $record->properties,
                        fn ($key) => !in_array($key, $skipFields),
                        ARRAY_FILTER_USE_KEY
                    );
                @endphp

                @if(!empty($filteredProps))
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                        <table class="w-full text-xs">
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach($filteredProps as $key => $value)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                                        <td class="px-4 py-2.5 w-1/3 font-medium text-gray-600 dark:text-gray-400 bg-gray-50/80 dark:bg-white/[0.03] whitespace-nowrap align-top">
                                            {{ ucwords(str_replace('_', ' ', $key)) }}
                                        </td>
                                        <td class="px-4 py-2.5 font-mono text-gray-700 dark:text-gray-300">
                                            @if(is_null($value))
                                                <em class="text-gray-400 dark:text-gray-600 not-italic">null</em>
                                            @elseif(is_bool($value))
                                                <span class="{{ $value ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $value ? 'true' : 'false' }}
                                                </span>
                                            @elseif(is_array($value))
                                                <pre class="whitespace-pre-wrap text-xs leading-relaxed">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                <span class="break-all">{{ $value }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- ── Row 5: Request Info ────────────────────────────────────────────── --}}
    @if($record->ip_address || $record->user_agent)
        <div class="px-6 py-4 bg-gray-50/50 dark:bg-white/[0.02]">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">
                Request Info
            </p>
            <div class="grid grid-cols-2 gap-4">
                @if($record->ip_address)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">IP Address</p>
                        <p class="font-mono text-sm text-gray-900 dark:text-white">{{ $record->ip_address }}</p>
                    </div>
                @endif
                @if($record->user_agent)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">User Agent</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 break-all leading-relaxed">
                            {{ $record->user_agent }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
