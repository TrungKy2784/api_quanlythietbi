<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http; // Thêm dòng này
use Illuminate\Http\Request;
use App\Models\PhieuTraThietBi;
use App\Models\TaiSanNhapCT;
use App\Models\PhieuCapThietBi;
use DB;
use Illuminate\Support\Facades\Log;

class PhieuTraThietBiController extends Controller
{
    public function index()
    {
        return response()->json(PhieuTraThietBi::all());
    }
    public function duyet(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject', // approve: duyệt, reject: từ chối
        ]);

        $phieu = PhieuTraThietBi::find($id);

        if (!$phieu) {
            return response()->json(['message' => 'Không tìm thấy phiếu trả thiết bị'], 404);
        }

        if ($phieu->TrangThaiDeXuat != 0) {
            return response()->json(['message' => 'Phiếu không còn ở trạng thái chờ duyệt'], 400);
        }

        $action = $request->action;
        $trangThaiMoi = $action === 'approve' ? 1 : 2; // 1: Đã duyệt, 2: Từ chối

        $phieu->TrangThaiDeXuat = $trangThaiMoi;
        $phieu->updated_at = now();

        // Nếu là duyệt thì cập nhật trạng thái tài sản
        if ($action === 'approve') {
            // Lấy phiếu cấp liên quan để lấy MaTaiSanNhap
            $phieuCap = PhieuCapThietBi::where('MaDuPhong', $phieu->MaPhieuCapThietBi)->first();
            if ($phieuCap && $phieuCap->MaTaiSanNhap) {
                // Cập nhật trạng thái tài sản theo trạng thái lúc trả
                $trangThaiMap = [
                    'Bình thường' => 'TT002', // Sẵn sàng cấp lại
                    'Hư hỏng' => 'TT003',     // Cần sửa chữa
                    'Mất' => 'TT004'          // Đã mất
                ];
                TaiSanNhapCT::where('MaTaiSanNhap', $phieuCap->MaTaiSanNhap)
                    ->update([
                        'MaTrangThai' => $trangThaiMap[$phieu->TrangThaiLucTra] ?? 'TT002',
                        'updated_at' => now()
                    ]);
                // Xóa tài sản khỏi phiếu cấp
                $phieuCap->update(['MaTaiSanNhap' => null]);
            }
        }

        $phieu->save();

        $message = $action === 'approve'
            ? 'Đã duyệt đề xuất thành công'
            : 'Đã từ chối đề xuất';

        return response()->json(['message' => $message]);
    }

public function taoPhieuTra(Request $request)
{
    $request->validate([
        // ... các validate hiện có
    ]);

    // Kiểm tra phiếu cấp tồn tại và chưa trả
    $phieuCap = PhieuCapThietBi::where('MaDuPhong', $request->MaPhieuCapThietBi)
        ->whereNotNull('MaTaiSanNhap') // Chỉ xử lý phiếu cấp có tài sản
        ->first();

    if (!$phieuCap) {
        return response()->json([
            'success' => false,
            'message' => 'Phiếu cấp không tồn tại hoặc đã trả tài sản'
        ], 404);
    }

    DB::beginTransaction();
    try {
        // 1. Tạo phiếu trả
        $phieuTra = PhieuTraThietBi::create([
            'MaDuPhong' => $this->generateMaPhieuTra(),
            'MaNV' => $request->MaNV,
            'MaPhieuCapThietBi' => $request->MaPhieuCapThietBi,
            'TrangThaiLucTra' => $request->TrangThaiLucTra,
            'NgayTra' => $request->NgayTra,
            'GhiChu' => $request->GhiChu,
            'NoiDungDeXuat' => $request->NoiDungDeXuat,
            'NgayTaoDeXuat' => $request->NoiDungDeXuat ? now() : null,
            'TrangThaiDeXuat' => $request->NoiDungDeXuat ? 0 : null,
        ]);

        // 2. Cập nhật trạng thái tài sản
        $trangThaiMap = [
            'Bình thường' => 'TT002', // Sẵn sàng cấp lại
            'Hư hỏng' => 'TT003',     // Cần sửa chữa
            'Mất' => 'TT004'          // Đã mất
        ];
        
        TaiSanNhapCT::where('MaTaiSanNhap', $phieuCap->MaTaiSanNhap)
            ->update(['MaTrangThai' => $trangThaiMap[$request->TrangThaiLucTra]]);

        // 3. Xóa tài sản khỏi phiếu cấp
        $phieuCap->update(['MaTaiSanNhap' => null]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Tạo phiếu trả thành công',
            'data' => $phieuTra
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
        ], 500);
    }
}


