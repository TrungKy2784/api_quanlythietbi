<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrangThaiSeeder extends Seeder
{
     public function run()
        {
            DB::table('trang_thai')->insert([
                ['MaDuPhong' => 'TT001', 'TenTrangThai' => 'Đang sử dụng', 'GhiChu' => 'Tài sản đang được sử dụng'],
                ['MaDuPhong' => 'TT002', 'TenTrangThai' => 'Chưa sử dụng', 'GhiChu' => 'Tài sản chưa sử dụng'],
                ['MaDuPhong' => 'TT003', 'TenTrangThai' => 'Hư hỏng', 'GhiChu' => 'Tài sản bị hư hỏng'],
                ['MaDuPhong' => 'TT004', 'TenTrangThai' => 'Bị mất', 'GhiChu' => 'Tài sản bị mất'],
                ['MaDuPhong' => 'TT005', 'TenTrangThai' => 'Sắp hết hạn', 'GhiChu' => 'Tài sản sắp hết hạn bảo hành'],
                ['MaDuPhong' => 'TT006', 'TenTrangThai' => 'Chờ duyệt cấp', 'GhiChu' => 'Tài sản đang chờ duyệt cấp'],
                ['MaDuPhong' => 'TT007', 'TenTrangThai' => 'Chờ duyệt trả', 'GhiChu' => 'Tài sản đang chờ duyệt trả'],
                ['MaDuPhong' => 'TT008', 'TenTrangThai' => 'Bị từ chối', 'GhiChu' => 'Tài sản bị từ chối'],
            ]);
        }
}
