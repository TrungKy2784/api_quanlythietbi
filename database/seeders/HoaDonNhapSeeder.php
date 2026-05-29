<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HoaDonNhapSeeder extends Seeder
{
     public function run()
        {
           DB::table('hoa_don_nhap')->insert([
               [
                   'MaDuPhong' => 'HDN001',
                   'MaNV' => 'NV001',
                   'IDNCC' => 'NCC001',
                   'NgayNhap' => now(),
                   'SoHoaDon' => 'HD001',
                   'GhiChu' => 'Hóa đơn mua laptop'
               ],
               [
                   'MaDuPhong' => 'HDN002',
                   'MaNV' => 'NV002',
                   'IDNCC' => 'NCC002',
                   'NgayNhap' => now(),
                   'SoHoaDon' => 'HD002',
                   'GhiChu' => 'Hóa đơn mua phần mềm'
               ]
           ]);
        }
}

