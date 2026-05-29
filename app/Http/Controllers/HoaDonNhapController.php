<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HoaDonNhap;
use App\Models\HoaDonNhapChiTiet;
use Illuminate\Support\Facades\DB;

class HoaDonNhapController extends Controller
{
    // Lấy danh sách hóa đơn
    public function index()
    {
        $hoadonnhap = DB::table('hoa_don_nhap')
            ->join('nha_cung_cap', 'hoa_don_nhap.IDNCC', '=', 'nha_cung_cap.MaDuPhong')
            ->join('nhan_vien', 'hoa_don_nhap.MaNV', '=', 'nhan_vien.MaDuPhong')
            ->select('hoa_don_nhap.*', 'nha_cung_cap.TenNCC', 'nhan_vien.HoTen')
            ->get();
        return response()->json($hoadonnhap);
    }

    private function generateMaDuPhongChiTiet()
{
    $latest = HoaDonNhapChiTiet::where('MaDuPhong', 'like', 'HDNCT%')
        ->orderByRaw("CAST(SUBSTRING(MaDuPhong, 6, LEN(MaDuPhong)) AS INT) DESC")
        ->value('MaDuPhong');

    if ($latest && preg_match('/HDNCT(\d+)/', $latest, $matches)) {
        $next = (int)$matches[1] + 1;
    } else {
        $next = 1;
    }

    return 'HDNCT' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

    // Tạo mã hóa đơn tự động
    private function generateMaDuPhong()
    {
        $latest = HoaDonNhap::orderByDesc('created_at')->first();
        if ($latest && preg_match('/HDN(\d+)/', $latest->MaDuPhong, $matches)) {
            $next = (int)$matches[1] + 1;
        } else {
            $next = 1;
        }
        return 'HDN' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // Tạo số hóa đơn tự động
    private function generateSoHoaDon()
    {
        $latest = HoaDonNhap::orderByDesc('created_at')->first();
        if ($latest && preg_match('/HD(\d+)/', $latest->SoHoaDon, $matches)) {
            $next = (int)$matches[1] + 1;
        } else {
            $next = 1;
        }
        return 'HD' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // Tạo hóa đơn nhập kèm chi tiết
    public function store(Request $request)
    {
        $validated = $request->validate([
            'MaNV' => 'required|string|exists:nhan_vien,MaDuPhong',
            'IDNCC' => 'required|string|exists:nha_cung_cap,MaDuPhong',
            'NgayNhap' => 'required|date',
            'GhiChu' => 'nullable|string',
            'chiTiet' => 'required|array|min:1',
            'chiTiet.*.MaTaiSan' => 'required|string|exists:tai_san,MaDuPhong',
            'chiTiet.*.SoLuong' => 'required|numeric|min:1',
            'chiTiet.*.DonGia' => 'nullable|integer',
            'chiTiet.*.NhanHieu' => 'nullable|string|max:150',
        ]);

        DB::beginTransaction();
        try {
            // Tạo hóa đơn nhập
            $validated['MaDuPhong'] = $this->generateMaDuPhong();
            $validated['SoHoaDon'] = $this->generateSoHoaDon();
            $hoaDon = HoaDonNhap::create($validated);

            foreach ($validated['chiTiet'] as $index => $chiTiet) {
            $maDuPhongCT = $this->generateMaDuPhongChiTiet();

            $hoaDonCT = HoaDonNhapChiTiet::create([
                'MaHoaDonNhap' => $hoaDon->MaDuPhong,
                'MaTaiSan' => $chiTiet['MaTaiSan'],
                'SoLuong' => $chiTiet['SoLuong'],
                'DonGia' => $chiTiet['DonGia'] ?? null,
                'NhanHieu' => $chiTiet['NhanHieu'] ?? null,
                'MaTrangThai' => 'TT002',
                'MaDuPhong' => $maDuPhongCT,
            ]);

            // Tạo chi tiết tài sản nhập
            for ($i = 0; $i < $chiTiet['SoLuong']; $i++) {
                DB::table('tai_san_nhap_ct')->insert([
                    'MaTaiSanNhap' => $maDuPhongCT . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'MaHoaDonNhapCT' => $maDuPhongCT,
                    'MaTaiSan' => $chiTiet['MaTaiSan'],
                    'NgayBatDauBaoHanh' => now(),
                    'NgayHetHanBaoHanh' => now()->addYears(2),
                    'MaTrangThai' => 'TT002',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }


            DB::commit();
            return response()->json(['message' => 'Tạo hóa đơn và chi tiết thành công', 'hoaDon' => $hoaDon], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Đã xảy ra lỗi: ' . $e->getMessage()], 500);
        }
    }

    // Lấy thông tin hóa đơn kèm chi tiết
    public function showWithDetails($maDuPhong)
    {
        $hoaDon = HoaDonNhap::where('MaDuPhong', $maDuPhong)->firstOrFail();
        $chiTiet = HoaDonNhapChiTiet::where('MaHoaDonNhap', $maDuPhong)->get();

        return response()->json([
            'hoaDon' => $hoaDon,
            'chiTiet' => $chiTiet
        ]);
    }

    // Cập nhật hóa đơn
    public function update(Request $request, $id)
    {
        $hoaDon = HoaDonNhap::findOrFail($id);

        $validated = $request->validate([
            'MaNV' => 'required|string|exists:nhan_vien,MaDuPhong',
            'IDNCC' => 'required|string|exists:nha_cung_cap,MaDuPhong',
            'GhiChu' => 'nullable|string',
        ]);

        $hoaDon->update($validated);

        return response()->json($hoaDon);
    }

    // Xóa hóa đơn
    public function destroy($id)
{
    $hoaDon = HoaDonNhap::findOrFail($id);

    // Kiểm tra thời gian nhập
    $created = \Carbon\Carbon::parse($hoaDon->created_at);
    if ($created->diffInDays(now()) > 7) {
        return response()->json(['error' => 'Chỉ được xóa hóa đơn trong vòng 7 ngày sau khi nhập!'], 403);
    }

    // Lấy danh sách chi tiết hóa đơn
    $chiTietList = HoaDonNhapChiTiet::where('MaHoaDonNhap', $hoaDon->MaDuPhong)->get();

    // Kiểm tra trạng thái tài sản nhập chi tiết
    foreach ($chiTietList as $chiTiet) {
        // Lấy các tài sản nhập chi tiết liên quan
        $taiSanNhapCTs = \App\Models\TaiSanNhapCT::where('MaHoaDonNhapCT', $chiTiet->MaDuPhong)->get();
        foreach ($taiSanNhapCTs as $tsct) {
            if ($tsct->MaTrangThai === 'TT001') {
                return response()->json([
                    'error' => 'Không thể xóa hóa đơn vì có tài sản đang ở trạng thái "Đang sử dụng".'
                ], 403);
            }
        }
    }

    // Xóa chi tiết hóa đơn và tài sản nhập chi tiết liên quan
    foreach ($chiTietList as $chiTiet) {
        \App\Models\TaiSanNhapCT::where('MaHoaDonNhapCT', $chiTiet->MaDuPhong)->delete();
        $chiTiet->delete();
    }

    $hoaDon->delete();

    return response()->json(['message' => 'Xóa thành công']);
}

}
