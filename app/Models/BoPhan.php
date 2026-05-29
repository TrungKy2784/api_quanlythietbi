<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoPhan extends Model
{
    use HasFactory;

    protected $table = 'bo_phan';

    protected $fillable = [
        'MaDuPhong',
        'IDCoSo',
        'TenBoPhan',
        'GhiChu',
    ];

}

