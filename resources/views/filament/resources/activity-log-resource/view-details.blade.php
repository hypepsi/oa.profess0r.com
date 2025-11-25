<x-filament::modal.heading>
    Activity Log Details
</x-filament::modal.heading>

<div class="space-y-4">
    <div>
        <strong>Time:</strong> {{ $record->created_at->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
    </div>
    
    <div>
        <strong>User:</strong> {{ $record->user_name }}
    </div>
    
    <div>
        <strong>Action:</strong> 
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium
            @if($record->action === 'created') bg-green-100 text-green-800
            @elseif($record->action === 'updated') bg-yellow-100 text-yellow-800
            @elseif($record->action === 'deleted') bg-red-100 text-red-800
            @elseif($record->action === 'login') bg-blue-100 text-blue-800
            @else bg-gray-100 text-gray-800
            @endif">
            {{ ucfirst($record->action) }}
        </span>
    </div>
    
    <div>
        <strong>Description:</strong> {{ $record->description }}
    </div>
    
    @if($record->model_type)
        <div>
            <strong>Model:</strong> {{ class_basename($record->model_type) }}
            @if($record->model_id)
                (ID: {{ $record->model_id }})
            @endif
        </div>
    @endif
    
    @if($record->ip_address)
        <div>
            <strong>IP Address:</strong> {{ $record->ip_address }}
        </div>
    @endif
    
    @if($record->user_agent)
        <div>
            <strong>User Agent:</strong> {{ $record->user_agent }}
        </div>
    @endif
    
    @if($record->properties && is_array($record->properties))
        <div>
            <strong>Details:</strong>
            <pre class="mt-2 p-3 bg-gray-100 rounded text-xs overflow-auto">{{ json_encode($record->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>


