<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaiSanTable extends Migration
{
    public function up()
    {
        Schema::create('tai_san', function (Blueprint $table) {
            $table->id(); // ID (int identity)
            $table->string('MaDuPhong', 14)->unique(); // Mã định danh tài sản
            $table->string('MaSo', 14)->nullable(); // Mã số tùy biến
            $table->string('IDLoaiTaiSan', 14); // Mã loại tài sản, không khóa ngoại
            $table->string('TenTaiSan', 150); // Tên tài sản
            $table->string('DonViTinh', 20); // Đơn vị tính
            $table->string('GhiChu', 200)->nullable(); // Ghi chú (optional)
            $table->string('HinhAnh')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tai_san');
    }
}


