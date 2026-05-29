<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TaiSanNhapCT;
use App\Models\PhieuCapThietBi;
use App\Models\PhieuTraThietBi;
use App\Models\TaiSan;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Lấy thống kê trạng thái thiết bị
     */
    public function getDeviceStats()
    {
        return response()->json([
            'notUsed' => TaiSanNhapCT::where('MaTrangThai', 'TT002')->count(),
            'inUse' => TaiSanNhapCT::where('MaTrangThai', 'TT001')->count(),
            'broken' => TaiSanNhapCT::where('MaTrangThai', 'TT003')->count(),
            'lost' => TaiSanNhapCT::where('MaTrangThai', 'TT004')->count(),
        ]);
    }

    /**
     * Lấy danh sách thiết bị với các bộ lọc
     */
    public function devices(Request $request)
    {
        $query = TaiSanNhapCT::query()
            ->with(['taiSan', 'phieuCap.nhanVienNhan.boPhan'])
            ->join('tai_san', 'tai_san_nhap_ct.MaTaiSan', '=', 'tai_san.MaDuPhong');

        // Lọc theo trạng thái
        if ($request->status && $request->status !== 'all') {
            $query->where('tai_san_nhap_ct.MaTrangThai', $request->status);
        }

        // Lọc theo khoảng thời gian
        if ($request->fromDate && $request->toDate) {
            $query->whereBetween('tai_san_nhap_ct.created_at', [$request->fromDate, $request->toDate]);
        }

        // Lọc theo bộ phận
        if ($request->department && $request->department !== 'all') {
            $query->whereHas('phieuCap.nhanVienNhan', function($q) use ($request) {
                $q->where('MaBoPhan', $request->department);
            });
        }

        // Lọc theo hạn bảo hành
        if ($request->warrantyStatus) {
            $now = now();
            switch ($request->warrantyStatus) {
                case 'expired':
                    $query->whereNotNull('NgayHetHanBaoHanh')
                          ->where('NgayHetHanBaoHanh', '<', $now);
                    break;
                case 'expiring_soon':
                    $soon = $now->copy()->addDays(30);
                    $query->whereNotNull('NgayHetHanBaoHanh')
                          ->where('NgayHetHanBaoHanh', '>=', $now)
                          ->where('NgayHetHanBaoHanh', '<=', $soon);
                    break;
                case 'valid':
                    $query->whereNotNull('NgayHetHanBaoHanh')
                          ->where('NgayHetHanBaoHanh', '>', now()->addDays(30));
                    break;
            }
        }

        $devices = $query->select([
            'tai_san_nhap_ct.*',
            'tai_san.TenTaiSan',
            'tai_san.NgayHetHanBaoHanh',
            DB::raw('(SELECT nv.HoTen FROM phieu_cap_thiet_bi pc 
                     JOIN nhan_vien nv ON pc.MaNVNhanTB = nv.MaDuPhong
                     WHERE pc.MaTaiSanNhap = tai_san_nhap_ct.MaTaiSanNhap LIMIT 1) as NguoiSuDung'),
            DB::raw('(SELECT bp.TenBoPhan FROM phieu_cap_thiet_bi pc 
                     JOIN nhan_vien nv ON pc.MaNVNhanTB = nv.MaDuPhong
                     JOIN bo_phan bp ON nv.MaBoPhan = bp.MaBoPhan
                     WHERE pc.MaTaiSanNhap = tai_san_nhap_ct.MaTaiSanNhap LIMIT 1) as BoPhan'),
            DB::raw('CASE 
                     WHEN tai_san.NgayHetHanBaoHanh IS NULL THEN "Không có thông tin" 
                     WHEN tai_san.NgayHetHanBaoHanh < NOW() THEN "Hết hạn" 
                     WHEN tai_san.NgayHetHanBaoHanh <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN "Sắp hết hạn" 
                     ELSE "Còn hạn" 
                   END AS TinhTrangBaoHanh')
        ])->paginate(20);

        return response()->json($devices);
    }

    /**
     * Tổng hợp thống kê tổng quan
     */
    public function summary()
    {
        $now = now();
        $soon = now()->addDays(30);
        
        return response()->json([
            'total' => TaiSanNhapCT::count(),
            'inUse' => TaiSanNhapCT::where('MaTrangThai', 'TT001')->count(),
            'notUsed' => TaiSanNhapCT::where('MaTrangThai', 'TT002')->count(),
            'damaged' => TaiSanNhapCT::where('MaTrangThai', 'TT003')->count(),
            'lost' => TaiSanNhapCT::where('MaTrangThai', 'TT004')->count(),
            'expiringSoon' => TaiSanNhapCT::whereNotNull('NgayHetHanBaoHanh')
                                ->where('NgayHetHanBaoHanh', '>=', $now)
                                ->where('NgayHetHanBaoHanh', '<=', $soon)
                                ->count(),
            'expired' => TaiSanNhapCT::whereNotNull('NgayHetHanBaoHanh')
                                ->where('NgayHetHanBaoHanh', '<', $now)
                                ->count(),
            'pendingApprovals' => PhieuCapThietBi::where('TrangThaiDeXuat', 0)->count() + 
                                PhieuTraThietBi::where('TrangThaiDeXuat', 0)->count()
        ]);
    }

    /**
     * Xu hướng theo tháng
     */
    public function trends()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('Y-m'),
                'deviceAdded' => TaiSanNhapCT::whereYear('created_at', $date->year)
                                    ->whereMonth('created_at', $date->month)
                                    ->count(),
                'deviceAssigned' => PhieuCapThietBi::whereYear('NgayCap', $date->year)
                                    ->whereMonth('NgayCap', $date->month)
                                    ->count(),
                'warrantyExpired' => TaiSanNhapCT::whereNotNull('NgayHetHanBaoHanh')
                                    ->whereYear('NgayHetHanBaoHanh', $date->year)
                                    ->whereMonth('NgayHetHanBaoHanh', $date->month)
                                    ->count()
            ];
        }
        
        return response()->json($months);
    }

    /**
     * Lấy danh sách thiết bị sắp hết hạn bảo hành
     */
    public function getExpiringSoonDevices()
    {
        $now = now();
        $soon = now()->addDays(30);
        
        $devices = TaiSanNhapCT::with(['taiSan', 'phieuCap.nhanVienNhan.boPhan'])
            ->whereNotNull('NgayHetHanBaoHanh')
            ->where('NgayHetHanBaoHanh', '>=', $now)
            ->where('NgayHetHanBaoHanh', '<=', $soon)
            ->orderBy('NgayHetHanBaoHanh')
            ->get()
            ->map(function ($device) {
                $remainingDays = now()->diffInDays(Carbon::parse($device->NgayHetHanBaoHanh));
                
                return [
                    'MaTaiSanNhap' => $device->MaTaiSanNhap,
                    'MaTaiSan' => $device->MaTaiSan,
                    'TenTaiSan' => $device->taiSan->TenTaiSan,
                    'NgayHetHanBaoHanh' => Carbon::parse($device->NgayHetHanBaoHanh)->format('d/m/Y'),
                    'ConLai' => $remainingDays . ' ngày',
                    'TinhTrang' => $remainingDays <= 7 ? 'Sắp hết hạn' : 'Gần hết hạn',
                    'NguoiSuDung' => optional($device->phieuCap)->nhanVienNhan->HoTen ?? 'Chưa cấp',
                    'BoPhan' => optional(optional($device->phieuCap)->nhanVienNhan)->boPhan->TenBoPhan ?? 'Chưa cấp',
                    'MaTrangThai' => $device->MaTrangThai,
                    'TrangThai' => $this->getStatusName($device->MaTrangThai)
                ];
            });
        
        return response()->json($devices);
    }

    /**
     * Lấy danh sách thiết bị đã hết hạn bảo hành
     */
    public function getExpiredDevices()
    {
        $devices = TaiSanNhapCT::with(['taiSan', 'phieuCap.nhanVienNhan.boPhan'])
            ->whereNotNull('NgayHetHanBaoHanh')
            ->where('NgayHetHanBaoHanh', '<', now())
            ->orderBy('NgayHetHanBaoHanh', 'desc')
            ->get()
            ->map(function ($device) {
                $expiredDays = now()->diffInDays(Carbon::parse($device->NgayHetHanBaoHanh));
                
                return [
                    'MaTaiSanNhap' => $device->MaTaiSanNhap,
                    'MaTaiSan' => $device->MaTaiSan,
                    'TenTaiSan' => $device->taiSan->TenTaiSan,
                    'NgayHetHanBaoHanh' => $device->NgayHetHanBaoHanh->format('d/m/Y'),
                    'DaHetHan' => $expiredDays . ' ngày',
                    'NguoiSuDung' => optional($device->phieuCap)->nhanVienNhan->HoTen ?? 'Chưa cấp',
                    'BoPhan' => optional(optional($device->phieuCap)->nhanVienNhan)->boPhan->TenBoPhan ?? 'Chưa cấp',
                    'MaTrangThai' => $device->MaTrangThai,
                    'TrangThai' => $this->getStatusName($device->MaTrangThai)
                ];
            });
        
        return response()->json($devices);
    }

    /**
     * Helper function để lấy tên trạng thái
     */
    private function getStatusName($statusCode)
    {
        $statuses = [
            'TT001' => 'Đang sử dụng',
            'TT002' => 'Chưa sử dụng',
            'TT003' => 'Hỏng',
            'TT004' => 'Mất',
            'TT005' => 'Sắp hết hạn bảo hành'
        ];
        
        return $statuses[$statusCode] ?? 'Không xác định';
    }
    public function getInUseDevices()
{
    $devices = DB::table('tai_san_nhap_ct as tsn')
        ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
        ->leftJoin(DB::raw('(
            SELECT 
                pctb1.MaTaiSanNhap,
                pctb1.MaNVNhanTB,
                pctb1.MaBoPhan,
                pctb1.NgayCap,
                pctb1.created_at as NgayCapThietBi
            FROM phieu_cap_thiet_bi pctb1
            WHERE pctb1.id = (
                SELECT MAX(pctb2.id)
                FROM phieu_cap_thiet_bi pctb2
                WHERE pctb2.MaTaiSanNhap = pctb1.MaTaiSanNhap
            )
        ) as pctb_latest'), 'pctb_latest.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
        ->leftJoin('nhan_vien as nv', 'pctb_latest.MaNVNhanTB', '=', 'nv.MaDuPhong')
        ->leftJoin('bo_phan as bp', 'pctb_latest.MaBoPhan', '=', 'bp.MaDuPhong')
        ->where('tsn.MaTrangThai', 'TT001')
        ->select([
            'tsn.MaTaiSanNhap as id',
            'tsn.MaTaiSan as deviceCode',
            'ts.TenTaiSan as name',
            'nv.MaDuPhong as assignedToId',
            'nv.HoTen as assignedToName',
            'bp.TenBoPhan as department',
            'pctb_latest.NgayCap as assignmentDate',
            'pctb_latest.NgayCapThietBi as actualAssignmentDate'
        ])
        ->get()
        ->map(function ($device) {
            return [
                'id' => $device->id,
                'deviceCode' => $device->deviceCode,
                'name' => $device->name,
                'assignedTo' => $device->assignedToId ? [
                    'id' => $device->assignedToId,
                    'name' => $device->assignedToName,
                    'department' => $device->department
                ] : null,
                'assignmentDate' => $device->assignmentDate ?: $device->actualAssignmentDate,
                'status' => 'in_use'
            ];
        });

    return response()->json($devices);
}

    /**
     * Lấy danh sách thiết bị chưa sử dụng (tối ưu)
     */
    public function getNotUsedDevices()
    {
        $devices = DB::table('tai_san_nhap_ct as tsn')
            ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
            ->where('tsn.MaTrangThai', 'TT002')
            ->select([
                'tsn.MaTaiSanNhap as id',
                'tsn.MaTaiSan as deviceCode',
                'ts.TenTaiSan as name',
                'ts.LoaiTaiSan as type',
                'tsn.NgayNhap as purchaseDate'
            ])
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'deviceCode' => $device->deviceCode,
                    'name' => $device->name,
                    'type' => $device->type,
                    'purchaseDate' => $device->purchaseDate,
                    'status' => 'not_used'
                ];
            });

        return response()->json($devices);
    }

 public function getDamagedDevices()
{
    $devices = DB::table('phieu_tra_thiet_bi as pttb')
        ->join('phieu_cap_thiet_bi as pctb', 'pttb.MaPhieuCapThietBi', '=', 'pctb.MaDuPhong')
        ->join('tai_san_nhap_ct as tsn', 'pctb.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
        ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
        ->leftJoin('nhan_vien as nv', 'pttb.MaNV', '=', 'nv.MaDuPhong')
        ->where('tsn.MaTrangThai', 'TT003') // hoặc TT004 cho thiết bị mất
        ->select([
            'tsn.MaTaiSanNhap as id',
            'tsn.MaTaiSan as deviceCode',
            'ts.TenTaiSan as name',
            'nv.MaDuPhong as reportedById',
            'nv.HoTen as reportedByName',
            'pttb.created_at as reportDate',
            'pttb.GhiChu as damageDescription'
        ])
        ->get()
        ->map(function ($device) {
            return [
                'id' => $device->id,
                'deviceCode' => $device->deviceCode,
                'name' => $device->name,
                'reportedBy' => $device->reportedById ? [
                    'id' => $device->reportedById,
                    'name' => $device->reportedByName
                ] : null,
                'reportDate' => $device->reportDate,
                'damageDescription' => $device->damageDescription,
                'status' => 'damaged'
            ];
        });

    return response()->json($devices);
}

/**
 * Lấy danh sách thiết bị báo mất (đã sửa để lấy người báo cáo)
 */
public function getLostDevices()
{
    $devices = DB::table('tai_san_nhap_ct as tsn')
        ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
        ->leftJoin('phieu_cap_thiet_bi as pctb', 'tsn.MaTaiSanNhap', '=', 'pctb.MaTaiSanNhap')
        ->leftJoin('nhan_vien as nv', 'pctb.MaNVNhanTB', '=', 'nv.MaDuPhong')
        ->where('tsn.MaTrangThai', 'TT004')
        ->select([
            'tsn.MaTaiSanNhap as id',
            'tsn.MaTaiSan as deviceCode',
            'ts.TenTaiSan as name',
            'nv.MaDuPhong as reportedById',
            'nv.HoTen as reportedByName',
            'tsn.updated_at as reportDate',
            'pctb.GhiChu as damageDescription'
        ])
        ->get()
        ->map(function ($device) {
            return [
                'id' => $device->id,
                'deviceCode' => $device->deviceCode,
                'name' => $device->name,
                'reportedBy' => $device->reportedById ? [
                    'id' => $device->reportedById,
                    'name' => $device->reportedByName
                ] : null,
                'reportDate' => $device->reportDate,
                'damageDescription' => $device->damageDescription,
                'status' => 'lost'
            ];
        });

    return response()->json($devices);
}

   

    /**
     * Lấy danh sách yêu cầu chờ duyệt (tối ưu)
     */
     public function getPendingApprovals()
{
    // Yêu cầu cấp phát chờ duyệt
    $assignRequests = DB::table('phieu_cap_thiet_bi as pctb')
        ->join('nhan_vien as ndx', 'pctb.MaNV', '=', 'ndx.MaDuPhong') // Sửa join condition
        ->leftJoin('nhan_vien as nn', 'pctb.MaNVNhanTB', '=', 'nn.MaDuPhong')
        ->where('pctb.TrangThaiDeXuat', 0)
        ->select([
            'pctb.MaDuPhong as id', // Hoặc có thể là 'pctb.MaPhieuCap as id'
            DB::raw("'assignment' as type"),
            'ndx.MaDuPhong as requestedById', // Sửa để consistent
            'ndx.HoTen as requestedByName',
            'pctb.created_at as createdAt',
            'pctb.NoiDungDeXuat as reason'
        ])
        ->get()
        ->map(function ($request) {
            // Sử dụng MaPhieuCap thay vì MaDuPhong để join
            $devices = DB::table('phieu_cap_thiet_bi as pctbct') // Thêm _ct nếu có bảng chi tiết
                ->join('tai_san_nhap_ct as tsn', 'pctbct.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
                ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong') // Sửa join condition
                ->where('pctbct.MaDuPhong', $request->id) // Sử dụng đúng key
                ->select([
                    'tsn.MaTaiSanNhap as id',
                    'ts.TenTaiSan as name',
                    'tsn.MaTaiSan as deviceCode'
                ])
                ->get();
                
            return [
                'id' => $request->id ?? null,
                'type' => $request->type ?? null,
                'requestedBy' => [
                    'id' => $request->requestedById ?? null,
                    'name' => $request->requestedByName ?? 'Unknown'
                ],
                'createdAt' => $request->createdAt ?? null,
                'devices' => $devices,
                'reason' => $request->reason ?? null
            ];
        });

    // Yêu cầu trả thiết bị chờ duyệt
    $returnRequests = DB::table('phieu_tra_thiet_bi as pttb')
        ->join('nhan_vien as ndx', 'pttb.MaNV', '=', 'ndx.MaDuPhong') // Sửa join condition
        ->leftJoin('nhan_vien as nt', 'pttb.MaNV', '=', 'nt.MaDuPhong')
        ->where('pttb.TrangThaiDeXuat', 0)
        ->select([
            'pttb.MaDuPhong as id', // Hoặc 'pttb.MaPhieuTra as id'
            DB::raw("'return' as type"),
            'ndx.MaDuPhong as requestedById',
            'ndx.HoTen as requestedByName',
            'pttb.created_at as createdAt',
            'pttb.NoiDungDeXuat as reason'
        ])
        ->get()
        ->map(function ($request) {
            $devices = DB::table('phieu_tra_thiet_bi as pttbct') // Có thể cần thêm _ct
                ->join('phieu_cap_thiet_bi as pctb', 'pttbct.MaPhieuCapThietBi', '=', 'pctb.MaDuPhong')
                ->leftJoin('tai_san_nhap_ct as tsn', 'pctb.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
                ->leftJoin('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
                ->where('pttbct.MaDuPhong', $request->id) // Sử dụng đúng key
                ->select([
                    'tsn.MaTaiSanNhap as id',
                    'ts.TenTaiSan as name',
                    'tsn.MaTaiSan as deviceCode'
                ])
                ->get();

            return [
                'id' => $request->id,
                'type' => $request->type,
                'requestedBy' => [
                    'id' => $request->requestedById,
                    'name' => $request->requestedByName
                ],
                'createdAt' => $request->createdAt,
                'devices' => $devices,
                'reason' => $request->reason
            ];
        });

    // Merge 2 collections và return
    $allRequests = $assignRequests->merge($returnRequests);
    
    return response()->json($allRequests);
}

// Phiên bản debug để kiểm tra dữ liệu
public function getPendingApprovalsDebug()
{
    // Kiểm tra dữ liệu cơ bản trước
    $assignCount = DB::table('phieu_cap_thiet_bi')->where('TrangThaiDeXuat', 0)->count();
    $returnCount = DB::table('phieu_tra_thiet_bi')->where('TrangThaiDeXuat', 0)->count();
    
    return response()->json([
        'assign_count' => $assignCount,
        'return_count' => $returnCount,
        'message' => 'Check if there are pending records'
    ]);
}

    public function approveRequest($id, Request $request)
{
    DB::beginTransaction();
    try {
        $validated = $request->validate([
            'type' => 'required|in:assignment,return'
        ]);
        $type = $validated['type'];
        
        if ($type === 'assignment') {
            // Update assignment request
            DB::table('phieu_cap_thiet_bi')
                ->where('MaDuPhong', $id)
                ->update([
                    'TrangThaiDeXuat' => 1,
                    'NgayCap' => now(),
                    'updated_at' => now()
                ]);
            
            // Update device status
            $devices = DB::table('phieu_cap_thiet_bi')
                ->where('MaDuPhong', $id)
                ->pluck('MaTaiSanNhap');
                
            DB::table('tai_san_nhap_ct')
                ->whereIn('MaTaiSanNhap', $devices)
                ->update(['MaTrangThai' => 'TT001']);
        } else {
            // Update return request
            DB::table('phieu_tra_thiet_bi')
                ->where('MaDuPhong', $id)
                ->update([
                    'TrangThaiDeXuat' => 1,
                    'NgayTra' => now(),
                    'updated_at' => now()
                ]);
                
            // Update device status
            $devices = DB::table('phieu_tra_thiet_bi')
                ->join('phieu_cap_thiet_bi', 'phieu_tra_thiet_bi.MaPhieuCapThietBi', '=', 'phieu_cap_thiet_bi.MaDuPhong')
                ->where('phieu_tra_thiet_bi.MaDuPhong', $id)
                ->pluck('phieu_cap_thiet_bi.MaTaiSanNhap');
                
            DB::table('tai_san_nhap_ct')
                ->whereIn('MaTaiSanNhap', $devices)
                ->update(['MaTrangThai' => 'TT002']);
        }
        
        DB::commit();
        return response()->json(['message' => 'Đã duyệt yêu cầu thành công'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Lỗi khi duyệt yêu cầu',
            'details' => $e->getMessage()
        ], 500);
    }
}

public function rejectRequest($id, Request $request)
{
    DB::beginTransaction();
    try {
        $validated = $request->validate([
            'type' => 'required|in:assignment,return',
            'reason' => 'required|string'
        ]);
        
        $type = $validated['type'];
        $reason = $validated['reason'];
        
        if ($type === 'assignment') {
            DB::table('phieu_cap_thiet_bi')
                ->where('MaDuPhong', $id)
                ->update([
                    'TrangThaiDeXuat' => 2,
                    'NoiDungDeXuat' => $reason,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('phieu_tra_thiet_bi')
                ->where('MaDuPhong', $id)
                ->update([
                    'TrangThaiDeXuat' => 2,
                    'NoiDungDeXuat' => $reason,
                    'updated_at' => now()
                ]);
        }
        
        DB::commit();
        return response()->json(['message' => 'Đã từ chối yêu cầu'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Lỗi khi từ chối yêu cầu',
            'details' => $e->getMessage()
        ], 500);
    }
}
}