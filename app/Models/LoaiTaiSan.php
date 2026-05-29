<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoaiTaiSan extends Model
{
    use HasFactory;

    protected $table = 'loai_tai_san';

    protected $fillable = [
        'MaDuPhong', 'TenLoai', 'GhiChu'
    ];
}
