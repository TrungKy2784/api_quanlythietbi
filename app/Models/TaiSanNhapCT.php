<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaiSanNhapCT extends Model
{
    use HasFactory;

    protected $table = 'tai_san_nhap_ct';  // Tên bảng trong cơ sở dữ liệu
    protected $primaryKey = 'id';  // Khóa chính của bảng
    public $timestamps = true;  // Laravel sẽ tự động tạo và sử dụng cột created_at và updated_at

    // Các trường có thể mass assign
    protected $fillable = [
        'MaTaiSanNhap',      // 👉 Thêm dòng này
        'MaDuPhong',
        'MaHoaDonNhapCT',
        'MaTaiSan',
        'NgayBatDauBaoHanh',
        'NgayHetHanBaoHanh',
        'MaTrangThai',
        'SoLuong',
        'HinhAnh',
    ];

    // Nếu muốn một phương thức để dễ dàng cập nhật trạng thái tài sản
public function capNhatTrangThai($maTrangThai)
{
    // Kiểm tra xem mã trạng thái có hợp lệ không
    if (!in_array($maTrangThai, ['TT001', 'TT002', 'TT003', 'TT004'])) {
        \Log::error("Mã trạng thái không hợp lệ: $maTrangThai");
        return false;
    }

    $this->MaTrangThai = $maTrangThai;  // Cập nhật trạng thái
    return $this->save();  // Lưu thay đổi
}



    // Tìm tài sản nhập chi tiết theo mã
    public static function findByMaTaiSanNhap($maTaiSanNhap)
    {
        return self::where('MaTaiSanNhap', $maTaiSanNhap)->first();
    }
    
        public function taiSan()
    {
        return $this->belongsTo(TaiSan::class, 'MaTaiSan', 'MaDuPhong');
    }

    public function phieuCap()
    {
        return $this->belongsTo(PhieuCapThietBi::class, 'MaPhieuCap', 'id');
    }
}
