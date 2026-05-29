<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBoPhanTable extends Migration
{
    public function up()
    {
        Schema::create('bo_phan', function (Blueprint $table) {
            $table->id(); // ID (int identity)
            $table->string('MaDuPhong', 14)->unique(); // Mã định danh dự phòng
            $table->string('IDCoSo', 14); // Liên kết đến CoSo (không cần khóa ngoại)
            $table->string('TenBoPhan', 50); // Tên bộ phận
            $table->string('GhiChu', 50)->nullable(); // Ghi chú (optional)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bo_phan');
    }
}

