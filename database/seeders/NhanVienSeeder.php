<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class NhanVienSeeder extends Seeder
{
    public function run()
        {
            DB::table('nhan_vien')->insert([
                [
                    'MaDuPhong' => 'NV001',
                    'HoTen' => 'Nguyễn Văn A',
                    'SoDienThoai' => '0123456789',
                    'NgaySinh' => Carbon::parse('1990-01-01'),
                    'DiaChi' => 'Hà Nội',
                    'TrangThai' => 1
                ],
                [
                    'MaDuPhong' => 'NV002',
                    'HoTen' => 'Trần Thị B',
                    'SoDienThoai' => '0987654321',
                    'NgaySinh' => Carbon::parse('1985-05-15'),
                    'DiaChi' => 'Hồ Chí Minh',
                    'TrangThai' => 1
                ]
            ]);
        }
}
