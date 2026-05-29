<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrangThaiTable extends Migration
{
    public function up()
    {
        Schema::create('trang_thai', function (Blueprint $table) {
            $table->id(); // ID (int identity)
            $table->string('MaDuPhong', 14)->unique(); // Mã định danh theo định dạng yyyyMMddHHmmss
            $table->string('TenTrangThai', 50)->unique(); // Tên trạng thái tài sản, phải duy nhất
            $table->string('GhiChu', 50)->nullable(); // Ghi chú (optional)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trang_thai');
    }
}


