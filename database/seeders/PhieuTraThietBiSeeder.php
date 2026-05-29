<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PhieuTraThietBiSeeder extends Seeder
{
    public function run()
    {
        DB::table('phieu_tra_thiet_bi')->insert([
            [
                'MaDuPhong' => 'PTTB002',
                'NgayTra' => Carbon::now(),
                'MaNV' => 'NV002',
                'MaPhieuCapThietBi' => 'PCTB002',
                'TrangThaiLucTra' => 'TT003',
                'NgayTaoDeXuat' => Carbon::now(),
                'NoiDungDeXuat' => 'Đề xuất trả lại màng hình',
                'TrangThaiDeXuat' => 0,
                'GhiChu' => 'Laptop trả lại',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
