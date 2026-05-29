<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class LoaiTaiSanSeeder extends Seeder
{
    public function run()
        {
            DB::table('loai_tai_san')->insert([
                [
                    'MaDuPhong' => 'LTS001',
                    'TenLoai' => 'Laptop',
                    'GhiChu' => 'Máy tính xách tay'
                ],
                [
                    'MaDuPhong' => 'LTS002',
                    'TenLoai' => 'Điện thoại',
                    'GhiChu' => 'Thiết bị di động'
                ]
            ]);
        }
}
