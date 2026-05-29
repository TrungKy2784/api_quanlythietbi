<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoSo extends Model
{
    use HasFactory;

    protected $table = 'co_so';

    protected $fillable = [
        'MaDuPhong',
        'TenCoSo',
        'DiaChi',
    ];
}

