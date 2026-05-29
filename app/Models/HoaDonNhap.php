<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoaDonNhap extends Model
{
    use HasFactory;

    protected $table = 'hoa_don_nhap';

    protected $fillable = [
        'MaDuPhong',
        'MaNV',
        'IDNCC',
        'NgayNhap',
        'SoHoaDon',
        'GhiChu',
        'created_at',
        'updated_at'
    ];
}

