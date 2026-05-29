<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrangThai extends Model
{
    use HasFactory;

    protected $table = 'trang_thai'; // Tên bảng trong cơ sở dữ liệu

    // Các cột có thể được gán giá trị
    protected $fillable = [
        'MaDuPhong',
        'TenTrangThai',
        'GhiChu',
    ];
}

