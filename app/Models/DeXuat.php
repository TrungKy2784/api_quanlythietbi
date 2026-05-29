<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeXuat extends Model
{
    use HasFactory;

    protected $table = 'de_xuat';

    protected $fillable = [
        'MaDuPhong',  // Sửa lại tên trường cho đúng với bảng
        'TenDeXuat',
        'GhiChu',
    ];
}
