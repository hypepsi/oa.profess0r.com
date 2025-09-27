<?php

use Illuminate\Support\Facades\Route;

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
