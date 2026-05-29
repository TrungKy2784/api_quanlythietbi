<?php

namespace App\Http\Middleware;

// PHẢI use chính xác class Middleware gốc này của Laravel
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware; 

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',             // Bỏ qua toàn bộ các route có tiền tố api/
        'login',             // Bỏ qua route login (vì nằm ngoài api/)
        'register',          // Bỏ qua route register
        'forgot-password',   // Bỏ qua route quên mật khẩu
        'reset-password',    // Bỏ qua route reset mật khẩu
    ];
}