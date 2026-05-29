<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaiSanNhapCTTable extends Migration
{
        public function up()
        {
            Schema::create('tai_san_nhap_ct', function (Blueprint $table) {
                $table->id(); // Tạo cột 'id' là khóa chính
                $table->string('MaTaiSanNhap', 14); // MaHoaDonNhapCT theo định dạng [MaHoaDonNhapCT - MaTaiSan]
                $table->string('MaHoaDonNhapCT', 14); // MaDuPhong của table HoaDonNhapChiTiet 
                $table->string('HinhAnh')->nullable();
                $table->string('MaTaiSan', 14); // MaTaiSan là MaDuPhong của TaiSan
                $table->integer('SoLuong')->default(1);
                $table->date('NgayBatDauBaoHanh'); // Ngày bắt đầu bảo hành
                $table->date('NgayHetHanBaoHanh'); // Ngày hết hạn bảo hành
                $table->string('MaTrangThai', 14); // MaTrangThai là MaDuPhong của TrangThai
                $table->timestamps(); // Timestamp cho các trường created_at và updated_at
            });
        }

    public function down()
    {
        Schema::dropIfExists('tai_san_nhap_ct');
    }
}

    