<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaiKhoanTable extends Migration
{
    public function up()
    {
        Schema::create('tai_khoan', function (Blueprint $table) {
            $table->id(); // ID (identity)

            $table->string('MaNV', 14); // MaDuPhong của nhân viên, không cần foreign key
            $table->string('TenDangNhap', 15)->unique(); // Chỉ chứa chữ và số
            $table->string('MatKhau', 255); // Tăng chiều dài cột MatKhau lên 255 để chứa mật khẩu mã hóa
            $table->integer('TrangThai')->default(0); // 0: đang sử dụng; 1: bị khóa
            $table->foreignId('IDQuyen')->constrained('phan_quyen')->onDelete('cascade');
            $table->string('email', 255); 

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tai_khoan');
    }
}
