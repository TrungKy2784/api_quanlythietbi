<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');  // Đảm bảo bạn có view đăng nhập tại resources/views/auth/login.blade.php
    }
}
