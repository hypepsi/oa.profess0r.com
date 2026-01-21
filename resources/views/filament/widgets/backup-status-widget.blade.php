<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            {{-- System Backup Section --}}
            <div class="space-y-4">
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
                    <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No backups yet</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Click "Backup Now" to create your first backup</p>
                    </div>
                @endif
            </div>

            {{-- Divider --}}
            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            {{-- GeoFeed Backup Section --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-medium text-gray-950 dark:text-white">GeoFeed Backup</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Automated sync to remote at 3:05 AM
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
                        <x-filament::button 
                            wire:click="downloadGeoFeedFromDatabase"
                            size="sm"
                            color="gray"
                            icon="heroicon-o-circle-stack"
                        >
                            From Database
                        </x-filament::button>
                        
                        <x-filament::button 
                            wire:click="downloadGeoFeedFromRemote"
                            size="sm"
                            color="primary"
                            icon="heroicon-o-cloud-arrow-down"
                        >
                            From Remote
                        </x-filament::button>
                    </div>
                </div>
                
                @php
                    $geoMeta = $this->getGeoFeedBackupMetadata();
                @endphp
                
                @if($geoMeta)
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Last Backup</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $geoMeta['last_backup_time'] }}</dd>
                        </div>
                        
                        <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Filename</dt>
                            <dd class="mt-1 truncate text-sm text-gray-900 dark:text-white" title="{{ $geoMeta['last_backup_file'] }}">
                                {{ $geoMeta['last_backup_file'] }}
                            </dd>
                        </div>
                        
                        <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Size</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ number_format($geoMeta['last_backup_size'] / 1024, 2) }} KB</dd>
                        </div>
                        
                        <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Source</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ ucfirst($geoMeta['source']) }}
                                </span>
                            </dd>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No GeoFeed backup yet</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Download GeoFeed from database or remote</p>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
