<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHoaDonNhapTable extends Migration
{
    public function up()
    {
        Schema::create('hoa_don_nhap', function (Blueprint $table) {
            $table->id(); // ID tự động tăng
            $table->string('MaDuPhong', 14)->unique(); // Mã DuPhong theo định dạng yyyyMMddHHmmss
            $table->string('MaNV', 14); // Mã nhân viên liên kết với NhanVien
            $table->string('IDNCC', 14); // Mã nhà cung cấp, phải là kiểu string(14)
            $table->dateTime('NgayNhap'); // Ngày giờ nhập
            $table->string('SoHoaDon', 10)->nullable(); // Số hóa đơn
            $table->string('GhiChu', 50)->nullable(); // Ghi chú (optional)
            $table->timestamps();

            // Cấu hình khóa ngoại với bảng nhà cung cấp
            $table->foreign('IDNCC')->references('MaDuPhong')->on('nha_cung_cap')->onDelete('cascade');
            // Cấu hình khóa ngoại với bảng nhân viên
            $table->foreign('MaNV')->references('MaDuPhong')->on('nhan_vien')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hoa_don_nhap');
    }
}
