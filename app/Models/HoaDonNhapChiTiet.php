<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoaDonNhapChiTiet extends Model
{
    use HasFactory;

    protected $table = 'hoa_don_nhap_chi_tiet';

    protected $fillable = [
        'MaDuPhong',
        'MaHoaDonNhap',
        'MaTaiSan',
        'SoLuong',
        'DonGia',
        'NhanHieu',
    ];
}

