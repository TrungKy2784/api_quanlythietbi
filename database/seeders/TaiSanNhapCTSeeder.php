<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaiSanNhapCTSeeder extends Seeder
{
    public function run()
        {
            DB::table('tai_san_nhap_ct')->insert([
                [
                    'MaTaiSanNhap' => 'HDNCT001-01',
                    'MaHoaDonNhapCT' => 'HDNCT001',
                    'MaTaiSan' => 'TS001',
                    'NgayBatDauBaoHanh' => Carbon::now(),
                    'NgayHetHanBaoHanh' => Carbon::now()->addYears(2),
                    'MaTrangThai' => 'TT001',
                    'SoLuong' => 1, // hoặc số lượng bạn muốn
                    'HinhAnh' => null // hoặc tên file ảnh nếu có
                ],
                [
                    'MaTaiSanNhap' => 'HDNCT002-01',
                    'MaHoaDonNhapCT' => 'HDNCT002',
                    'MaTaiSan' => 'TS002',
                    'NgayBatDauBaoHanh' => Carbon::now(),
                    'NgayHetHanBaoHanh' => Carbon::now()->addYears(1),
                    'MaTrangThai' => 'TT002',
                    'SoLuong' => 1,
                    'HinhAnh' => null // hoặc tên file ảnh nếu có
                ]
            ]);
        }
}
