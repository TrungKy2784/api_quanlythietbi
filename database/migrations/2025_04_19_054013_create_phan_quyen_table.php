<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhanQuyenTable extends Migration
{
    public function up()
    {
        Schema::create('phan_quyen', function (Blueprint $table) {
            $table->id();
            $table->string('TenQuyen', 50);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('phan_quyen');
    }
}

