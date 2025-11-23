<div class="space-y-4">
    <div>
        <h3 class="text-sm font-medium text-gray-500">User</h3>
        <p class="mt-1 text-sm text-gray-900">{{ $record->user->name ?? 'Unknown' }}</p>
    </div>
    
    <div>
        <h3 class="text-sm font-medium text-gray-500">Message</h3>
        <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $record->content ?? '—' }}</p>
    </div>
    
    @if(!empty($record->attachments) && is_array($record->attachments))
        <div>
            <h3 class="text-sm font-medium text-gray-500">Attachments</h3>
            <ul class="mt-1 space-y-1">
                @foreach($record->attachments as $attachment)
                    <li class="text-sm">
                        <a href="{{ Storage::url($attachment) }}" target="_blank" class="text-primary-600 hover:text-primary-800">
                            {{ basename($attachment) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <div>
        <h3 class="text-sm font-medium text-gray-500">Created</h3>
        <p class="mt-1 text-sm text-gray-900">{{ $record->created_at->format('Y-m-d H:i:s') }}</p>
    </div>
</div>

