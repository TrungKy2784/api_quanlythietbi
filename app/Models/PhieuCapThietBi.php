<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuCapThietBi extends Model
{
    use HasFactory;

    protected $table = 'phieu_cap_thiet_bi';
    protected $primaryKey = 'ID';
    public $timestamps = true;

    protected $fillable = [
        'MaDuPhong',
        'NgayCap',
        'MaNV',
        'MaTaiSanNhap',
        'MaNVNhanTB',
        'MaBoPhan',
        'MaCoSo',
        'TrangThaiLucCap',
        'NgayHetHanCap',
        'NgayTaoDeXuat',
        'NoiDungDeXuat',
        'TrangThaiDeXuat',
        'GhiChu'
    ];

    // Quan hệ với bảng tài sản nhập chi tiết
    public function taiSanNhap()
    {
        return $this->belongsTo(TaiSanNhapCT::class, 'MaTaiSanNhap', 'MaTaiSanNhap');
    }
}
