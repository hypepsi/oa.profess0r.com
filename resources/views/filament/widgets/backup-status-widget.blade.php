<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold">System Backup</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Backup all system data to Excel</p>
                </div>
                
                <div class="flex gap-2">
                    <x-filament::button 
                        wire:click="createBackup"
                        color="primary"
                        icon="heroicon-o-arrow-down-tray"
                        size="sm"
                    >
                        Backup Now
                    </x-filament::button>
                    
                    @if($this->getBackupMetadata())
                        <x-filament::button 
                            wire:click="downloadLatestBackup"
                            color="success"
                            icon="heroicon-o-cloud-arrow-down"
                            size="sm"
                        >
                            Download Latest
                        </x-filament::button>
                    @endif
                </div>
            </div>
            
            @php
                $metadata = $this->getBackupMetadata();
            @endphp
            
            @if($metadata)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-xs space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Last Backup:</span>
                        <span class="font-medium">{{ $metadata['last_backup_time'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">File:</span>
                        <span class="font-medium truncate ml-2" title="{{ $metadata['last_backup_file'] }}">{{ $metadata['last_backup_file'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Size:</span>
                        <span class="font-medium">{{ number_format($metadata['last_backup_size'] / 1024 / 1024, 2) }} MB</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-1.5 mt-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-600 dark:text-gray-400 flex-shrink-0">Path:</span>
                            <span class="font-mono text-[10px] text-right break-all">{{ $metadata['last_backup_path'] }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center justify-center gap-3">
                    <span>💾 Auto backup: Daily 3:00 AM</span>
                    <span>•</span>
                    <span>🗄️ Retention: 30 days</span>
                </div>
            @else
                <div class="text-center py-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        <p class="text-sm font-medium">No backup found</p>
                        <p class="text-xs mt-1">Click "Backup Now" to create your first backup</p>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
