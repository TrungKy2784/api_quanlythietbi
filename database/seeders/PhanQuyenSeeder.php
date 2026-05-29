<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhanQuyen;
use Illuminate\Support\Facades\DB;

class PhanQuyenSeeder extends Seeder
{
     public function run()
        {
            DB::table('phan_quyen')->insert([
                ['TenQuyen' => 'Admin'],
                ['TenQuyen' => 'Nhân viên quản trị'],
                ['TenQuyen' => 'Nhân viên nhập kho'],
                ['TenQuyen' => 'Nhân viên quản lý thiết bị'],
            ]);
        }
}

