<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNhaCungCapTable extends Migration
{
    public function up()
    {
        Schema::create('nha_cung_cap', function (Blueprint $table) {
            $table->id(); // ID (int identity)
            $table->string('MaDuPhong', 14)->unique(); // Mã cung cấp, theo định dạng yyyyMMddHHmmss
            $table->string('TenNCC', 50)->unique(); // Tên nhà cung cấp
            $table->string('DiaChiNCC', 150)->nullable(); // Địa chỉ nhà cung cấp
            $table->string('GhiChu', 50)->nullable(); // Ghi chú (optional)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nha_cung_cap');
    }
}

