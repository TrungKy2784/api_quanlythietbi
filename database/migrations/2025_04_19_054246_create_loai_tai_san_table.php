<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoaiTaiSanTable extends Migration
{
    public function up()
    {
        Schema::create('loai_tai_san', function (Blueprint $table) {
            $table->id(); // ID (int identity)
            $table->string('MaDuPhong', 14)->unique(); // Mã định danh dự phòng
            $table->string('TenLoai', 50)->unique(); // Tên loại tài sản, duy nhất
            $table->string('GhiChu', 50)->nullable(); // Ghi chú (optional)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loai_tai_san');
    }
}


