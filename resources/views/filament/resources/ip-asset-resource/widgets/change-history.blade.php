<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-clock"
                    class="h-5 w-5 text-gray-500"
                />
                <span>Change History Tracking</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Records of when key fields were last modified
        </x-slot>

        @php
            $isAdmin = auth()->user()->email === 'admin@bunnycommunications.com';
            $meta = $this->record->meta ?? [];
            
            // Define field configurations
            $fieldConfigs = [
                'status' => [
                    'label' => 'Status',
                    'icon' => 'heroicon-o-flag',
                    'color' => 'purple',
                    'format' => 'text',
                ],
                'type' => [
                    'label' => 'Type',
                    'icon' => 'heroicon-o-tag',
                    'color' => 'indigo',
                    'format' => 'text',
                ],
                'asn' => [
                    'label' => 'ASN',
                    'icon' => 'heroicon-o-hashtag',
                    'color' => 'cyan',
                    'format' => 'text',
                ],
                'client' => [
                    'label' => 'Client',
                    'icon' => 'heroicon-o-user-group',
                    'color' => 'blue',
                    'format' => 'relation',
                    'name_key' => 'name',
                ],
                'location' => [
                    'label' => 'Location',
                    'icon' => 'heroicon-o-map-pin',
                    'color' => 'emerald',
                    'format' => 'relation',
                    'name_key' => 'name',
                ],
                'geo_location' => [
                    'label' => 'Geo Location',
                    'icon' => 'heroicon-o-globe-alt',
                    'color' => 'teal',
                    'format' => 'text',
                ],
                'cost' => [
                    'label' => 'Cost',
                    'icon' => 'heroicon-o-currency-dollar',
                    'color' => 'amber',
                    'format' => 'money',
                ],
                'price' => [
                    'label' => 'Price',
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'orange',
                    'format' => 'money',
                ],
            ];
            
            // Collect all fields with changes
            $changedFields = [];
            foreach ($fieldConfigs as $field => $config) {
                $historyKey = $field . '_history';
                if (!empty($meta[$historyKey])) {
                    $changedFields[$field] = [
                        'config' => $config,
                        'history' => $meta[$historyKey],
                    ];
                }
            }
        @endphp

        @if(!empty($changedFields))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($changedFields as $field => $data)
                    @php
                        $config = $data['config'];
                        $history = $data['history'];
                        $colorClasses = [
                            'purple' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-900', 'icon' => 'text-purple-600', 'badge' => 'text-purple-600', 'border-l' => 'border-purple-300'],
                            'indigo' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-900', 'icon' => 'text-indigo-600', 'badge' => 'text-indigo-600', 'border-l' => 'border-indigo-300'],
                            'cyan' => ['bg' => 'bg-cyan-50', 'border' => 'border-cyan-200', 'text' => 'text-cyan-900', 'icon' => 'text-cyan-600', 'badge' => 'text-cyan-600', 'border-l' => 'border-cyan-300'],
                            'blue' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-900', 'icon' => 'text-blue-600', 'badge' => 'text-blue-600', 'border-l' => 'border-blue-300'],
                            'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-900', 'icon' => 'text-emerald-600', 'badge' => 'text-emerald-600', 'border-l' => 'border-emerald-300'],
                            'teal' => ['bg' => 'bg-teal-50', 'border' => 'border-teal-200', 'text' => 'text-teal-900', 'icon' => 'text-teal-600', 'badge' => 'text-teal-600', 'border-l' => 'border-teal-300'],
                            'amber' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-900', 'icon' => 'text-amber-600', 'badge' => 'text-amber-600', 'border-l' => 'border-amber-300'],
                            'orange' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-900', 'icon' => 'text-orange-600', 'badge' => 'text-orange-600', 'border-l' => 'border-orange-300'],
                        ];
                        $colors = $colorClasses[$config['color']] ?? $colorClasses['blue'];
                    @endphp
                    
                    <div class="flex items-start gap-3 p-4 {{ $colors['bg'] }} rounded-lg border {{ $colors['border'] }}">
                        <x-filament::icon
                            icon="{{ $config['icon'] }}"
                            class="h-5 w-5 {{ $colors['icon'] }} mt-0.5 flex-shrink-0"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm {{ $colors['text'] }} mb-2">{{ $config['label'] }}</div>
                            
                            <div class="space-y-2">
                                @php
                                    $reversedHistory = array_reverse($history, true);
                                @endphp
                                @foreach($reversedHistory as $index => $change)
                                    <div class="text-xs border-l-2 {{ $colors['border-l'] }} pl-3 py-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="{{ $colors['badge'] }} font-medium mb-1 text-xs">
                                                    {{ \Carbon\Carbon::parse($change['changed_at'])->format('m-d H:i') }}
                                                </div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    @if($config['format'] === 'money')
                                                        <span class="text-red-600 line-through text-xs">
                                                            ${{ number_format($change['from'] ?? 0, 2) }}
                                                        </span>
                                                        <span class="text-gray-400">→</span>
                                                        <span class="text-green-600 font-medium text-xs">
                                                            ${{ number_format($change['to'] ?? 0, 2) }}
                                                        </span>
                                                    @elseif($config['format'] === 'relation')
                                                        @php
                                                            // Fallback to database lookup if name not stored in history
                                                            $fromName = $change['from_name'] ?? null;
                                                            $toName = $change['to_name'] ?? null;
                                                            
                                                            if (!$fromName && !empty($change['from_id'])) {
                                                                if ($field === 'client') {
                                                                    $fromName = \App\Models\Customer::find($change['from_id'])?->name;
                                                                } elseif ($field === 'location') {
                                                                    $fromName = \App\Models\Location::find($change['from_id'])?->name;
                                                                }
                                                            }
                                                            
                                                            if (!$toName && !empty($change['to_id'])) {
                                                                if ($field === 'client') {
                                                                    $toName = \App\Models\Customer::find($change['to_id'])?->name;
                                                                } elseif ($field === 'location') {
                                                                    $toName = \App\Models\Location::find($change['to_id'])?->name;
                                                                }
                                                            }
                                                        @endphp
                                                        <span class="text-red-600 line-through text-xs truncate">
                                                            {{ $fromName ?? ($change['from_id'] ? 'Unknown (ID:'.$change['from_id'].')' : '—') }}
                                                        </span>
                                                        <span class="text-gray-400">→</span>
                                                        <span class="text-green-600 font-medium text-xs truncate">
                                                            {{ $toName ?? ($change['to_id'] ? 'Unknown (ID:'.$change['to_id'].')' : '—') }}
                                                        </span>
                                                    @else
                                                        <span class="text-red-600 line-through text-xs truncate">
                                                            {{ $change['from'] ?: '—' }}
                                                        </span>
                                                        <span class="text-gray-400">→</span>
                                                        <span class="text-green-600 font-medium text-xs truncate">
                                                            {{ $change['to'] ?: '—' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($isAdmin)
                                                <button 
                                                    x-data="{ field: '{{ $field }}', index: {{ $index }} }"
                                                    x-on:click="
                                                        $wire.mountAction('deleteFieldHistory', { field: field, index: index })
                                                    "
                                                    class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-100 flex-shrink-0"
                                                    title="Delete this record"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 bg-gray-50 rounded-lg border border-gray-200 text-center">
                <div class="text-sm text-gray-600">
                    No change history yet. Modifications will be tracked here automatically.
                </div>
            </div>
        @endif

        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="text-xs text-gray-500">
                💡 <strong>Tip:</strong> All field changes are automatically timestamped. 
                Check Activity Logs for complete change history with details.
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
