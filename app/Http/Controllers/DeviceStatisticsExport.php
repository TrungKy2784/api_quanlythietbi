<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Facades\Excel;


class DeviceStatisticsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function buildQuery()
    {
        $query = DB::table('tai_san_nhap_ct as tsn')
            ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
            ->leftJoin('phieu_cap_thiet_bi as pc', 'tsn.MaTaiSanNhap', '=', 'pc.MaTaiSanNhap')
            ->leftJoin('nhan_vien as nv', 'pc.MaNVNhanTB', '=', 'nv.MaDuPhong')
            ->leftJoin('bo_phan as bp', 'pc.MaBoPhan', '=', 'bp.MaDuPhong')
            ->select([
                'tsn.MaTaiSanNhap',
                'tsn.MaTaiSan',
                'ts.TenTaiSan',
                'tsn.MaTrangThai',
                DB::raw("CONVERT(VARCHAR, tsn.created_at, 103) as NgayNhap"),
                DB::raw("CONVERT(VARCHAR, tsn.NgayHetHanBaoHanh, 103) as NgayHetHanBaoHanh"),
                'nv.HoTen as NguoiSuDung',
                'bp.TenBoPhan'
            ])
            ->distinct();

        $filters = $this->request;

        if ($filters->status && $filters->status !== 'all') {
            $query->where('tsn.MaTrangThai', $filters->status);
        }

        if ($filters->department && $filters->department !== 'all') {
            $query->where(function ($q) use ($filters) {
                $q->where('pc.MaBoPhan', $filters->department)
                  ->orWhere(function ($q2) {
                      $q2->whereNull('pc.MaBoPhan')
                          ->where('tsn.MaTrangThai', 'TT002');
                  });
            });
        }

        if ($filters->fromDate && $filters->toDate) {
            try {
                $from = Carbon::parse($filters->fromDate)->startOfDay();
                $to = Carbon::parse($filters->toDate)->endOfDay();
                $query->whereBetween('tsn.created_at', [$from, $to]);
            } catch (\Exception $e) {
                // Bỏ lọc nếu lỗi ngày
            }
        }

        return $query;
    }

    public function collection()
    {
        return $this->buildQuery()->get();
    }

    public function headings(): array
    {
        return [
            'Mã tài sản nhập',
            'Mã tài sản',
            'Tên tài sản',
            'Trạng thái',
            'Ngày nhập',
            'Ngày hết hạn BH',
            'Người sử dụng',
            'Tên bộ phận'
        ];
    }

    public function map($row): array
    {
        $trangThai = match ($row->MaTrangThai) {
            'TT001' => 'Đang sử dụng',
            'TT002' => 'Chưa sử dụng',
            'TT003' => 'Hư hỏng',
            'TT004' => 'Mất',
            'TT005' => 'Sắp hết hạn',
            default => 'Không xác định',
        };

        return [
            $row->MaTaiSanNhap,
            $row->MaTaiSan,
            $row->TenTaiSan,
            $trangThai,
            $row->NgayNhap,
            $row->NgayHetHanBaoHanh,
            $row->NguoiSuDung,
            $row->TenBoPhan
        ];
    }
}
