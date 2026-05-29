<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\TaiKhoan;  // Sử dụng bảng tai_khoan

class UserController extends Controller
{
    /**
     * Đăng ký tài khoản mới.
     */
    public function register(Request $request)
    {
        // Xác thực dữ liệu
        $validated = $request->validate([
            'TenDangNhap' => 'required|string|max:255|unique:tai_khoan',
            'password' => 'required|string|min:6|confirmed',
            'MaNV' => 'required|string|max:14',  // Đảm bảo MaNV có trong request
        ]);

        // Kiểm tra xem MaNV có tồn tại trong bảng nhan_vien hay không
        $nhanVien = DB::table('nhan_vien')->where('MaDuPhong', $validated['MaNV'])->first();

        if (!$nhanVien) {
            return response()->json(['error' => 'Mã nhân viên không hợp lệ'], 400);
        }

        // Tạo tài khoản mới, bao gồm TenDangNhap
        $taiKhoan = TaiKhoan::create([
            'TenDangNhap' => $validated['TenDangNhap'],  // Sử dụng TenDangNhap thay vì TenTaiKhoan
            'MatKhau' => Hash::make($validated['password']),
            'IDQuyen' => 2,  // Đảm bảo quyền hợp lệ
            'MaNV' => $validated['MaNV'],  // Truyền MaNV từ request
        ]);

        return response()->json([
            'message' => 'Đăng ký thành công',
            'user' => $taiKhoan
        ]);
    }





    /**
     * Đăng nhập người dùng và trả về token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'TenTaiKhoan' => 'required|string|max:255',  // Thay đổi từ email thành TenTaiKhoan
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Kiểm tra thông tin đăng nhập trong bảng tai_khoan
        $taiKhoan = TaiKhoan::where('TenTaiKhoan', $request->TenTaiKhoan)->first();

        if (!$taiKhoan || !Hash::check($request->password, $taiKhoan->MatKhau)) {
            return response()->json(['message' => 'Thông tin đăng nhập không chính xác'], 401);
        }

        // Tạo token cho tài khoản khi đăng nhập thành công
        $token = $taiKhoan->createToken('MyApp')->plainTextToken;

        return response()->json(['message' => 'Đăng nhập thành công', 'token' => $token]);
    }

    /**
     * Lấy thông tin người dùng hiện tại.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Đăng xuất người dùng (hủy token).
     */
    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Đăng xuất thành công']);
    }
}
