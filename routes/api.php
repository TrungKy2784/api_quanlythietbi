<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaiSanController;
use App\Http\Controllers\LoaiTaiSanController;
use App\Http\Controllers\TrangThaiController;
use App\Http\Controllers\BoPhanController;
use App\Http\Controllers\NhanVienController;
use App\Http\Controllers\PhanQuyenController;
use App\Http\Controllers\PhieuCapThietBiController;
use App\Http\Controllers\PhieuTraThietBiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ThongKeController;
use App\Http\Controllers\DeXuatController;
use App\Http\Controllers\NhaCungCapController;
use App\Http\Controllers\HoaDonNhapController;
use App\Http\Controllers\HoaDonNhapChiTietController;
use App\Http\Controllers\TaiSanNhapCTController;
use App\Http\Controllers\BarcodeController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('profile', [AuthController::class, 'profile']);
    // Đặt các route đặc biệt TRƯỚC route resource
    Route::get('/taisans/export', [TaiSanController::class, 'export']);
    Route::get('/taisans/export-with-external-images', [TaiSanController::class, 'exportWithExternalImages']);
    Route::get('/taisans/export-with-images', [TaiSanController::class, 'exportWithImages']);
    Route::post('/taisans/import', [TaiSanController::class, 'import']);
    Route::post('/taisans/import-with-images', [TaiSanController::class, 'importWithImages']);
    Route::resource('taisans', TaiSanController::class);
    Route::resource('loaitaisans', LoaiTaiSanController::class);
    Route::resource('trangthais', TrangThaiController::class);
    Route::resource('bophans', BoPhanController::class);
    Route::resource('nhanviens', NhanVienController::class);
    Route::resource('phanquyens', PhanQuyenController::class);
    Route::resource('dexuats', DeXuatController::class);
    Route::resource('nhacungcaps', NhaCungCapController::class);
    Route::resource('hoadonnhaps', HoaDonNhapController::class);

    
    Route::get('hoadonnhapCTs', [HoaDonNhapChiTietController::class, 'index']);
    Route::post('hoadonnhapCTs', [HoaDonNhapChiTietController::class, 'store']);
    Route::delete('hoadonnhapCTs/{id}', [HoaDonNhapChiTietController::class, 'destroy']);
    Route::get('hoadonnhaps/{id}/with-details', [HoaDonNhapController::class, 'showWithDetails']);
    Route::apiResource('hoa-don-nhap', HoaDonNhapController::class);
    Route::post('hoa-don-nhap-with-details', [HoaDonNhapController::class, 'storeWithDetails']);
    Route::get('hoa-don-nhap/{maDuPhong}/details', [HoaDonNhapController::class, 'showWithDetails']);
    Route::apiResource('hoa-don-nhap-chi-tiet', HoaDonNhapChiTietController::class);
    Route::get('/hoadonnhap-chi-tiet/by-hoadon/{maHoaDon}', [HoaDonNhapChiTietController::class, 'getByMaHoaDonNhap']);


    Route::post('/taisans/upload-image-from-url', [TaiSanController::class, 'uploadImageFromUrl']);



    Route::get('taisannhapCTs', [TaiSanNhapCTController::class, 'index']);
    Route::post('taisannhapCTs', [TaiSanNhapCTController::class, 'store']);
    Route::delete('taisannhapCTs/{id}', [TaiSanNhapCTController::class, 'destroy']);
    Route::get('/taisannhapCTs/{maTaiSanNhap}', [TaiSanNhapCTController::class, 'show']);

    Route::get('taisannhapCTs/scan/{MaTaiSanNhap}', [TaiSanNhapCTController::class, 'showScan']);
    Route::get('taisannhapcts/with-details', [TaiSanNhapCTController::class, 'indexWithDetails']);
    Route::get('/taisannhapCTs/{maTaiSanNhap}/images', [TaiSanNhapCTController::class, 'getImages']);

    Route::post('taisannhapCTs/{id}/upload-image', [TaiSanNhapCTController::class, 'uploadImages']);

    Route::delete('/taisannhapCTs/{maTaiSanNhap}/images', [TaiSanNhapCTController::class, 'deleteImage']);
    Route::get('taisannhapCTs-with-name', [TaiSanNhapCTController::class, 'getTaiSanNhapCTWithName']);
    Route::get('/taisannhapcts/chuacap', [TaiSanNhapCTController::class, 'getTaiSanChuaCap']);
    Route::get('/taisannhapCTs/with-details', [TaiSanNhapCTController::class, 'indexWithDetails']);
    Route::put('/taisan/{maTaiSanNhap}/dang-su-dung', [TaiSanNhapCTController::class, 'capNhatTrangThaiDangSuDung']);
    Route::post('/cap-nhat-khi-tra/{maTaiSanNhap}', [TaiSanNhapCTController::class, 'capNhatKhiTraThietBi'])
        ->where('maTaiSanNhap', '[A-Za-z0-9]+');
   

    
    Route::get('phieucap', [PhieuCapThietBiController::class, 'index']);
    Route::get('/phieucap/all', [PhieuCapThietBiController::class, 'getAllPhieuCap']);
    Route::get('/phieucap/dang-cho-duyet', [PhieuCapThietBiController::class, 'getPhieuDangChoDuyet']);
    Route::post('/phieucap/duyet/{id}', [PhieuCapThietBiController::class, 'duyetDeXuat']);
    Route::get('phieutra/cho-duyet', [PhieuCapThietBiController::class, 'getPhieuChoDuyet']);
    Route::post('phieutra/duyet/{id}', [PhieuCapThietBiController::class, 'duyetPhieu']);
    Route::get('phieu-cap/cho-duyet', [PhieuCapThietBiController::class, 'getChoDuyetCap']);
    Route::get('phieu-cap/sap-het-han-thang', [PhieuCapThietBiController::class, 'getPhieuCapSapHetHan']);
    Route::get('phieu-cap/da-het-han', [PhieuCapThietBiController::class, 'getDaHetHan']);
    Route::post('phieucap/tao', [PhieuCapThietBiController::class, 'store']);
    Route::put('/phieucap/{id}', [PhieuCapThietBiController::class, 'update']); // Sửa phiếu cấp
    Route::delete('/phieucap/{id}', [PhieuCapThietBiController::class, 'destroy']); // Xóa phiếu cấp
    Route::get('/phieucap/chuatra', [PhieuCapThietBiController::class, 'getPhieuCapChuaTra']);
        // Route::post('/phieucap/{id}/duyet', [PhieuCapThietBiController::class, 'duyetDeXuat'])
        // //  ->middleware('can:approve,App\Models\PhieuCapThietBi')
        //  ->name('phieucap.duyet');
    // Thêm route mới trong api.php
    Route::get('/phieucap/search-by-manv-nhan-tb', [PhieuCapThietBiController::class, 'searchByMaNVNhanTB']);
    Route::get('/phieucap/search-by-tennv-nhan-tb', [PhieuCapThietBiController::class, 'searchByTenNVNhanTB']);


    Route::get('phieutra', [PhieuTraThietBiController::class, 'index']);
    Route::get('phieutra/cho-duyet', [PhieuTraThietBiController::class, 'getPhieuChoDuyet']);
    Route::post('/phieutra/duyet/{id}', [PhieuTraThietBiController::class, 'duyet']);
    Route::get('/phieutra/dang-cho-duyet', [PhieuTraThietBiController::class, 'getPhieuTraDangChoDuyet']);
    Route::post('/phieutra/tao', [PhieuTraThietBiController::class, 'store']);
    
    // Cập nhật phiếu trả (quản lý hoặc duyệt đề xuất)
    Route::put('/phieutra/sua/{id}', [PhieuTraThietBiController::class, 'update']);
    // Xóa phiếu trả
    Route::delete('/phieutra/{id}', [PhieuTraThietBiController::class, 'destroy']);
    
    // API route for traThietBi (return device)
    Route::put('/tra-thiet-bi/{id}', [PhieuTraThietBiController::class, 'traThietBi']);

    // API route for xacNhanDeXuat (confirm proposal)
    Route::put('/xac-nhan-de-xuat/{id}', [PhieuTraThietBiController::class, 'xacNhanDeXuat']);
    

    Route::get('thong-ke', [ThongKeController::class, 'thongKeDanhSach']);
    Route::get('/dashboard/approval-stats', [ThongKeController::class, 'getApprovalStats']);
    Route::get('/device-lifecycle/{maTaiSanNhap}', [ThongKeController::class, 'getDeviceLifecycle']);


    Route::get('tai-khoans', [AuthController::class, 'index']);
    Route::post('tai-khoans', [AuthController::class, 'registerAcc']);
    Route::put('/tai-khoans/{id}', [AuthController::class, 'update']);
    Route::delete('/tai-khoans/{id}', [AuthController::class, 'destroy']);

    Route::resource('cosos', App\Http\Controllers\CoSoController::class);
    
    Route::get('/dashboard/trends', [DashboardController::class, 'trends']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);  
    Route::get('/dashboard/devices/expiring-soon', [DashboardController::class, 'getExpiringSoonDevices']);

    // Device status routes
    Route::get('/devices/in-use', [DashboardController::class, 'getInUseDevices']);
    Route::get('/devices/not-used', [DashboardController::class, 'getNotUsedDevices']);
    Route::get('/devices/damaged', [DashboardController::class, 'getDamagedDevices']);
    Route::get('/devices/lost', [DashboardController::class, 'getLostDevices']);
    
    // Approval routes
    Route::put('/approvals/{id}/approve', [DashboardController::class, 'approveRequest']);
    Route::put('/approvals/{id}/reject', [DashboardController::class, 'rejectRequest']);
    
    // Device actions
    Route::put('/devices/{id}/repair', [DashboardController::class, 'markAsRepaired']);
    Route::put('/devices/{id}/found', [DashboardController::class, 'markAsFound']);

    
    Route::get('/statistics/devices', [ThongKeController::class, 'getDeviceStatistics']);
    Route::get('/statistics/approvals', [ThongKeController::class, 'getApprovalStatistics']);
    Route::post('/statistics/export-and-send-email', [ThongKeController::class, 'exportAndSendEmail']);
    Route::get('/statistics/export', [ThongKeController::class, 'exportDeviceStatistics']);

});

Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/register', [AuthController::class, 'register']);

Route::get('nhanvien-khong-co-tk', [NhanVienController::class, 'nhanVienChuaCoTaiKhoan']);
Route::get('dashboard/stats', [DashboardController::class, 'getDeviceStats']);
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/approvals/pending', [DashboardController::class, 'getPendingApprovals']);
