<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class TaiKhoanSeeder extends Seeder
{
    public function run()
        {
            DB::table('tai_khoan')->insert([
                [
                    'MaNV' => 'NV001',
                    'TenDangNhap' => 'admin1',
                    'MatKhau' => Hash::make('password123'), // Mật khẩu mã hóa
                    'TrangThai' => 0,
                    'IDQuyen' => 1 // Admin
                ],
                [
                    'MaNV' => 'NV002',
                    'TenDangNhap' => 'nhanvien2',
                    'MatKhau' => Hash::make('password123'), // Mật khẩu mã hóa
                    'TrangThai' => 0,
                    'IDQuyen' => 2 // Nhân viên quản trị
                ]
            ]);
        }
}
