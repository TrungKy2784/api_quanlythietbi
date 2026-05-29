<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use App\Models\PhanQuyen; 


use Illuminate\Foundation\Auth\User as Authenticatable;

class TaiKhoan extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'tai_khoan';

    protected $fillable = [
        'TenDangNhap',
        'MatKhau',
        'IDQuyen',
        'email',
        'MaNV',
        'TrangThai', // ✅ THÊM DÒNG NÀY
    ];

    protected $hidden = [
        'MatKhau',
    ];

    public $timestamps = false;

    public function getAuthPassword()
    {
        return $this->MatKhau;
    }

    public function phanquyen()
{
    return $this->belongsTo(PhanQuyen::class, 'IDQuyen');
}
}


