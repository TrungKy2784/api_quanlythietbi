<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PhanQuyenSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Database\Seeders\CoSoSeeder;
use Database\Seeders\BoPhanSeeder;
use Database\Seeders\LoaiTaiSanSeeder;
use Database\Seeders\TaiSanSeeder;
use Database\Seeders\DeXuatSeeder;
use Database\Seeders\TrangThaiSeeder;
use Database\Seeders\NhaCungCapSeeder;
use Database\Seeders\HoaDonNhapSeeder;
use Database\Seeders\HoaDonNhapChiTietSeeder;
use Database\Seeders\PhieuCapThietBiSeeder;
use Database\Seeders\PhieuTraThietBiSeeder;
use Database\Seeders\TaiSanNhapCTSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Gọi các seeders mà bạn muốn chạy.
        $this->call([
            PhanQuyenSeeder::class,
            NhanVienSeeder::class,
            TaiKhoanSeeder::class,
            CoSoSeeder::class,
            BoPhanSeeder::class,
            LoaiTaiSanSeeder::class,
            TaiSanSeeder::class,
            TrangThaiSeeder::class,
            DeXuatSeeder::class,
            NhaCungCapSeeder::class,
            HoaDonNhapSeeder::class,
            HoaDonNhapChiTietSeeder::class,
            TaiSanNhapCTSeeder::class,
            PhieuCapThietBiSeeder::class,
            PhieuTraThietBiSeeder::class,

        ]);
    }
}
