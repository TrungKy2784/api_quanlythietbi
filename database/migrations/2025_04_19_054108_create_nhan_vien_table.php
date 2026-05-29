<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_create_nhan_vien_table.php
class CreateNhanVienTable extends Migration
{
    public function up()
    {
        Schema::create('nhan_vien', function (Blueprint $table) {
            $table->id(); // auto-increment khóa chính
            $table->string('MaDuPhong', 14)->unique();
            $table->string('HoTen', 50);
            $table->string('SoDienThoai', 20)->nullable();
            $table->date('NgaySinh')->nullable();
            $table->string('DiaChi', 100)->nullable();
            $table->integer('TrangThai')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nhan_vien');
    }
}

