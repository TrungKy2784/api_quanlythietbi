<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class DeXuatSeeder extends Seeder
{
    public function run()
        {
            DB::table('de_xuat')->insert([
                ['MaDuPhong' => 'DX001', 'TenDeXuat' => 'Đề xuất mua thiết bị', 'GhiChu' => 'Đề xuất mua thêm thiết bị văn phòng'],
                ['MaDuPhong' => 'DX002', 'TenDeXuat' => 'Đề xuất nâng cấp thiết bị', 'GhiChu' => 'Đề xuất nâng cấp hệ thống máy tính'],
            ]);
        }
}
