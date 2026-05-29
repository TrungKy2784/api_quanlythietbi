<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phieu_cap_thiet_bi', function (Blueprint $table) {
            $table->id(); // ID int tự tăng

            $table->string('MaDuPhong', 14)->unique(); // Format yyyyMMddHHmmss
            $table->dateTime('NgayCap');

            $table->string('MaNV', 14); // MaDuPhong của NhanVien
            $table->string('MaTaiSanNhap', 14)->nullable(); // MaTaiSanNhap của TaiSanNhapCT
            $table->string('MaNVNhanTB', 14); // MaDuPhong của NhanVien
            $table->string('MaBoPhan', 14); // MaDuPhong của BoPhan
            $table->string('MaCoSo', 14); // MaDuPhong của CoSo
            $table->string('TrangThaiLucCap', 14); // MaDuPhong của TrangThai

            $table->dateTime('NgayHetHanCap')->nullable();
            $table->dateTime('NgayTaoDeXuat')->nullable();

            $table->string('NoiDungDeXuat', 300)->nullable(); // nvarchar(300)
            $table->integer('TrangThaiDeXuat')->nullable()->default(0); // 0: Chờ, 1: Đồng ý, 2: Từ chối
            $table->string('GhiChu', 300)->nullable(); // nvarchar(300)

            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phieu_cap_thiet_bi');
    }
};
