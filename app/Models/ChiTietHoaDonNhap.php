<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietHoaDonNhap extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_hoa_don_nhap';
    protected $primaryKey = 'ma_cthd';
    public $timestamps = false;

    protected $fillable = [
        'ma_cthd', 'ma_hd', 'ma_ts', 'so_luong', 'don_gia',
    ];

    public function hoaDonNhap()
    {
        return $this->belongsTo(HoaDonNhap::class, 'ma_hd', 'ma_hd');
    }

    public function taiSan()
    {
        return $this->belongsTo(TaiSan::class, 'ma_ts', 'ma_ts');
    }
}

