<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaiSan extends Model
{
    protected $table = 'tai_san'; // Chỉ định bảng tương ứng
        protected $primaryKey = 'id'; // Cấu hình khóa chính nếu cần (mặc định là 'id')
        public $timestamps = true; // Đảm bảo rằng timestamps được sử dụng

        // Cấu hình các thuộc tính có thể điền (mass assignable)
        protected $fillable = [
            'MaDuPhong', 'MaSo', 'IDLoaiTaiSan', 'TenTaiSan',
            'DonViTinh', 'GhiChu', 'TrangThai', 'HinhAnh'
        ];

        /**
         * Quan hệ với bảng LoaiTaiSan
         */
        public function loaiTaiSan()
        {
            return $this->belongsTo(LoaiTaiSan::class, 'IDLoaiTaiSan', 'MaDuPhong');
        }
        
}

