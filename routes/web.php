<?php

use App\Models\EmailAttachment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->to('/admin');
})->name('home.redirect');

// Email attachment download (protected by Filament's auth middleware)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/email/attachments/{id}/download', function ($id) {
        $attachment = EmailAttachment::findOrFail($id);
        $disk = Storage::disk($attachment->disk);
        if (!$disk->exists($attachment->path)) {
            abort(404, 'Attachment file not found on disk.');
        }
        
        // Ensure inline display for images and pdfs, download for others
        $mime = $attachment->mime_type;
        $inlineMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        
        if (in_array($mime, $inlineMimes)) {
            return response()->file($disk->path($attachment->path), [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $attachment->filename . '"'
            ]);
        }
        
        return $disk->download($attachment->path, $attachment->filename);
    })->name('email.attachment.download');
});
