<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapThietBi extends Model
{
    use HasFactory;

    protected $table = 'cap_thiet_bi';
    protected $primaryKey = 'ma_ctb';
    public $timestamps = false;

    protected $fillable = [
        'ma_ctb', 'ma_ts', 'ma_nv', 'ngay_cap', 'ghi_chu',
    ];

    public function taiSan()
    {
        return $this->belongsTo(TaiSan::class, 'ma_ts', 'ma_ts');
    }

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nv', 'ma_nv');
    }
}

