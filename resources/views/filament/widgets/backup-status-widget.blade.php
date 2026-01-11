<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-950 dark:text-white">System Backup</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Automated daily backups at 3:00 AM</p>
                </div>
                
                <div class="flex gap-2">
                    <x-filament::button 
                        wire:click="createBackup"
                        size="sm"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        Backup Now
                    </x-filament::button>
                    
                    @if($this->getBackupMetadata())
                        <x-filament::button 
                            wire:click="downloadLatestBackup"
                            color="success"
                            size="sm"
                            icon="heroicon-o-cloud-arrow-down"
                        >
                            Download
                        </x-filament::button>
                    @endif
                </div>
            </div>
            
            @php
                $metadata = $this->getBackupMetadata();
            @endphp
            
            @if($metadata)
                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Last Backup</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $metadata['last_backup_time'] }}</dd>
                    </div>
                    
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Filename</dt>
                        <dd class="mt-1 truncate text-sm text-gray-900 dark:text-white" title="{{ $metadata['last_backup_file'] }}">
                            {{ $metadata['last_backup_file'] }}
                        </dd>
                    </div>
                    
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Size</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ number_format($metadata['last_backup_size'] / 1024 / 1024, 2) }} MB</dd>
                    </div>
                    
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Location</dt>
                        <dd class="mt-1 truncate text-sm text-gray-900 dark:text-white" title="{{ $metadata['last_backup_path'] }}">
                            /backups/
                        </dd>
                    </div>
                </div>
            @else
                {{-- Empty State --}}
                <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No backups yet</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Click "Backup Now" to create your first backup</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
