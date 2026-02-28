<?php

use App\Models\EmailAttachment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| 访问根路径时，直接跳转到 Filament 后台（/admin）
| 其它（如 Filament 自己注册的 /admin 路由）保持不变。
*/

Route::get('/', function () {
    return redirect()->to('/admin');
})->name('home.redirect');

// Email attachment download (requires auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/email/attachments/{attachment}/download', function (EmailAttachment $attachment) {
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->filename);
    })->name('email.attachment.download');
});
