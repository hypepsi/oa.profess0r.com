<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500">User</h3>
            <p class="mt-1 text-sm text-gray-900">{{ $record->user->name ?? 'Unknown' }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Created</h3>
            <p class="mt-1 text-sm text-gray-900">{{ $record->created_at->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
    
    @if(!empty($record->content))
        <div>
            <h3 class="text-sm font-medium text-gray-500">Message</h3>
            <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $record->content }}</p>
        </div>
    @endif
    
    @if(!empty($record->attachments) && is_array($record->attachments))
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-2">Attachments</h3>
            <div class="space-y-3">
                @foreach($record->attachments as $attachment)
                    @php
                        $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($attachment);
                        $fileName = basename($attachment);
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
                        $isPdf = $fileExt === 'pdf';
                    @endphp
                    <div class="border rounded-lg p-3 bg-gray-50">
                        @if($isImage)
                            <div class="mb-2">
                                <img src="{{ $fileUrl }}" alt="{{ $fileName }}" 
                                     class="max-w-full h-auto rounded border cursor-pointer hover:opacity-90"
                                     onclick="window.open('{{ $fileUrl }}', '_blank')"
                                     style="max-height: 400px;">
                            </div>
                        @elseif($isPdf)
                            <div class="mb-2">
                                <iframe src="{{ $fileUrl }}#toolbar=0" 
                                        class="w-full border rounded"
                                        style="height: 500px;"
                                        title="{{ $fileName }}"></iframe>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">{{ $fileName }}</span>
                            <a href="{{ $fileUrl }}" target="_blank" 
                               class="text-sm text-primary-600 hover:text-primary-800 underline">
                                {{ $isPdf ? 'View PDF' : ($isImage ? 'Open Image' : 'Download') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>


