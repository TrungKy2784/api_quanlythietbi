<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class TaiSanSeeder extends Seeder
{
    public function run()
        {
            DB::table('tai_san')->insert([
                [
                    'MaDuPhong' => 'TS001',
                    'MaSo' => 'LTS001',
                    'IDLoaiTaiSan' => 'LTS001',
                    'TenTaiSan' => 'Laptop HP',
                    'DonViTinh' => 'Cái',
                    'GhiChu' => 'Laptop cho nhân viên A',
                    
                ],
                [
                    'MaDuPhong' => 'TS002',
                    'MaSo' => 'LTS002',
                    'IDLoaiTaiSan' => 'LTS002',
                    'TenTaiSan' => 'Điện thoại Samsung',
                    'DonViTinh' => 'Cái',
                    'GhiChu' => 'Điện thoại cho nhân viên B'
                ]
            ]);
        }
}
