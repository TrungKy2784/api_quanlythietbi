<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoSoTable extends Migration
{
    public function up()
    {
        Schema::create('co_so', function (Blueprint $table) {
            $table->id(); // ID (int identity)
            $table->string('MaDuPhong', 14)->unique(); // Mã định danh dự phòng
            $table->string('TenCoSo', 50)->unique(); // Tên cơ sở, duy nhất
            $table->string('DiaChi', 150); // Địa chỉ
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('co_so');
    }
}


