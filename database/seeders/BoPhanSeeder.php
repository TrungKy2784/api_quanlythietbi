<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class BoPhanSeeder extends Seeder
{
    public function run()
        {
            DB::table('bo_phan')->insert([
                [
                    'MaDuPhong' => 'BP001',
                    'IDCoSo' => 'CS001',
                    'TenBoPhan' => 'Bộ Phận A',
                    'GhiChu' => 'Ghi chú bộ phận A'
                ],
                [
                    'MaDuPhong' => 'BP002',
                    'IDCoSo' => 'CS002',
                    'TenBoPhan' => 'Bộ Phận B',
                    'GhiChu' => 'Ghi chú bộ phận B'
                ]
            ]);
        }
}
