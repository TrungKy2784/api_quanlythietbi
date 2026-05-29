<?php

namespace App\Http\Controllers;

use App\Models\TaiKhoan; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Mail; 
use App\Mail\ResetPasswordMail;

class AuthController extends Controller
{
   public function index()
   {
       // Sử dụng model TaiKhoan thay vì User
       $taiKhoans = TaiKhoan::all();

       // Trả về dữ liệu dưới dạng JSON
       return response()->json($taiKhoans);
   }

    /**
     * Đăng nhập người dùng và trả về token.
     */
public function login(Request $request)
{
    $taiKhoan = TaiKhoan::with('phanquyen')
        ->where('TenDangNhap', $request->TenDangNhap)
        ->first();

    if (!$taiKhoan || !Hash::check($request->password, $taiKhoan->MatKhau)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Kiểm tra trạng thái tài khoản
    if ($taiKhoan->TrangThai == 1) {
        return response()->json(['error' => 'Tài khoản bị khóa'], 403);
    }

    $token = $taiKhoan->createToken('YourAppName')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $taiKhoan,
        'role' => $taiKhoan->phanquyen->TenQuyen ?? null,
    ]);
}


    /**
     * Đăng ký người dùng mới.
     */
    public function register(Request $request)
    {
        // Xác thực dữ liệu
        $validated = $request->validate([
            'TenDangNhap' => 'required|string|max:255|unique:tai_khoan',
            'password' => 'required|string|min:6|confirmed',
            'MaNV' => 'required|string|max:14', 
           'email' => 'nullable|email|max:255',
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
            'email' => $validated['email'],
            'MaNV' => $validated['MaNV'],  // Truyền MaNV từ request
        ]);

        return response()->json([
            'message' => 'Đăng ký thành công',
            'user' => $taiKhoan
        ]);
    }

    public function registerAcc(Request $request)
   {
       // Xác thực dữ liệu
       $validated = $request->validate([
           'TenDangNhap' => 'required|string|max:255|unique:tai_khoan',
           'password' => 'required|string|min:6|confirmed',
           'MaNV' => 'required|string|max:14',  // Đảm bảo MaNV có trong request
           'IDQuyen' => 'exists:phan_quyen,id',
           'email' => 'nullable|email|max:255',
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
           'IDQuyen' => $validated['IDQuyen'] ?? 2,
           'MaNV' => $validated['MaNV'],  // Truyền MaNV từ request
            'email' => $validated['email'],
       ]);

       return response()->json([
           'message' => 'Đăng ký thành công',
           'user' => $taiKhoan
       ]);
   }
    /**
        * Cập nhật thông tin tài khoản
        */
public function update(Request $request, $id)
{
    $user = Auth::user();  // Lấy người dùng hiện tại

    // Kiểm tra xem người dùng có phải là admin không
  

    // Tiến hành cập nhật tài khoản
    $taiKhoan = TaiKhoan::findOrFail($id);

    $validated = $request->validate([
        'TenDangNhap' => 'required|string|max:255',
        'IDQuyen' => 'required|exists:phan_quyen,id',
        'TrangThai' => 'required|integer|in:0,1',
        'password' => 'nullable|string|min:8|confirmed',
       'email' => 'nullable|email|max:255',
    ]);

    $taiKhoan->update([
        'TenDangNhap' => $validated['TenDangNhap'],
        'IDQuyen' => $validated['IDQuyen'],
        'email' => $validated['email'],
        'TrangThai' => $validated['TrangThai'],  // Cập nhật trạng thái
        'MatKhau' => isset($validated['password']) ? bcrypt($validated['password']) : $taiKhoan->MatKhau,
    ]);

    return response()->json($taiKhoan);
}
    //đỏi mật khẩu
    public function changePassword(Request $request)
{
    $user = $request->user();

    $request->validate([
        'old_password' => 'required',
        'new_password' => 'required|min:6|confirmed',
    ]);

    // Kiểm tra mật khẩu cũ
    if (!Hash::check($request->old_password, $user->MatKhau)) {
        return response()->json(['message' => 'Mật khẩu cũ không đúng'], 400);
    }

    // Cập nhật mật khẩu mới
    $user->MatKhau = Hash::make($request->new_password);
    $user->save();

    return response()->json(['message' => 'Đổi mật khẩu thành công']);
}

       /**
        * Xóa tài khoản
        */
       public function destroy($id)
       {
           // Tìm tài khoản cần xóa
           $taiKhoan = TaiKhoan::find($id);

           if (!$taiKhoan) {
               return response()->json(['error' => 'Tài khoản không tồn tại'], 404);
           }

           // Xóa tài khoản
           $taiKhoan->delete();

           return response()->json(['message' => 'Tài khoản đã được xóa thành công']);
       }

       /**
     * Gửi email reset mật khẩu
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $taiKhoan = TaiKhoan::where('email', $request->email)->first();

        if (!$taiKhoan) {
            return response()->json(['error' => 'Email không tồn tại trong hệ thống'], 404);
        }

        // Tạo mã reset ngẫu nhiên
        $resetToken = Str::random(60);
        
        // Lưu mã reset vào database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $taiKhoan->email],
            ['token' => $resetToken, 'created_at' => now()]
        );

        // Gửi email (cần cấu hình mail trong .env)
        Mail::to($request->email)->send(new ResetPasswordMail($resetToken, $request->email));

        return response()->json(['message' => 'Link reset mật khẩu đã được gửi đến email của bạn']);
    }

    /**
     * Reset mật khẩu
     */
    public function resetPassword(Request $request)
    {
         $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        // Kiểm tra token hợp lệ
        $resetData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetData) {
            return response()->json(['error' => 'Token không hợp lệ hoặc đã hết hạn'], 400);
        }

        // Cập nhật mật khẩu mới
        $taiKhoan = TaiKhoan::where('email', $request->email)->first();
        $taiKhoan->MatKhau = Hash::make($request->password);
        $taiKhoan->save();

        // Xóa token đã sử dụng
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Mật khẩu đã được cập nhật thành công']);
    }

}
