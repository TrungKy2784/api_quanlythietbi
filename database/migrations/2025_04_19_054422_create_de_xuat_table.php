<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeXuatTable extends Migration
{
    public function up()
    {
        Schema::create('de_xuat', function (Blueprint $table) {
            $table->id(); // ID (int identity)
            $table->string('MaDuPhong', 14)->unique(); // Mã đề xuất, theo định dạng yyyyMMddHHmmss
            $table->string('TenDeXuat', 50)->unique(); // Tên đề xuất
            $table->string('GhiChu', 50)->nullable(); // Ghi chú (optional)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('de_xuat');
    }
}


