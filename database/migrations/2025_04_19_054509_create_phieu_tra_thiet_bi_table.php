<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhieuTraThietBiTable extends Migration
{
    public function up()
    {
        Schema::create('phieu_tra_thiet_bi', function (Blueprint $table) {
            $table->id(); // ID tự tăng

            $table->string('MaDuPhong', 14)->unique(); // Mã định dạng yyyyMMddHHmmss
            $table->dateTime('NgayTra'); // Ngày trả thiết bị

            $table->string('MaNV', 14); // MaDuPhong của bảng nhan_vien (không tạo khóa ngoại)
            $table->string('MaPhieuCapThietBi', 14); // MaDuPhong của bảng phieu_cap_thiet_bi (không tạo khóa ngoại)
            $table->string('TrangThaiLucTra', 14); // MaDuPhong của bảng trang_thai (không tạo khóa ngoại)

            $table->dateTime('NgayTaoDeXuat')->nullable(); // Ngày tạo đề xuất
            $table->string('NoiDungDeXuat', 300)->nullable(); // Nội dung đề xuất
            $table->integer('TrangThaiDeXuat')->nullable()->default(0); // 0: Đang chờ duyệt, 1: Đã duyệt (đồng ý)
            $table->string('GhiChu', 300)->nullable(); // Ghi chú

            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('phieu_tra_thiet_bi');
    }
}
