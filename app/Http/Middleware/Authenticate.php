<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Xác định nơi redirect khi không xác thực.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null; // Không redirect nếu là request API
        }

        return route('Login'); // Hoặc thay bằng '/login' nếu chưa có route tên 'login'
    }
}
