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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($this->record->status === 'Released' || $this->record->released_at)
                <div class="flex items-start gap-3 p-4 bg-red-50 rounded-lg border border-red-200">
                    <x-filament::icon
                        icon="heroicon-o-arrow-right-on-rectangle"
                        class="h-5 w-5 text-red-600 mt-0.5"
                    />
                    <div class="flex-1">
                        <div class="font-medium text-sm text-red-900">Released At</div>
                        @if($this->record->released_at)
                            <div class="text-sm text-red-700 mt-1">
                                {{ $this->record->released_at->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
                            </div>
                        @else
                            <div class="text-sm text-red-600 mt-1">Just released (save to record time)</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($this->record->client_changed_at)
                <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <x-filament::icon
                        icon="heroicon-o-arrow-path"
                        class="h-5 w-5 text-blue-600 mt-0.5"
                    />
                    <div class="flex-1">
                        <div class="font-medium text-sm text-blue-900">Client Change History</div>
                        @php
                            $clientHistory = $this->record->meta['client_history'] ?? [];
                            $isAdmin = auth()->user()->email === 'admin@bunnycommunications.com';
                        @endphp
                        @if(!empty($clientHistory))
                            <div class="space-y-2 mt-2">
                                @php
                                    $reversedHistory = array_reverse($clientHistory, true);
                                @endphp
                                @foreach($reversedHistory as $index => $change)
                                    <div class="text-xs border-l-2 border-blue-300 pl-3 py-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="text-blue-600 font-medium mb-1">
                                                    {{ \Carbon\Carbon::parse($change['changed_at'])->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-red-600 line-through">
                                                        {{ $change['from_id'] ? \App\Models\Customer::find($change['from_id'])?->name ?? 'ID:'.$change['from_id'] : 'None' }}
                                                    </span>
                                                    <span class="text-gray-400">→</span>
                                                    <span class="text-green-600 font-medium">
                                                        {{ $change['to_id'] ? \App\Models\Customer::find($change['to_id'])?->name ?? 'ID:'.$change['to_id'] : 'None' }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($isAdmin)
                                                <button 
                                                    x-data="{ index: {{ $index }} }"
                                                    x-on:click="
                                                        $wire.mountAction('deleteClientHistory', { index: index })
                                                    "
                                                    class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-100 flex-shrink-0"
                                                    title="Delete this record"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($this->record->client)
                            <div class="text-xs text-blue-600 mt-1">Current: {{ $this->record->client->name }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($this->record->cost_changed_at)
                <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-lg border border-amber-200">
                    <x-filament::icon
                        icon="heroicon-o-currency-dollar"
                        class="h-5 w-5 text-amber-600 mt-0.5"
                    />
                    <div class="flex-1">
                        <div class="font-medium text-sm text-amber-900">Cost Change History</div>
                        @php
                            $costHistory = $this->record->meta['cost_history'] ?? [];
                            $isAdmin = auth()->user()->email === 'admin@bunnycommunications.com';
                        @endphp
                        @if(!empty($costHistory))
                            <div class="space-y-2 mt-2">
                                @php
                                    $reversedHistory = array_reverse($costHistory, true);
                                @endphp
                                @foreach($reversedHistory as $index => $change)
                                    <div class="text-xs border-l-2 border-amber-300 pl-3 py-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="text-amber-600 font-medium mb-1">
                                                    {{ \Carbon\Carbon::parse($change['changed_at'])->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-red-600 line-through">
                                                        ${{ number_format($change['from'] ?? 0, 2) }}
                                                    </span>
                                                    <span class="text-gray-400">→</span>
                                                    <span class="text-green-600 font-medium">
                                                        ${{ number_format($change['to'] ?? 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($isAdmin)
                                                <button 
                                                    x-data="{ index: {{ $index }} }"
                                                    x-on:click="
                                                        $wire.mountAction('deleteCostHistory', { index: index })
                                                    "
                                                    class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-100 flex-shrink-0"
                                                    title="Delete this record"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($this->record->cost)
                            <div class="text-xs text-amber-600 mt-1">Current: ${{ number_format($this->record->cost, 2) }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($this->record->price_changed_at)
                <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-lg border border-amber-200">
                    <x-filament::icon
                        icon="heroicon-o-banknotes"
                        class="h-5 w-5 text-amber-600 mt-0.5"
                    />
                    <div class="flex-1">
                        <div class="font-medium text-sm text-amber-900">Price Change History</div>
                        @php
                            $priceHistory = $this->record->meta['price_history'] ?? [];
                            $isAdmin = auth()->user()->email === 'admin@bunnycommunications.com';
                        @endphp
                        @if(!empty($priceHistory))
                            <div class="space-y-2 mt-2">
                                @php
                                    $reversedHistory = array_reverse($priceHistory, true);
                                @endphp
                                @foreach($reversedHistory as $index => $change)
                                    <div class="text-xs border-l-2 border-amber-300 pl-3 py-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="text-amber-600 font-medium mb-1">
                                                    {{ \Carbon\Carbon::parse($change['changed_at'])->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-red-600 line-through">
                                                        ${{ number_format($change['from'] ?? 0, 2) }}
                                                    </span>
                                                    <span class="text-gray-400">→</span>
                                                    <span class="text-green-600 font-medium">
                                                        ${{ number_format($change['to'] ?? 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($isAdmin)
                                                <button 
                                                    x-data="{ index: {{ $index }} }"
                                                    x-on:click="
                                                        $wire.mountAction('deletePriceHistory', { index: index })
                                                    "
                                                    class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-100 flex-shrink-0"
                                                    title="Delete this record"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($this->record->price)
                            <div class="text-xs text-amber-600 mt-1">Current: ${{ number_format($this->record->price, 2) }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if(!$this->record->released_at && !$this->record->client_changed_at && !$this->record->cost_changed_at && !$this->record->price_changed_at)
                <div class="col-span-2 p-4 bg-gray-50 rounded-lg border border-gray-200 text-center">
                    <div class="text-sm text-gray-600">
                        No change history yet. Modifications to Status, Client, Cost, or Price will be tracked here.
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="text-xs text-gray-500">
                💡 <strong>Tip:</strong> Changes to Status (Released), Client, Cost, and Price are automatically timestamped. 
                Check Activity Logs for complete change history with details.
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

