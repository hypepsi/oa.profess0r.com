@php
    $fileUrl = asset('storage/' . $record->file_path);
    $fileType = $record->file_type;
    $isImage = str_starts_with($fileType, 'image/');
    $isPdf = $fileType === 'application/pdf';
    $isText = $fileType === 'text/plain';
    $isOffice = in_array($fileType, [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ]);
@endphp

<div class="space-y-4">
    @if ($isImage)
        {{-- Image Preview --}}
        <div class="flex justify-center rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
            <img src="{{ $fileUrl }}" alt="{{ $record->title }}" class="max-h-[70vh] max-w-full rounded">
        </div>
    
    @elseif ($isPdf)
        {{-- PDF Preview --}}
        <div class="w-full" style="height: 75vh;">
            <iframe src="{{ $fileUrl }}" class="h-full w-full rounded-lg border-0"></iframe>
        </div>
    
    @elseif ($isText)
        {{-- Text File Preview --}}
        <div class="max-h-[70vh] overflow-auto rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
            <pre class="whitespace-pre-wrap text-sm text-gray-900 dark:text-white">{{ Storage::disk('public')->get($record->file_path) }}</pre>
        </div>
    
    @elseif ($isOffice)
        {{-- Office Documents via Google Docs Viewer --}}
        <div class="w-full" style="height: 75vh;">
            <iframe src="https://docs.google.com/viewer?url={{ urlencode($fileUrl) }}&embedded=true" class="h-full w-full rounded-lg border-0"></iframe>
        </div>
    
    @else
        {{-- Unsupported File Type --}}
        <div class="py-12 text-center">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="mx-auto mb-4 h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-base font-medium text-gray-900 dark:text-white">Preview not available</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">This file type cannot be previewed online</p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Please download to view</p>
            </div>
        </div>
    @endif
</div>
