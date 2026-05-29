<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PhieuCapThietBiSeeder extends Seeder
{
    public function run()
    {
        DB::table('phieu_cap_thiet_bi')->insert([
            [
                'MaDuPhong' => 'PCTB003',
                'NgayCap' => Carbon::now(),
                'MaNV' => 'NV003',
                'MaTaiSanNhap' => 'TSNC003',
                'MaNVNhanTB' => 'NV001',
                'MaBoPhan' => 'BP002',
                'TrangThaiLucCap' => 'TT001',
                'NgayHetHanCap' => Carbon::now()->addDays(5),
                'NgayTaoDeXuat' => Carbon::now()->subDays(2),
                'NoiDungDeXuat' => 'Cấp laptop cho nhân viên C',
                'TrangThaiDeXuat' => 0,
                'GhiChu' => 'Đang đợi duyệt phiếu cấp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'MaDuPhong' => 'PCTB004',
                'NgayCap' => Carbon::now(),
                'MaNV' => 'NV003',
                'MaTaiSanNhap' => 'TSNC004',
                'MaNVNhanTB' => 'NV002',
                'MaBoPhan' => 'BP001',
                'TrangThaiLucCap' => 'TT001',
                'NgayHetHanCap' => Carbon::now()->subDay(),
                'NgayTaoDeXuat' => Carbon::now()->subDays(11),
                'NoiDungDeXuat' => 'Cấp điện thoại cho nhân viên D',
                'TrangThaiDeXuat' => 1,
                'GhiChu' => 'Thiết bị đã hết hạn',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
