@php
    $update = $update ?? null;
    $attachments = $update->attachments ?? [];
    $hasAttachments = !empty($attachments) && is_array($attachments) && count($attachments) > 0;
@endphp

@if($hasAttachments)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($attachments as $attachment)
            @php
                $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($attachment);
                $fileName = basename($attachment);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
                $isPdf = $fileExt === 'pdf';
            @endphp
            <div class="border rounded-lg p-3 bg-gray-50 hover:bg-gray-100 transition">
                @if($isImage)
                    <div class="mb-2">
                        <img src="{{ $fileUrl }}" alt="{{ $fileName }}" 
                             class="w-full h-auto rounded border cursor-pointer hover:opacity-90"
                             onclick="window.open('{{ $fileUrl }}', '_blank')"
                             style="max-height: 200px; object-fit: contain;">
                    </div>
                @elseif($isPdf)
                    <div class="mb-2">
                        <iframe src="{{ $fileUrl }}#toolbar=0" 
                                class="w-full border rounded"
                                style="height: 300px;"
                                title="{{ $fileName }}"></iframe>
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 truncate" title="{{ $fileName }}">{{ $fileName }}</span>
                    <a href="{{ $fileUrl }}" target="_blank" 
                       class="text-sm text-primary-600 hover:text-primary-800 underline ml-2 whitespace-nowrap">
                        {{ $isPdf ? 'View PDF' : ($isImage ? 'Open' : 'Download') }}
                    </a>
                </div>
                @if($update && $update->user)
                    <div class="text-xs text-gray-500 mt-1">
                        by {{ $update->user->name }} • {{ $update->created_at->setTimezone('Asia/Shanghai')->diffForHumans() }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-500">No attachments in the latest update.</p>
@endif
