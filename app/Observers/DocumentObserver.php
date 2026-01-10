<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\ActivityLogger;

class DocumentObserver
{
    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        ActivityLogger::log(
            action: 'document_uploaded',
            model: $document,
            properties: [
                'title' => $document->title,
                'category' => $document->category,
                'file_name' => $document->file_name,
                'file_size' => $document->formatted_file_size,
            ]
        );
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        $changes = [];
        
        if ($document->wasChanged('title')) {
            $changes['title'] = [
                'from' => $document->getOriginal('title'),
                'to' => $document->title,
            ];
        }
        
        if ($document->wasChanged('category')) {
            $changes['category'] = [
                'from' => $document->getOriginal('category'),
                'to' => $document->category,
            ];
        }
        
        if ($document->wasChanged('description')) {
            $changes['description'] = [
                'from' => $document->getOriginal('description'),
                'to' => $document->description,
            ];
        }

        if (!empty($changes)) {
            ActivityLogger::log(
                action: 'document_updated',
                model: $document,
                properties: $changes
            );
        }
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        ActivityLogger::log(
            action: 'document_deleted',
            model: $document,
            properties: [
                'title' => $document->title,
                'category' => $document->category,
                'file_name' => $document->file_name,
            ]
        );
    }
}
