<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhapKho extends Model
{
    use HasFactory;

    protected $table = 'nhap_kho';
    protected $primaryKey = 'ma_nk';
    public $timestamps = false;

    protected $fillable = [
        'ma_nk', 'ngay_nhap', 'ma_nv', 'ghi_chu',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nv', 'ma_nv');
    }
}

