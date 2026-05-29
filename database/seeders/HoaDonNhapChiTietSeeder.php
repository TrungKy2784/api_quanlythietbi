<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HoaDonNhapChiTiet;
use Illuminate\Support\Facades\DB;

class HoaDonNhapChiTietSeeder extends Seeder
{
    public function run()
        {
            DB::table('hoa_don_nhap_chi_tiet')->insert([
                [
                    'MaDuPhong' => 'HDNCT001',
                    'MaHoaDonNhap' => 'HDN001',
                    'MaTaiSan' => 'TS001',
                    'SoLuong' => 10,
                    'DonGia' => 15000000,
                    'NhanHieu' => 'HP'
                ],
                [
                    'MaDuPhong' => 'HDNCT002',
                    'MaHoaDonNhap' => 'HDN002',
                    'MaTaiSan' => 'TS002',
                    'SoLuong' => 20,
                    'DonGia' => 5000000,
                    'NhanHieu' => 'Samsung'
                ]
            ]);
        }
}

