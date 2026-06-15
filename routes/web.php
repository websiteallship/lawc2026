<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/player');
});

Route::get('/player/afk-logout', function (\Illuminate\Http\Request $request) {
    if (\Illuminate\Support\Facades\Auth::check()) {
        \Illuminate\Support\Facades\Auth::logout();
    }
    
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/player/login')->withErrors([
        'email' => 'Đã tự động đăng xuất do treo máy (không tương tác thời gian dài).',
    ]);
});
