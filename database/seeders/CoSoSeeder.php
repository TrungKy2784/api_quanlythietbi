<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class CoSoSeeder extends Seeder
{
    public function run()
        {
            DB::table('co_so')->insert([
                [
                    'MaDuPhong' => 'CS001',
                    'TenCoSo' => 'Cơ Sở A',
                    'DiaChi' => 'Địa chỉ cơ sở A'
                ],
                [
                    'MaDuPhong' => 'CS002',
                    'TenCoSo' => 'Cơ Sở B',
                    'DiaChi' => 'Địa chỉ cơ sở B'
                ]
            ]);
        }
}


