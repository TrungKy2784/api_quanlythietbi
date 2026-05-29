<?php

namespace App\Http\Controllers;

use App\Models\NhanVien;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class NhanVienController extends Controller
{
    // Lấy tất cả nhân viên
    public function index()
    {
        $nhanvien = NhanVien::all();
        return response()->json($nhanvien);
    }
    private function generateMaDuPhong()
{
    $lastMa = NhanVien::orderByDesc('MaDuPhong')->first();

    if ($lastMa && preg_match('/NV(\d+)/', $lastMa->MaDuPhong, $matches)) {
        $number = (int) $matches[1] + 1;
    } else {
        $number = 1;
    }

    return 'NV' . str_pad($number, 3, '0', STR_PAD_LEFT);
}


    // Tạo mới nhân viên
public function store(Request $request)
{
    $request->validate([
        'HoTen' => 'required',
    ]);

    $maDuPhong = $this->generateMaDuPhong();

    $nhanvien = NhanVien::create([
        'MaDuPhong' => $maDuPhong,
        'HoTen' => $request->HoTen,
        'SoDienThoai' => $request->SoDienThoai,
        'NgaySinh' => $request->NgaySinh,
        'DiaChi' => $request->DiaChi,
        'TrangThai' => $request->TrangThai ?? 0,
    ]);

    return response()->json($nhanvien, 201);
}



    // Lấy nhân viên theo ID
    public function show($id)
    {
        $nhanvien = NhanVien::find($id);

        if (!$nhanvien) {
            return response()->json(['message' => 'Nhân viên không tồn tại'], 404);
        }

        return response()->json($nhanvien);
    }

    // Cập nhật nhân viên
    public function update(Request $request, $id)
{
    $nhanvien = NhanVien::find($id);

    if (!$nhanvien) {
        return response()->json(['message' => 'Nhân viên không tồn tại'], 404);
    }

    $request->validate([
        'HoTen' => 'required',
    ]);

    $nhanvien->update([
        // không cập nhật MaDuPhong
        'HoTen' => $request->HoTen,
        'SoDienThoai' => $request->SoDienThoai,
        'NgaySinh' => $request->NgaySinh,
        'DiaChi' => $request->DiaChi,
        'TrangThai' => $request->TrangThai ?? 0,
    ]);

    return response()->json($nhanvien);
}

    // Xóa nhân viên
    public function destroy($id)
    {
        $nhanvien = NhanVien::find($id);

        if (!$nhanvien) {
            return response()->json(['message' => 'Nhân viên không tồn tại'], 404);
        }

        $nhanvien->delete();
        return response()->json(['message' => 'Nhân viên đã bị xóa']);
    }
    public function nhanVienChuaCoTaiKhoan()
    {
        try {
            $ds = DB::table('nhan_vien')
                ->whereNotIn('MaDuPhong', function ($query) {
                    $query->select('MaNV')->from('tai_khoan');
                })
                ->get();

            return response()->json($ds);
        } catch (\Exception $e) {
            \Log::error('Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage());
            return response()->json(['error' => 'Lỗi máy chủ nội bộ'], 500);
        }
    }


}
