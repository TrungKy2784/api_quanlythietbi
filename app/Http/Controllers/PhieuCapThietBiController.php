<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PhieuCapThietBi; 

class PhieuCapThietBiController extends Controller
{
// Trong PhieuCapThietBiController
public function index()
{
    $records = DB::table('phieu_cap_thiet_bi')
    ->leftJoin('co_so', 'phieu_cap_thiet_bi.MaCoSo', '=', 'co_so.MaDuPhong')
    ->leftJoin('tai_san_nhap_ct', 'phieu_cap_thiet_bi.MaTaiSanNhap', '=', 'tai_san_nhap_ct.MaTaiSanNhap')
    ->leftJoin('tai_san', 'tai_san_nhap_ct.MaTaiSan', '=', 'tai_san.MaDuPhong')
    ->where(function($query) {
        $query->whereNotIn('phieu_cap_thiet_bi.TrangThaiDeXuat', [0, 2])
              ->orWhereNull('phieu_cap_thiet_bi.TrangThaiDeXuat');
    })
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('phieu_tra_thiet_bi')
            ->whereColumn('phieu_tra_thiet_bi.MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong')
            ->where(function($subQuery) {
                $subQuery->whereNotIn('phieu_tra_thiet_bi.TrangThaiDeXuat', [0, 2])
                        ->orWhereNull('phieu_tra_thiet_bi.TrangThaiDeXuat');
            });
    })
    ->where(function($query) {
        $query->whereNull('tai_san.IDLoaiTaiSan')
              ->orWhere('tai_san.IDLoaiTaiSan', '<>', 'LTS003');
    })
    ->orderByDesc('phieu_cap_thiet_bi.id')
    ->select('phieu_cap_thiet_bi.*', 'co_so.TenCoSo')
    ->get();

    return response()->json($records);
}


    public function getAllPhieuCap()
    {
        $records = DB::table('phieu_cap_thiet_bi')
            ->leftJoin('phieu_tra_thiet_bi', function($join) {
                $join->on('phieu_cap_thiet_bi.MaDuPhong', '=', 'phieu_tra_thiet_bi.MaPhieuCapThietBi')
                    ->where('phieu_tra_thiet_bi.TrangThaiDeXuat', '=', 1); // Chỉ join với phiếu trả đã được duyệt
            })
            ->select(
                'phieu_cap_thiet_bi.*',
                DB::raw('CASE WHEN phieu_tra_thiet_bi.id IS NOT NULL THEN 1 ELSE 0 END as daTra'),
                'phieu_tra_thiet_bi.NgayTra',
                'phieu_tra_thiet_bi.TrangThaiLucTra'
            )
            ->orderByDesc('phieu_cap_thiet_bi.id')
            ->get();
            
        return response()->json($records);
    }