protected function generateMaPhieuTra()
{
    $latest = PhieuTraThietBi::orderByDesc('id')->value('MaDuPhong');
    
    if ($latest && preg_match('/PTTB(\d+)/', $latest, $matches)) {
        $nextNumber = (int)$matches[1] + 1;
    } else {
        $nextNumber = 1;
    }

    return 'PTTB' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

private function capNhatTrangThaiTaiSan($maPhieuCap, $trangThaiLucTra)
{
    $phieuCap = PhieuCapThietBi::where('MaDuPhong', $maPhieuCap)->first();
    if (!$phieuCap || !$phieuCap->MaTaiSanNhap) {
        return;
    }

    $trangThaiMap = [
        'Bình thường' => 'TT002',
        'Hư hỏng' => 'TT003',
        'Mất' => 'TT004',
    ];

    $maTrangThai = $trangThaiMap[$trangThaiLucTra] ?? 'TT002';

    // Cập nhật trực tiếp
    TaiSanNhapCT::where('MaTaiSanNhap', $phieuCap->MaTaiSanNhap)
        ->update([
            'MaTrangThai' => $maTrangThai,
            'updated_at' => now()
        ]);

    Log::info("Đã cập nhật trạng thái tài sản trực tiếp", [
        'MaTaiSanNhap' => $phieuCap->MaTaiSanNhap,
        'TrangThai' => $maTrangThai
    ]);
}

public function store(Request $request)
{
    $request->validate([
        'MaNV' => 'required|string|max:14',
        'MaPhieuCapThietBi' => 'required|string|max:14',
        'TrangThaiLucTra' => 'required|string|in:Bình thường,Hư hỏng,Mất',
        'NgayTra' => 'required|date',
        'GhiChu' => 'nullable|string|max:300',
        'NoiDungDeXuat' => 'nullable|string|max:300',
    ]);

    // Kiểm tra phiếu cấp tồn tại
    $phieuCap = PhieuCapThietBi::where('MaDuPhong', $request->MaPhieuCapThietBi)->first();
    if (!$phieuCap) {
        return response()->json(['message' => 'Phiếu cấp không tồn tại'], 404);
    }

    // Tạo mã phiếu trả
    $maDuPhong = $this->generateMaPhieuTra();

    // Tạo phiếu trả
    $phieuTra = PhieuTraThietBi::create([
        'MaDuPhong' => $maDuPhong,
        'MaNV' => $request->MaNV,
        'MaPhieuCapThietBi' => $request->MaPhieuCapThietBi,
        'TrangThaiLucTra' => $request->TrangThaiLucTra,
        'NgayTra' => $request->NgayTra,
        'GhiChu' => $request->GhiChu,
        'NoiDungDeXuat' => $request->NoiDungDeXuat,
        'NgayTaoDeXuat' => $request->NoiDungDeXuat ? now() : null,
        'TrangThaiDeXuat' => $request->NoiDungDeXuat ? 0 : null,
    ]);

    // CHỈ cập nhật trạng thái tài sản nếu KHÔNG phải phiếu đề xuất
    if (!$request->NoiDungDeXuat && $phieuCap->MaTaiSanNhap) {
        $trangThaiMap = [
            'Bình thường' => 'TT002', // Đã trả - sẵn sàng cấp lại
            'Hư hỏng' => 'TT003',     // Hư hỏng - cần sửa chữa
            'Mất' => 'TT004',         // Mất - không còn sử dụng
        ];

        TaiSanNhapCT::where('MaTaiSanNhap', $phieuCap->MaTaiSanNhap)
            ->update([
                'MaTrangThai' => $trangThaiMap[$request->TrangThaiLucTra] ?? 'TT002',
                'updated_at' => now()
            ]);
    }

    return response()->json([
        'message' => 'Tạo phiếu trả thành công',
        'data' => $phieuTra
    ]);
}

    public function update(Request $request, $id)
{
    $phieu = PhieuTraThietBi::find($id);
    if (!$phieu) {
        return response()->json(['message' => 'Không tìm thấy phiếu trả'], 404);
    }

    $request->validate([
        'NgayTra' => 'required|date',
        'MaNV' => 'required|string|max:14',
        'MaPhieuCapThietBi' => 'required|string|max:14',
        'TrangThaiLucTra' => 'required|string|in:Bình thường,Hư hỏng,Mất',
        'NoiDungDeXuat' => 'nullable|string|max:300',
        'GhiChu' => 'nullable|string|max:300',
        'NgayTaoDeXuat' => 'nullable|date',
        'TrangThaiDeXuat' => 'nullable|integer|in:0,1',
    ]);

    $phieu->update([
        'MaNV' => $request->MaNV,
        'MaPhieuCapThietBi' => $request->MaPhieuCapThietBi,
        'TrangThaiLucTra' => $request->TrangThaiLucTra,
        'NgayTra' => $request->NgayTra,
        'GhiChu' => $request->GhiChu,
        'NoiDungDeXuat' => $request->NoiDungDeXuat,
        'NgayTaoDeXuat' => $request->NgayTaoDeXuat,
        'TrangThaiDeXuat' => $request->TrangThaiDeXuat,
    ]);

    // Nếu phiếu trả đã được duyệt (TrangThaiDeXuat == 1), cập nhật trạng thái tài sản
    if ($phieu->TrangThaiDeXuat === 1) {
        // Gọi hàm cập nhật trạng thái tài sản, có thể truyền phiếu cấp và trạng thái trả
        $this->capNhatTrangThaiTaiSan($phieu->MaPhieuCapThietBi, $phieu->TrangThaiLucTra);
    }

    return response()->json(['message' => 'Cập nhật phiếu trả thành công', 'data' => $phieu]);
}

    public function destroy($id)
    {
        $phieu = PhieuTraThietBi::find($id);
        if (!$phieu) {
            return response()->json(['message' => 'Phiếu trả không tồn tại'], 404);
        }

        $phieu->delete();
        return response()->json(['message' => 'Xóa thành công']);
    }
    
   public function getPhieuTraDangChoDuyet()
{
    $phieus = PhieuTraThietBi::where('phieu_tra_thiet_bi.TrangThaiDeXuat', 0)
        ->leftJoin('nhan_vien', 'phieu_tra_thiet_bi.MaNV', '=', 'nhan_vien.MaDuPhong')
        ->leftJoin('phieu_cap_thiet_bi', 'phieu_tra_thiet_bi.MaPhieuCapThietBi', '=', 'phieu_cap_thiet_bi.MaDuPhong')
        ->select(
            'phieu_tra_thiet_bi.*',
            'nhan_vien.HoTen as TenNguoiTra',
            'phieu_cap_thiet_bi.MaTaiSanNhap',
            'phieu_cap_thiet_bi.MaNVNhanTB'
        )
        ->orderByDesc('phieu_tra_thiet_bi.NgayTaoDeXuat')
        ->get();

    return response()->json($phieus);
}
}
