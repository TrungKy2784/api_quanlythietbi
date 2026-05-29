<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\HoaDonNhapChiTiet;
use App\Models\TaiSanNhapCT;

class HoaDonNhapChiTietController extends Controller
{
    public function index()
    {
        return HoaDonNhapChiTiet::all();
    }
public function getByMaHoaDonNhap($maHoaDon)
{
    $chiTiet = DB::table('hoa_don_nhap_chi_tiet')
        ->leftJoin('hoa_don_nhap', 'hoa_don_nhap_chi_tiet.MaHoaDonNhap', '=', 'hoa_don_nhap.MaDuPhong')
        ->leftJoin('nhan_vien', 'hoa_don_nhap.MaNV', '=', 'nhan_vien.MaDuPhong')
        ->leftJoin('nha_cung_cap', 'hoa_don_nhap.IDNCC', '=', 'nha_cung_cap.MaDuPhong')
        ->leftJoin('tai_san', 'hoa_don_nhap_chi_tiet.MaTaiSan', '=', 'tai_san.MaDuPhong')
        ->where('hoa_don_nhap_chi_tiet.MaHoaDonNhap', $maHoaDon)
        ->select(
            'hoa_don_nhap_chi_tiet.*',
            'nhan_vien.HoTen',
            'nha_cung_cap.TenNCC',
            'tai_san.TenTaiSan'
        )
        ->get();

    return response()->json($chiTiet);
}

    public function show($id)
    {
        return HoaDonNhapChiTiet::findOrFail($id);
    }

    private function generateMaDuPhong()
    {
        $latestCode = HoaDonNhapChiTiet::where('MaDuPhong', 'like', 'HDNCT%')
            ->orderByRaw("CAST(SUBSTRING(MaDuPhong, 6) AS UNSIGNED) DESC")
            ->value('MaDuPhong');

        if ($latestCode && preg_match('/HDNCT(\d+)/', $latestCode, $matches)) {
            $next = (int)$matches[1] + 1;
        } else {
            $next = 1;
        }

        return 'HDNCT' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'MaHoaDonNhap' => 'required|string',
            'MaTaiSan' => 'required|string',
            'SoLuong' => 'required|numeric|min:1',
            'DonGia' => 'nullable|integer',
            'NhanHieu' => 'nullable|string|max:150',
        ]);

        DB::beginTransaction();
        try {
            $maDuPhongCT = $this->generateMaDuPhong();

            $hoaDonChiTiet = HoaDonNhapChiTiet::create([
                'MaHoaDonNhap' => $validated['MaHoaDonNhap'],
                'MaTaiSan' => $validated['MaTaiSan'],
                'SoLuong' => $validated['SoLuong'],
                'DonGia' => $validated['DonGia'] ?? null,
                'NhanHieu' => $validated['NhanHieu'] ?? null,
                'MaTrangThai' => 'TT002', // Mặc định là chưa sử dụng
                'MaDuPhong' => $maDuPhongCT,
            ]);

            for ($i = 0; $i < $validated['SoLuong']; $i++) {
                TaiSanNhapCT::create([
                    'MaTaiSanNhap' => $maDuPhongCT . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'MaHoaDonNhapCT' => $maDuPhongCT,
                    'MaTaiSan' => $validated['MaTaiSan'],
                    'NgayBatDauBaoHanh' => now(),
                    'NgayHetHanBaoHanh' => now()->addYears(2),
                    'MaTrangThai' => 'TT002',
                ]);
            }

            DB::commit();
            return response()->json($hoaDonChiTiet, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Lỗi khi tạo chi tiết hóa đơn: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $hoaDon = HoaDonNhapChiTiet::where('MaDuPhong', $id)->firstOrFail();

        // Xoá luôn tài sản nhập chi tiết liên quan nếu cần
        TaiSanNhapCT::where('MaHoaDonNhapCT', $hoaDon->MaDuPhong)->delete();

        $hoaDon->delete();
        return response()->json(null, 204);
    }
}
