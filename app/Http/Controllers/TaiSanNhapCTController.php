<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TaiSanNhapCT;
use Illuminate\Support\Facades\File; // Thêm dòng này
use Illuminate\Support\Facades\Log;
class TaiSanNhapCTController extends Controller
{
    public function index()
    {
        return TaiSanNhapCT::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'MaDuPhong' => 'required|string|unique:tai_san_nhap_ct,MaDuPhong',
            'MaHoaDonNhapCT' => 'required|string',
            'MaTaiSan' => 'required|string',
            'NgayBatDauBaoHanh' => 'required|date',
            'NgayHetHanBaoHanh' => 'required|date',
            'MaTrangThai' => 'required|string',
            'SoLuong' => 'required|integer|min:1',
            'HinhAnh' => 'nullable|string',
        ]);

        $record = TaiSanNhapCT::create($data);
        return response()->json($record, 201);
    }

public function show($maTaiSanNhap)
{
    $record = TaiSanNhapCT::where('MaTaiSanNhap', $maTaiSanNhap)->first();
    if (!$record) {
        return response()->json(['message' => 'Not found'], 404);
    }
    return response()->json($record);
}
    public function indexWithDetails()
{
    $assets = DB::table('tai_san_nhap_ct')
        ->leftJoin('tai_san', 'tai_san_nhap_ct.MaTaiSan', '=', 'tai_san.MaDuPhong')
        ->leftJoin('loai_tai_san', 'tai_san.IDLoaiTaiSan', '=', 'loai_tai_san.MaDuPhong')
        ->leftJoin('phieu_cap_thiet_bi', 'tai_san_nhap_ct.MaTaiSanNhap', '=', 'phieu_cap_thiet_bi.MaTaiSanNhap')
        ->leftJoin('nhan_vien', 'phieu_cap_thiet_bi.MaNVNhanTB', '=', 'nhan_vien.MaDuPhong')
        ->leftJoin('phieu_tra_thiet_bi', 'phieu_tra_thiet_bi.MaPhieuCapThietBi', '=', 'phieu_cap_thiet_bi.MaDuPhong')
        ->select(
            'tai_san_nhap_ct.*',
            'tai_san.TenTaiSan',
            'loai_tai_san.MaDuPhong as LoaiTaiSan',
            'tai_san.HinhAnh as HinhAnhTaiSan',
            DB::raw('CASE WHEN phieu_tra_thiet_bi.MaPhieuCapThietBi IS NULL THEN phieu_cap_thiet_bi.NgayCap ELSE NULL END as NgayCap'),
            DB::raw('CASE WHEN phieu_tra_thiet_bi.MaPhieuCapThietBi IS NULL THEN phieu_cap_thiet_bi.NgayHetHanCap ELSE NULL END as NgayHetHanCap'),
            DB::raw('CASE WHEN phieu_tra_thiet_bi.MaPhieuCapThietBi IS NULL THEN nhan_vien.HoTen ELSE NULL END as NguoiMuon'),
        )
        ->get();

    return response()->json($assets);
}
public function showScan($maTaiSanNhap)
{
    $maTaiSanNhap = urldecode($maTaiSanNhap);
    
    $record = DB::table('tai_san_nhap_ct')
        ->leftJoin('tai_san', 'tai_san_nhap_ct.MaTaiSan', '=', 'tai_san.MaDuPhong')
        ->leftJoin('phieu_cap_thiet_bi', 'tai_san_nhap_ct.MaTaiSanNhap', '=', 'phieu_cap_thiet_bi.MaTaiSanNhap')
        ->leftJoin('nhan_vien', 'phieu_cap_thiet_bi.MaNVNhanTB', '=', 'nhan_vien.MaDuPhong')
        ->leftJoin('phieu_tra_thiet_bi', 'phieu_tra_thiet_bi.MaPhieuCapThietBi', '=', 'phieu_cap_thiet_bi.MaDuPhong')
        ->select(
            'tai_san_nhap_ct.*',
            'tai_san.TenTaiSan',
            'tai_san.HinhAnh as HinhAnhTaiSan',
            DB::raw('CASE WHEN phieu_tra_thiet_bi.MaPhieuCapThietBi IS NULL THEN phieu_cap_thiet_bi.NgayCap ELSE NULL END as NgayCap'),
            DB::raw('CASE WHEN phieu_tra_thiet_bi.MaPhieuCapThietBi IS NULL THEN phieu_cap_thiet_bi.NgayHetHanCap ELSE NULL END as NgayHetHanCap'),
            DB::raw('CASE WHEN phieu_tra_thiet_bi.MaPhieuCapThietBi IS NULL THEN nhan_vien.HoTen ELSE NULL END as NguoiMuon')
        )
        ->where('tai_san_nhap_ct.MaTaiSanNhap', $maTaiSanNhap)
        ->first();

    // Thêm thông tin trạng thái cấp phát
    if ($record) {
        $record->TrangThaiCapPhat = is_null($record->NgayCap) ? 'Chưa cấp phát' : 'Đã cấp phát';
        if (!is_null($record->NgayCap)) {
            $record->TrangThaiCapPhat = DB::table('phieu_tra_thiet_bi')
                ->where('MaPhieuCapThietBi', $record->MaDuPhong)
                ->exists() ? 'Đã thu hồi' : 'Đã cấp phát';
        }
    }
    return response()->json($record);
}
    public function update(Request $request, $id)
    {
        $record = TaiSanNhapCT::findOrFail($id);

        $data = $request->validate([
            'MaDuPhong' => 'sometimes|required|string|unique:tai_san_nhap_ct,MaDuPhong,' . $id,
            'MaHoaDonNhapCT' => 'required|string',
            'MaTaiSan' => 'required|string',
            'NgayBatDauBaoHanh' => 'required|date',
            'NgayHetHanBaoHanh' => 'required|date',
            'MaTrangThai' => 'required|string',
            'SoLuong' => 'required|integer|min:1',
            'HinhAnh' => 'nullable|string',
        ]);

        $record->update($data);
        return response()->json($record, 200);
    }

    public function destroy($id)
    {
        TaiSanNhapCT::destroy($id);
        return response()->json(['message' => 'Đã xóa thành công'], 200);
    }

    public function capNhatTrangThaiDangSuDung($maTaiSanNhap)
    {
        $record = TaiSanNhapCT::where('MaTaiSanNhap', $maTaiSanNhap)->first();

        if (!$record) {
            return response()->json(['message' => 'Không tìm thấy tài sản'], 404);
        }

        $record->update([
            'MaTrangThai' => 'TT001', // TT001 = Đang sử dụng
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Đã cập nhật trạng thái tài sản là đang sử dụng']);
    }
    public function getTaiSanNhapCTWithName()
    {
        // Bước 1: Lấy danh sách MaTaiSanNhap đã được cấp nhưng chưa trả
        $assetsInUse = DB::table('phieu_cap_thiet_bi as pc')
            ->leftJoin('phieu_tra_thiet_bi as pt', 'pc.MaDuPhong', '=', 'pt.MaPhieuCapThietBi')
            ->whereNull('pt.id') // Chưa trả
            ->pluck('pc.MaTaiSanNhap')
            ->toArray();

        // Bước 2: Giữ lại các thiết bị thuộc phiếu cấp hiện tại (nếu có)
        $currentPhieuCapItems = [];
        if (request('current_phieucap_id')) {
            $currentPhieuCapItems = DB::table('phieu_cap_thiet_bi')
                ->where('id', request('current_phieucap_id'))
                ->pluck('MaTaiSanNhap')
                ->toArray();

            // Giữ lại những tài sản này để không bị loại khỏi danh sách
            $assetsInUse = array_diff($assetsInUse, $currentPhieuCapItems);
        }

        // Bước 3: Lấy tài sản chưa bị cấp hoặc thuộc phiếu hiện tại
        $query = DB::table('tai_san_nhap_ct')
            ->whereNotIn('tai_san_nhap_ct.MaTaiSanNhap', $assetsInUse);

        // Bước 4: Join tên tài sản
        $data = $query->join('tai_san', 'tai_san.MaDuPhong', '=', 'tai_san_nhap_ct.MaTaiSan')
            ->select(
                'tai_san_nhap_ct.MaTaiSanNhap',
                'tai_san.TenTaiSan',
                'tai_san_nhap_ct.HinhAnh',
                'tai_san_nhap_ct.MaHoaDonNhapCT',
                'tai_san_nhap_ct.NgayBatDauBaoHanh',
                'tai_san_nhap_ct.NgayHetHanBaoHanh',
                'tai_san_nhap_ct.MaTrangThai',
                'tai_san_nhap_ct.id'
            )
            ->distinct()
            ->get();

        return response()->json($data);
    }

public function getTaiSanChuaCap() {
    // Lấy danh sách các MaTaiSanNhap đã được cấp nhưng chưa trả hoặc không bị từ chối
    $dangSuDung = DB::table('phieu_cap_thiet_bi')
        ->leftJoin('phieu_tra_thiet_bi', 'phieu_cap_thiet_bi.MaDuPhong', '=', 'phieu_tra_thiet_bi.MaPhieuCapThietBi')
        ->whereNotNull('phieu_cap_thiet_bi.MaTaiSanNhap')
        ->where(function($query) {
            // Chưa có phiếu trả
            $query->whereNull('phieu_tra_thiet_bi.MaPhieuCapThietBi')
                  // Hoặc có phiếu trả nhưng trạng thái là 0 (chờ duyệt) hoặc 2 (bị từ chối) - tức là chưa trả thực sự
                  ->orWhereIn('phieu_tra_thiet_bi.TrangThaiDeXuat', [0, 2]);
        })
        ->where(function($query) {
            $query->whereNull('phieu_cap_thiet_bi.TrangThaiDeXuat') // Phiếu cấp trực tiếp (không phải đề xuất)
                  ->orWhere('phieu_cap_thiet_bi.TrangThaiDeXuat', '<>', 2); // Hoặc đề xuất đã duyệt (khác 2)
        })
        ->select('phieu_cap_thiet_bi.MaTaiSanNhap')
        ->get()
        ->pluck('MaTaiSanNhap')
        ->toArray();

    // Truy vấn chính để lấy các tài sản khả dụng
    $data = DB::table('tai_san_nhap_ct')
        ->whereNotIn('tai_san_nhap_ct.MaTaiSanNhap', $dangSuDung)
        ->join('tai_san', 'tai_san.MaDuPhong', '=', 'tai_san_nhap_ct.MaTaiSan')
        ->where('tai_san.IDLoaiTaiSan', '<>', 'LTS001') // Thêm điều kiện loại trừ LTS001
        ->select(
            'tai_san_nhap_ct.MaTaiSanNhap', 
            'tai_san.TenTaiSan', 
            'tai_san_nhap_ct.id'
        )
        ->distinct()
        ->get();
    
    return response()->json($data);
}
public function capNhatKhiTraThietBi(Request $request, $maTaiSanNhap)
{
    $request->validate([
        'trangThai' => 'required|string|in:TT002,TT003,TT004'
    ]);

    $record = TaiSanNhapCT::where('MaTaiSanNhap', $maTaiSanNhap)->first();
    if (!$record) {
        return response()->json(['message' => 'Không tìm thấy tài sản'], 404);
    }

    $record->update([
        'MaTrangThai' => $request->trangThai,
        'updated_at' => now()
    ]);

    return response()->json(['message' => 'Đã cập nhật trạng thái tài sản']);
}
public function uploadImages(Request $request, $id)
{
    $record = TaiSanNhapCT::where('MaTaiSanNhap', $id)->first();

    if (!$record) {
        return response()->json(['message' => 'Record not found'], 404);
    }

    if ($request->hasFile('images')) {
        $uploadedImages = [];
        $currentImages = $record->HinhAnh ? json_decode($record->HinhAnh) : [];

        foreach ($request->file('images') as $image) {
            $filename = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
            $image->move(public_path('img'), $filename);
            $uploadedImages[] = $filename;
        }

        // Kết hợp ảnh cũ và ảnh mới
        $allImages = array_merge($currentImages, $uploadedImages);
        
        $record->HinhAnh = json_encode($allImages);
        $record->save();

        return response()->json([
            'message' => 'Images uploaded successfully',
            'data' => $record->fresh()
        ]);
    }

    return response()->json(['message' => 'No images uploaded'], 400);
}

public function deleteImage(Request $request, $maTaiSanNhap)
{
    $request->validate([
        'image' => 'required|string'
    ]);

    $record = TaiSanNhapCT::where('MaTaiSanNhap', $maTaiSanNhap)->first();
    if (!$record) {
        return response()->json(['message' => 'Không tìm thấy tài sản'], 404);
    }

    $images = $record->HinhAnh ? json_decode($record->HinhAnh) : [];

    // Tìm và xóa ảnh trong mảng
    $filteredImages = array_filter($images, function ($img) use ($request) {
        return $img !== $request->image;
    });

    // Xóa file vật lý
    $imagePath = public_path('img/' . $request->image);
    if (File::exists($imagePath)) {
        File::delete($imagePath);
    }

    // Cập nhật lại dữ liệu hình ảnh trong database
    $record->HinhAnh = json_encode(array_values($filteredImages));
    $record->save();

    return response()->json([
        'message' => 'Đã xóa ảnh thành công',
        'images' => $filteredImages
    ]);
}

}