public function store(Request $request)
{
    // Kiểm tra xem có phải là đề xuất không
    $isDeXuat = $request->has('TrangThaiDeXuat') && $request->TrangThaiDeXuat !== null;
    
    // Lấy danh sách thiết bị (có thể là mảng hoặc string đơn)
    $taiSanList = $request->MaTaiSanNhap;
    if (!is_array($taiSanList)) {
        $taiSanList = [$taiSanList];
    }

    // Lọc bỏ các giá trị null/empty
    $taiSanList = array_filter($taiSanList, function($item) {
        return !empty($item);
    });

    // Kiểm tra nếu không có tài sản nào
    if (empty($taiSanList)) {
        return response()->json(['message' => 'Không có tài sản nào được chọn.'], 400);
    }

    // Kiểm tra tài sản (dù là đề xuất hay cấp trực tiếp)
    foreach ($taiSanList as $maTaiSan) {
        $taiSan = DB::table('tai_san_nhap_ct')->where('MaTaiSanNhap', $maTaiSan)->first();

        if (!$taiSan) {
            return response()->json(['message' => "Không tìm thấy tài sản: {$maTaiSan}"], 404);
        }

        // Kiểm tra trạng thái tài sản - không cho phép cấp/đề xuất thiết bị hư hoặc mất
        if (in_array($taiSan->MaTrangThai, ['TT003', 'TT004'])) {
            $action = $isDeXuat ? 'đề xuất cấp' : 'cấp phát';
            return response()->json(['message' => "Tài sản {$maTaiSan} đã bị hỏng hoặc mất, không thể {$action}."], 400);
        }

        // Kiểm tra tài sản đã được cấp phát chưa (chỉ áp dụng cho cấp trực tiếp)
        if (!$isDeXuat) {
            $daCap = DB::table('phieu_cap_thiet_bi')
                ->where('MaTaiSanNhap', $maTaiSan)
                ->where('TrangThaiDeXuat', '!=', 2) // Không tính các đề xuất bị từ chối
                ->exists();
            
            if ($daCap) {
                return response()->json(['message' => "Tài sản {$maTaiSan} đã được cấp phát trước đó."], 400);
            }
        }
    }

    DB::beginTransaction();
    
    try {
        $createdRecords = [];
        
        foreach ($taiSanList as $maTaiSan) {
            // Sinh mã cho từng phiếu
            $latest = DB::table('phieu_cap_thiet_bi')->orderByDesc('id')->value('MaDuPhong');
            $nextNumber = ($latest && preg_match('/PCTB(\d+)/', $latest, $matches)) ? ((int)$matches[1] + 1) : 1;
            $maDuPhong = 'PCTB' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Xác định trạng thái đề xuất
            $trangThaiDeXuat = $isDeXuat ? 0 : null;

            // Chuẩn bị data để insert
            $insertData = [
                'MaDuPhong' => $maDuPhong,
                'NgayCap' => $request->NgayCap,
                'MaNV' => $request->MaNV,
                'MaNVNhanTB' => $request->MaNVNhanTB,
                'MaBoPhan' => $request->MaBoPhan,
                'MaCoSo' => $request->MaCoSo,
                'TrangThaiLucCap' => $request->TrangThaiLucCap,
                'GhiChu' => $request->GhiChu,
                'NgayHetHanCap' => $request->NgayHetHanCap,
                'TrangThaiDeXuat' => $trangThaiDeXuat,
                'MaTaiSanNhap' => $maTaiSan,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Nếu là đề xuất, thêm các trường đề xuất
            if ($isDeXuat) {
                $insertData['NoiDungDeXuat'] = $request->NoiDungDeXuat;
                $insertData['NgayTaoDeXuat'] = now();
            }

            // Lưu phiếu
            $id = DB::table('phieu_cap_thiet_bi')->insertGetId($insertData);
            $createdRecords[] = $id;

            // Nếu là cấp trực tiếp thì cập nhật trạng thái tài sản
            if (!$isDeXuat) {
                $this->capNhatTrangThaiTaiSan($maTaiSan);
            }
        }

        DB::commit();

        return response()->json([
            'message' => $isDeXuat 
                ? 'Tạo đề xuất cấp thành công, chờ duyệt' 
                : 'Tạo phiếu cấp thành công',
            'ids' => $createdRecords,
            'count' => count($createdRecords),
            'isDeXuat' => $isDeXuat
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json(['message' => 'Lỗi khi tạo phiếu cấp: ' . $e->getMessage()], 500);
    }
}

    public function getPhieuDangChoDuyet()
{
    $phieus = DB::table('phieu_cap_thiet_bi')
        ->where('TrangThaiDeXuat', 0) // Chỉ lấy những phiếu có trạng thái chờ duyệt
        ->leftJoin('nhan_vien', 'phieu_cap_thiet_bi.MaNV', '=', 'nhan_vien.MaDuPhong')
        ->leftJoin('nhan_vien as nhan_vien_nhan', 'phieu_cap_thiet_bi.MaNVNhanTB', '=', 'nhan_vien_nhan.MaDuPhong')
        ->leftJoin('bo_phan', 'phieu_cap_thiet_bi.MaBoPhan', '=', 'bo_phan.MaDuPhong')
        ->leftJoin('co_so', 'phieu_cap_thiet_bi.MaCoSo', '=', 'co_so.MaDuPhong')
        ->leftJoin('tai_san_nhap_ct', 'phieu_cap_thiet_bi.MaTaiSanNhap', '=', 'tai_san_nhap_ct.MaTaiSanNhap')
        ->leftJoin('tai_san', 'tai_san_nhap_ct.MaTaiSan', '=', 'tai_san.MaDuPhong')
        ->select(
            'phieu_cap_thiet_bi.*',
            'nhan_vien.HoTen as TenNguoiTao',
            'nhan_vien_nhan.HoTen as TenNguoiNhan',
            'bo_phan.TenBoPhan',
            'co_so.TenCoSo',
            'tai_san.TenTaiSan',
            'tai_san_nhap_ct.MaTaiSan'
        )
        ->orderByDesc('phieu_cap_thiet_bi.NgayTaoDeXuat')
        ->get();

    return response()->json($phieus);
}
    public function duyetDeXuat(Request $request, $id)
{
    $request->validate([
        'action' => 'required|in:approve,reject', // approve: duyệt, reject: từ chối
        'MaTaiSanNhap' => 'required_if:action,approve', // Bắt buộc khi duyệt
        'TrangThaiLucCap' => 'required_if:action,approve' // Bắt buộc khi duyệt
    ]);

    $phieu = DB::table('phieu_cap_thiet_bi')->where('id', $id)->first();

    if (!$phieu) {
        return response()->json(['message' => 'Không tìm thấy phiếu cấp thiết bị'], 404);
    }

    if ($phieu->TrangThaiDeXuat != 0) {
        return response()->json(['message' => 'Phiếu không còn ở trạng thái chờ duyệt'], 400);
    }

    $action = $request->action;
    $trangThaiMoi = $action === 'approve' ? 1 : 2; // 1: Đã duyệt, 2: Từ chối

    $updateData = [
        'TrangThaiDeXuat' => $trangThaiMoi,
        'updated_at' => now()
    ];

    // Nếu là duyệt thì cập nhật thêm thông tin
    if ($action === 'approve') {
        $updateData['MaTaiSanNhap'] = $request->MaTaiSanNhap;
        $updateData['TrangThaiLucCap'] = $request->TrangThaiLucCap;
        $updateData['NgayCap'] = now(); // Cập nhật ngày cấp khi duyệt

        // Cập nhật trạng thái tài sản
        $this->capNhatTrangThaiTaiSan($request->MaTaiSanNhap);
    }

    DB::table('phieu_cap_thiet_bi')->where('id', $id)->update($updateData);

    $message = $action === 'approve' 
        ? 'Đã duyệt đề xuất thành công' 
        : 'Đã từ chối đề xuất';

    return response()->json(['message' => $message]);
}
public function update(Request $request, $id)
{
    $record = DB::table('phieu_cap_thiet_bi')->where('id', $id)->first();
    if (!$record) {
        return response()->json(['message' => 'Không tìm thấy phiếu cấp'], 404);
    }
 
    $validated = $request->validate([
        'NgayCap' => 'required|date',
        'MaNV' => 'required|exists:nhan_viens,MaNV',
        'MaNVNhanTB' => 'required',
        'MaBoPhan' => 'required',
        'MaCoSo' => 'required',
        'TrangThaiLucCap' => 'required'
    ]);
    // Cập nhật bình thường, không xử lý duyệt đề xuất ở đây
    $maTaiSanNhapUpdate = $request->MaTaiSanNhap ?? $record->MaTaiSanNhap;

    // Nếu cập nhật là phiếu cấp trực tiếp, kiểm tra hợp lệ
                if ($request->MaTaiSanNhap) {
        DB::table('phieu_cap_thiet_bi')
            ->where('id', $id)
            ->update(['TrangThaiDeXuat' => $request->TrangThaiDeXuat]);
    }
    // Thêm validation cho request
    $validated = $request->validate([
        'NgayCap' => 'required|date',
        'MaNV' => 'required|exists:nhan_viens,MaNV',
        'MaNVNhanTB' => 'required',
        'MaBoPhan' => 'required',
        'MaCoSo' => 'required',
        'TrangThaiLucCap' => 'required'
    ]);
    
    try {
        DB::table('phieu_cap_thiet_bi')->where('id', $id)->update([
        'NgayCap' => 'required|date',
        'MaNV' => 'required|exists:nhan_viens,MaNV',
            'MaNVNhanTB' => $request->MaNVNhanTB,
            'MaBoPhan' => $request->MaBoPhan,
            'MaCoSo' => $request->MaCoSo,
            'TrangThaiLucCap' => $request->TrangThaiLucCap,
            'MaTaiSanNhap' => $maTaiSanNhapUpdate,
            'NoiDungDeXuat' => $request->NoiDungDeXuat,
            'NgayTaoDeXuat' => $request->NgayTaoDeXuat,
            'NgayHetHanCap' => $request->NgayHetHanCap,
            'TrangThaiDeXuat' => $request->TrangThaiDeXuat,
            'GhiChu' => $request->GhiChu,
            'updated_at' => now(),
        ]);

        // Nếu là phiếu cấp trực tiếp thì cập nhật trạng thái tài sản
        if ($record->TrangThaiDeXuat === null && $maTaiSanNhapUpdate) {
            DB::table('tai_san_nhap_ct')
                ->where('MaTaiSanNhap', $maTaiSanNhapUpdate)
                ->update(['MaTrangThai' => $request->TrangThaiLucCap]);
        }

        $this->capNhatTrangThaiTaiSan($maTaiSanNhapUpdate);

        return response()->json(['message' => 'Cập nhật phiếu cấp thành công']);
    } catch (\Exception $e) {
        \Log::error('Lỗi cập nhật phiếu cấp: ' . $e->getMessage());
        return response()->json(['message' => 'Đã xảy ra lỗi khi cập nhật phiếu cấp.'], 500);
    }
}



    public function destroy($id)
    {
        $record = DB::table('phieu_cap_thiet_bi')->where('id', $id)->first();
        if (!$record) {
            return response()->json(['message' => 'Không tìm thấy phiếu cấp'], 404);
        }

        DB::table('phieu_cap_thiet_bi')->where('id', $id)->delete();
        return response()->json(['message' => 'Xóa phiếu cấp thành công']);
    }

    public function getPhieuChoDuyet()
    {
        $phieus = PhieuTraThietBi::where('TrangThaiDeXuat', 0)->get();

        if ($phieus->isEmpty()) {
            return response()->json(['message' => 'Không có phiếu trả đang chờ duyệt.'], 404);
        }

        return response()->json($phieus);
    }
        private function capNhatTrangThaiTaiSan($maTaiSanNhap)
    {
        DB::table('tai_san_nhap_ct')
            ->where('MaTaiSanNhap', $maTaiSanNhap)
            ->update([
                'MaTrangThai' => 'TT001', // TT001 = Đang sử dụng
                'updated_at' => now()
            ]);
    }
public function getPhieuCapChuaTra()
{
    $phieuCapChuaTra = PhieuCapThietBi::whereNotIn('id', function($query) {
        $query->select('MaPhieuCapThietBi')->from('phieu_tra_thiet_bi');
    })->get();

    return response()->json($phieuCapChuaTra);
}


public function getPhieuCapSapHetHan()
{
    $today = Carbon::today();
    $sapHetHan = DB::table('phieu_cap_thiet_bi')
        ->whereDate('NgayHetHanCap', '>=', $today)
        ->whereDate('NgayHetHanCap', '<=', $today->copy()->addDays(7))
        ->get();

    return response()->json($sapHetHan);
}

    public function getDaHetHan()
    {
        $now = Carbon::now();

        $dsDaHetHan = PhieuCapThietBi::with(['taiSanNhap'])
            ->whereHas('taiSanNhap', function ($query) use ($now) {
                $query->where('NgayHetHanCap', '<', $now);
            })
            ->get();

        return response()->json($dsDaHetHan);
    }
public function searchByMaNVNhanTB(Request $request) {
    $request->validate([
        'maNVNhanTB' => 'required|string|max:14|regex:/^NV\d+$/'
    ], [
        'maNVNhanTB.required' => 'Mã nhân viên nhận thiết bị là bắt buộc',
        'maNVNhanTB.regex' => 'Mã nhân viên phải bắt đầu bằng NV và theo sau là số'
    ]);

    $query = PhieuCapThietBi::query()
        ->where('phieu_cap_thiet_bi.MaNVNhanTB', $request->maNVNhanTB)
        ->leftJoin('bo_phan', 'phieu_cap_thiet_bi.MaBoPhan', '=', 'bo_phan.MaDuPhong')
        ->leftJoin('tai_san_nhap_ct', 'phieu_cap_thiet_bi.MaTaiSanNhap', '=', 'tai_san_nhap_ct.MaTaiSanNhap')
        ->leftJoin('tai_san', 'tai_san_nhap_ct.MaTaiSan', '=', 'tai_san.MaDuPhong')
        ->where(function($q) {
            $q->whereNull('tai_san.IDLoaiTaiSan')
              ->orWhere('tai_san.IDLoaiTaiSan', '<>', 'LTS003');
        })
        ->where(function($query) {
            // Chỉ hiển thị phiếu cấp khi: chưa có phiếu trả HOẶC tất cả phiếu trả đều bị từ chối (trạng thái = 2)
            // Loại bỏ phiếu cấp có phiếu trả với trạng thái null (trả trực tiếp)
            $query->whereNotExists(function($subQuery) {
                      // Chưa có phiếu trả nào
                      $subQuery->from('phieu_tra_thiet_bi')
                               ->whereColumn('MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong');
                  })
                  ->orWhere(function($subQuery) {
                      // Hoặc tất cả phiếu trả đều có trạng thái = 2 (từ chối)
                      $subQuery->whereNotExists(function($innerQuery) {
                          $innerQuery->from('phieu_tra_thiet_bi')
                                     ->whereColumn('MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong')
                                     ->whereNotIn('TrangThaiDeXuat', [2]); // Không có phiếu trả nào khác trạng thái 2
                      });
                  });
        })
        ->where(function($query) {
            // Loại bỏ phiếu cấp có phiếu trả với trạng thái null (trả trực tiếp)
            $query->whereNotExists(function($subQuery) {
                $subQuery->from('phieu_tra_thiet_bi')
                         ->whereColumn('MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong')
                         ->whereNull('TrangThaiDeXuat'); // Phiếu trả có trạng thái null
            });
        })
        ->where(function($query) {
            // Thêm điều kiện: phiếu trả mới nhất KHÔNG được ở trạng thái chờ duyệt (0)
            $query->whereNotExists(function($subQuery) {
                $subQuery->from('phieu_tra_thiet_bi as ptr_latest')
                         ->whereColumn('ptr_latest.MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong')
                         ->where('ptr_latest.TrangThaiDeXuat', 0) // Trạng thái chờ duyệt
                         ->whereNotExists(function($innerQuery) {
                             // Đảm bảo đây là phiếu trả mới nhất
                             $innerQuery->from('phieu_tra_thiet_bi as ptr_newer')
                                        ->whereColumn('ptr_newer.MaPhieuCapThietBi', 'ptr_latest.MaPhieuCapThietBi')
                                        ->whereRaw('ptr_newer.created_at > ptr_latest.created_at'); // Hoặc dùng trường ngày phù hợp
                         });
            });
        })
        ->where(function($q) {
            // Hiện tất cả phiếu cấp (trực tiếp hoặc đề xuất đã duyệt)
            $q->whereNull('phieu_cap_thiet_bi.TrangThaiDeXuat') // Cấp trực tiếp
              ->orWhereNotIn('phieu_cap_thiet_bi.TrangThaiDeXuat', [0, 2]); // Đề xuất đã duyệt
        })
        ->select(
            'bo_phan.TenBoPhan',
            'phieu_cap_thiet_bi.*',
            'tai_san.TenTaiSan',
            'tai_san.DonViTinh',
            'tai_san.HinhAnh as HinhAnhTaiSan'
        )
        ->orderByDesc('phieu_cap_thiet_bi.NgayCap');

    // Thêm thông tin nhân viên nếu cần
    if ($request->has('with_nhanvien')) {
        $query->with(['nhanVienNhan' => function($q) {
            $q->select('MaNV', 'HoTen');
        }]);
    }

    $phieuCaps = $query->get();

    return response()->json([
        'success' => true,
        'data' => $phieuCaps
    ]);
}

public function searchByTenNVNhanTB(Request $request)
{
    $request->validate([
        'tenNVNhanTB' => 'required|string|max:255'
    ], [
        'tenNVNhanTB.required' => 'Tên nhân viên nhận thiết bị là bắt buộc'
    ]);

    $query = PhieuCapThietBi::query()
        ->join('nhan_vien', 'phieu_cap_thiet_bi.MaNVNhanTB', '=', 'nhan_vien.MaDuPhong')
        ->leftJoin('bo_phan', 'phieu_cap_thiet_bi.MaBoPhan', '=', 'bo_phan.MaDuPhong')
        ->leftJoin('tai_san_nhap_ct', 'phieu_cap_thiet_bi.MaTaiSanNhap', '=', 'tai_san_nhap_ct.MaTaiSanNhap')
        ->leftJoin('tai_san', 'tai_san_nhap_ct.MaTaiSan', '=', 'tai_san.MaDuPhong')
        ->where('nhan_vien.HoTen', 'like', '%' . $request->tenNVNhanTB . '%')
        ->where(function($q) {
            $q->whereNull('tai_san.IDLoaiTaiSan')
              ->orWhere('tai_san.IDLoaiTaiSan', '<>', 'LTS003');
        })
        ->where(function($query) {
            // Chỉ hiển thị phiếu cấp khi: chưa có phiếu trả HOẶC tất cả phiếu trả đều bị từ chối (trạng thái = 2)
            // Loại bỏ phiếu cấp có phiếu trả với trạng thái null (trả trực tiếp)
            $query->whereNotExists(function($subQuery) {
                      // Chưa có phiếu trả nào
                      $subQuery->from('phieu_tra_thiet_bi')
                               ->whereColumn('MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong');
                  })
                  ->orWhere(function($subQuery) {
                      // Hoặc tất cả phiếu trả đều có trạng thái = 2 (từ chối)
                      $subQuery->whereNotExists(function($innerQuery) {
                          $innerQuery->from('phieu_tra_thiet_bi')
                                     ->whereColumn('MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong')
                                     ->whereNotIn('TrangThaiDeXuat', [2]); // Không có phiếu trả nào khác trạng thái 2
                      });
                  });
        })
        ->where(function($query) {
            // Loại bỏ phiếu cấp có phiếu trả với trạng thái null (trả trực tiếp)
            $query->whereNotExists(function($subQuery) {
                $subQuery->from('phieu_tra_thiet_bi')
                         ->whereColumn('MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong')
                         ->whereNull('TrangThaiDeXuat'); // Phiếu trả có trạng thái null
            });
        })
        ->where(function($query) {
            // Thêm điều kiện: phiếu trả mới nhất KHÔNG được ở trạng thái chờ duyệt (0)
            $query->whereNotExists(function($subQuery) {
                $subQuery->from('phieu_tra_thiet_bi as ptr_latest')
                         ->whereColumn('ptr_latest.MaPhieuCapThietBi', 'phieu_cap_thiet_bi.MaDuPhong')
                         ->where('ptr_latest.TrangThaiDeXuat', 0) // Trạng thái chờ duyệt
                         ->whereNotExists(function($innerQuery) {
                             // Đảm bảo đây là phiếu trả mới nhất
                             $innerQuery->from('phieu_tra_thiet_bi as ptr_newer')
                                        ->whereColumn('ptr_newer.MaPhieuCapThietBi', 'ptr_latest.MaPhieuCapThietBi')
                                        ->whereRaw('ptr_newer.created_at > ptr_latest.created_at'); // Hoặc dùng trường ngày phù hợp
                         });
            });
        })
        ->where(function($q) {
            // Hiện tất cả phiếu cấp (trực tiếp hoặc đề xuất đã duyệt)
            $q->whereNull('phieu_cap_thiet_bi.TrangThaiDeXuat') // Cấp trực tiếp
              ->orWhereNotIn('phieu_cap_thiet_bi.TrangThaiDeXuat', [0, 2]); // Đề xuất đã duyệt
        })
        ->select(
            'bo_phan.TenBoPhan',
            'phieu_cap_thiet_bi.*',
            'tai_san.TenTaiSan',
            'tai_san.DonViTinh',
            'tai_san.HinhAnh as HinhAnhTaiSan',
            'nhan_vien.HoTen as TenNhanVien'
        )
        ->orderByDesc('phieu_cap_thiet_bi.NgayCap');

    // Thêm thông tin nhân viên nếu cần
    if ($request->has('with_nhanvien')) {
        $query->with(['nhanVienNhan' => function($q) {
            $q->select('MaNV', 'HoTen');
        }]);
    }

    $phieuCaps = $query->get();

    return response()->json([
        'success' => true,
        'data' => $phieuCaps
    ]);
}
}
