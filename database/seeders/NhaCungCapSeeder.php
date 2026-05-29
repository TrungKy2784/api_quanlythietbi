<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NhaCungCap;
use Illuminate\Support\Facades\DB;

class NhaCungCapSeeder extends Seeder
{
    public function run()
        {
            DB::table('nha_cung_cap')->insert([
                ['MaDuPhong' => 'NCC001', 'TenNCC' => 'Công ty A', 'DiaChiNCC' => 'Hà Nội', 'GhiChu' => 'Nhà cung cấp thiết bị văn phòng'],
                ['MaDuPhong' => 'NCC002', 'TenNCC' => 'Công ty B', 'DiaChiNCC' => 'TP.HCM', 'GhiChu' => 'Nhà cung cấp phần mềm'],
            ]);
        }
}

