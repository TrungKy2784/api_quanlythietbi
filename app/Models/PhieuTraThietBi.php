<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuTraThietBi extends Model
{
    use HasFactory;

    // Định nghĩa tên bảng (nếu tên bảng không theo chuẩn Laravel)
    protected $table = 'phieu_tra_thiet_bi';

    // Định nghĩa các trường có thể được gán giá trị (mass assignable)
    protected $fillable = [
        'MaDuPhong', 'NgayTra', 'MaNV', 'MaPhieuCapThietBi',
        'TrangThaiLucTra', 'TrangThaiDeXuat', 'NgayTaoDeXuat',
        'NoiDungDeXuat', 'GhiChu',
    ];

    // Nếu cần, định nghĩa quan hệ với các bảng khác
    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'MaNV', 'MaDuPhong');
    }

    public function phieuCapThietBi()
    {
        return $this->belongsTo(PhieuCapThietBi::class, 'MaPhieuCapThietBi', 'MaDuPhong');
    }

    public function trangThai()
    {
        return $this->belongsTo(TrangThai::class, 'TrangThaiLucTra', 'MaDuPhong');
    }
}
