<?php

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Auth\LoginController;




Route::get('/db-test', function () {
    try {
        DB::connection()->getPdo();
        return "✅ Đã kết nối thành công tới SQL Server: " . DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        return "❌ Lỗi kết nối: " . $e->getMessage();
    }
});
Route::prefix('api')
     ->middleware('api')
     ->group(base_path('routes/api.php'));
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');

// routes/web.php
Route::get('/img/{filename}', function ($filename) {
    $path = public_path('img/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
})->where('filename', '.*');