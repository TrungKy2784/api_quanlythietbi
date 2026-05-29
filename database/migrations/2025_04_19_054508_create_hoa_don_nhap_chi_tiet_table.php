<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHoaDonNhapChiTietTable extends Migration
{
    public function up()
    {
        Schema::create('hoa_don_nhap_chi_tiet', function (Blueprint $table) {
            $table->id(); // Tạo cột 'id' là khóa chính
            $table->string('MaDuPhong', 14)->unique(); // Mã DuPhong theo định dạng bắt đầu từ HDNCT001 và +1 vào mã cuối cùng được tạo
            $table->string('MaHoaDonNhap', 14); // Mã hóa đơn nhập là MaDuPhong của table HoaDonNhap
            $table->string('MaTaiSan', 14); // Mã tài sản là MaDuPhong của table TaiSan
            $table->decimal('SoLuong', 15, 2); // Sửa kiểu dữ liệu thành decimal
            $table->integer('DonGia'); // Giá trị tiền tệ của tài sản (VNĐ)
            $table->string('NhanHieu', 150)->nullable(); // Nhan hiệu (optional)
            $table->timestamps(); // Timestamp cho các trường created_at và updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('hoa_don_nhap_chi_tiet');
    }
}


