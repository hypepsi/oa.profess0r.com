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
        {{-- 图片直接显示 --}}
        <div class="flex justify-center bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <img src="{{ $fileUrl }}" alt="{{ $record->title }}" class="max-w-full h-auto max-h-[70vh] rounded">
        </div>
    
    @elseif ($isPdf)
        {{-- PDF使用iframe --}}
        <div class="w-full" style="height: 75vh;">
            <iframe src="{{ $fileUrl }}" class="w-full h-full border-0 rounded-lg"></iframe>
        </div>
    
    @elseif ($isText)
        {{-- 文本文件 --}}
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-auto" style="max-height: 70vh;">
            <pre class="whitespace-pre-wrap oa-body">{{ Storage::disk('public')->get($record->file_path) }}</pre>
        </div>
    
    @elseif ($isOffice)
        {{-- Office文档用Google Docs Viewer --}}
        <div class="w-full" style="height: 75vh;">
            <iframe src="https://docs.google.com/viewer?url={{ urlencode($fileUrl) }}&embedded=true" class="w-full h-full border-0 rounded-lg"></iframe>
        </div>
    
    @else
        {{-- 不支持预览 --}}
        <div class="text-center py-12">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="oa-card-value">Preview not available</p>
                <p class="oa-body mt-2">This file type cannot be previewed online.</p>
                <p class="oa-helper mt-1">Please download to view.</p>
            </div>
        </div>
    @endif
</div>
