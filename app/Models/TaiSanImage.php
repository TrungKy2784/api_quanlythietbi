<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaiSanImage extends Model
{
    use HasFactory;

    protected $table = 'tai_san_images';
    
    protected $fillable = [
        'ma_tai_san_nhap',
        'image_path',
        'file_name',
        'file_size',
        'file_type',
        'is_main',
    ];

    /**
     * Get the asset that owns the image
     */
    public function taiSanNhapCT()
    {
        return $this->belongsTo(TaiSanNhapCT::class, 'ma_tai_san_nhap', 'MaTaiSanNhap');
    }
}