<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ThongKeController extends Controller
{
    public function getDeviceStatistics(Request $request)
    {
        $query = $this->buildDeviceQuery($request);

        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        // Clone query để lấy thống kê tổng quan
        $summaryQuery = clone $query;
        $summaryQuery->orders = null;

        $devices = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $devices->items(),
            'total' => $devices->total(),
            'current_page' => $devices->currentPage(),
            'per_page' => $devices->perPage(),
            'summary' => [
                'total' => $summaryQuery->count(),
                'in_use' => (clone $summaryQuery)->where('tsn.MaTrangThai', 'TT001')->count(),
                'not_used' => (clone $summaryQuery)->where('tsn.MaTrangThai', 'TT002')->count(),
                'damaged' => (clone $summaryQuery)->where('tsn.MaTrangThai', 'TT003')->count(),
                'lost' => (clone $summaryQuery)->where('tsn.MaTrangThai', 'TT004')->count(),
                'expiring' => (clone $summaryQuery)->where('tsn.MaTrangThai', 'TT005')->count()
            ]
        ]);
    }

public function exportDeviceStatistics(Request $request)
{
    return Excel::download(new class($request) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithColumnWidths, WithEvents {
         protected $request;
            protected $data;
            protected $statistics = [];
            protected $reportDate;
            protected $filterInfo;

            public function __construct($request)
            {
                $this->request = $request;
                $this->reportDate = now();
                $this->data = $this->buildQuery()->get();
                $this->calculateStatistics();
                $this->buildFilterInfo();
            }

            protected function calculateStatistics()
            {
                $this->statistics = [
                    'total' => $this->data->count(),
                    'in_use' => $this->data->where('MaTrangThai', 'TT001')->count(),
                    'available' => $this->data->where('MaTrangThai', 'TT002')->count(),
                    'broken' => $this->data->where('MaTrangThai', 'TT003')->count(),
                    'lost' => $this->data->where('MaTrangThai', 'TT004')->count(),
                    'expiring' => $this->data->where('MaTrangThai', 'TT005')->count(),
                    'departments' => $this->data->whereNotNull('TenBoPhan')->pluck('TenBoPhan')->unique()->count(),
                ];
            }

        protected function buildFilterInfo()
        {
           $filters = [];
    
            if ($this->request->status && $this->request->status !== 'all') {
                $statusName = match($this->request->status) {
                    'TT001' => 'Đang sử dụng',
                    'TT002' => 'Chưa sử dụng',
                    'TT003' => 'Hư hỏng',
                    'TT004' => 'Mất',
                    'TT005' => 'Sắp hết hạn',
                    default => 'Không xác định'
                };
                $filters[] = "Trạng thái: {$statusName}";
            }

            if ($this->request->fromDate && $this->request->toDate) {
                $filters[] = "Từ ngày: {$this->request->fromDate} đến {$this->request->toDate}";
            }

            if ($this->request->nhan_vien) {
                $filters[] = "Nhân viên: {$this->request->nhan_vien}";
            }

            if ($this->request->bo_phan) {
                $filters[] = "Bộ phận: {$this->request->bo_phan}";
            }

            if ($this->request->ten_tai_san) {
                $filters[] = "Tên tài sản: {$this->request->ten_tai_san}";
            }

            if ($this->request->ma_tai_san_nhap) {
                $filters[] = "Mã tài sản nhập: {$this->request->ma_tai_san_nhap}";
            }

            if ($this->request->ma_tai_san) {
                $filters[] = "Mã tài sản: {$this->request->ma_tai_san}";
            }

            $this->filterInfo = !empty($filters) ? implode(' | ', $filters) : 'Tất cả thiết bị';
        }

        protected function buildQuery()
        {
            return $this->buildDeviceQueryForExport($this->request);
        }

        private function buildDeviceQueryForExport(Request $request)
        {
             $query = DB::table('tai_san_nhap_ct as tsn')
        ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
        ->leftJoin(DB::raw('(
            SELECT 
                pctb1.MaTaiSanNhap,
                pctb1.MaNVNhanTB,
                pctb1.MaBoPhan,
                pctb1.created_at as NgayCapThietBi
            FROM phieu_cap_thiet_bi pctb1
            WHERE pctb1.id = (
                SELECT MAX(pctb2.id) 
                FROM phieu_cap_thiet_bi pctb2 
                WHERE pctb2.MaTaiSanNhap = pctb1.MaTaiSanNhap
            )
        ) as pctb_latest'), 'pctb_latest.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
        ->leftJoin('nhan_vien as nv', 'pctb_latest.MaNVNhanTB', '=', 'nv.MaDuPhong')
        ->leftJoin('bo_phan as bp', 'bp.MaDuPhong', '=', 'pctb_latest.MaBoPhan')
        ->select([
            'tsn.MaTaiSanNhap',
            'tsn.MaTaiSan',
            'ts.TenTaiSan',
            'tsn.MaTrangThai',
            'tsn.SoLuong',
            'tsn.created_at',
            DB::raw("FORMAT(tsn.created_at, 'dd/MM/yyyy') as NgayNhap"),
            DB::raw("FORMAT(tsn.NgayHetHanBaoHanh, 'dd/MM/yyyy') as NgayHetHanBaoHanh"),
            DB::raw("CASE WHEN tsn.MaTrangThai = 'TT001' THEN nv.HoTen ELSE NULL END as NguoiSuDung"),
            DB::raw("CASE WHEN tsn.MaTrangThai = 'TT001' THEN bp.TenBoPhan ELSE NULL END as TenBoPhan"),
            DB::raw("FORMAT(pctb_latest.NgayCapThietBi, 'dd/MM/yyyy') as NgayCapThietBiCuoi")
        ]);

    // Filter theo trạng thái (ví dụ: TT002 = hỏng, TT003 = mất)
    if ($request->filled('status') && $request->status !== 'all') {
    if ($request->status === 'TT005') {
        $query->whereNotNull('tsn.NgayHetHanBaoHanh')
              ->whereDate('tsn.NgayHetHanBaoHanh', '>', now())
              ->whereDate('tsn.NgayHetHanBaoHanh', '<=', now()->addDays(30));
    } else {
        $query->where('tsn.MaTrangThai', $request->status);
    }
}

    // Filter theo ngày nhập
    if ($request->filled('fromDate') && $request->filled('toDate')) {
        try {
            $fromDate = \Carbon\Carbon::parse($request->fromDate)->startOfDay();
            $toDate = \Carbon\Carbon::parse($request->toDate)->endOfDay();
            $query->whereBetween('tsn.created_at', [$fromDate, $toDate]);
        } catch (\Exception $e) {
            \Log::error('Lỗi parse date trong filter: ' . $e->getMessage());
        }
    }

    // Filter theo nhân viên sử dụng
    if ($request->filled('nhan_vien')) {
        $query->where('nv.HoTen', 'like', '%' . $request->nhan_vien . '%');
    }

    // Filter theo bộ phận
    if ($request->filled('bo_phan')) {
        $query->where('bp.TenBoPhan', 'like', '%' . $request->bo_phan . '%');
    }

    // Filter theo tên tài sản
    if ($request->filled('ten_tai_san')) {
        $query->where('ts.TenTaiSan', 'like', '%' . $request->ten_tai_san . '%');
    }

    // Filter theo mã tài sản nhập
    if ($request->filled('ma_tai_san_nhap')) {
        $query->where('tsn.MaTaiSanNhap', $request->ma_tai_san_nhap);
    }

    // Filter theo mã tài sản
    if ($request->filled('ma_tai_san')) {
        $query->where('tsn.MaTaiSan', $request->ma_tai_san);
    }

    return $query->orderBy('tsn.created_at', 'desc');
}

        public function collection()
        {
            $reportHeader = collect([
                // Header chính
                $this->createRow(['CÔNG TY TNHH ABC', '', '', '', '', '', '', '']),
                $this->createRow(['Địa chỉ: 123 Đường XYZ, Quận 1, TP.HCM', '', '', '', '', '', '', '']),
                $this->createRow(['Điện thoại: (028) 1234 5678 | Email: info@company.com', '', '', '', '', '', '', '']),
                $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                
                // Tiêu đề báo cáo
                $this->createRow(['BÁO CÁO THỐNG KÊ QUẢN LÝ TÀI SẢN THIẾT BỊ', '', '', '', '', '', '', '']),
                $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                
                // Thông tin báo cáo
                $this->createRow(['Ngày lập báo cáo:', $this->reportDate->format('d/m/Y H:i:s'), '', '', '', '', '', '']),
                $this->createRow(['Người lập:', 'Quản trị viên hệ thống', '', '', '', '', '', '']),
                $this->createRow(['Phạm vi báo cáo:', $this->filterInfo, '', '', '', '', '', '']),
                $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                
                // Tóm tắt thống kê
                $this->createRow(['I. TÓM TẮT THỐNG KÊ', '', '', '', '', '', '', '']),
                $this->createRow(['Tổng số thiết bị:', $this->statistics['total'], 'thiết bị', '', '', '', '', '']),
                $this->createRow(['Thiết bị đang sử dụng:', $this->statistics['in_use'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['in_use']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                $this->createRow(['Thiết bị chưa sử dụng:', $this->statistics['available'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['available']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                $this->createRow(['Thiết bị hư hỏng:', $this->statistics['broken'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['broken']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                $this->createRow(['Thiết bị bị mất:', $this->statistics['lost'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['lost']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                $this->createRow(['Thiết bị sắp hết hạn BH:', $this->statistics['expiring'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['expiring']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                $this->createRow(['Số bộ phận đang sử dụng:', $this->statistics['departments'], 'bộ phận', '', '', '', '', '']),
                $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                
                // Phần chi tiết
                $this->createRow(['II. CHI TIẾT THIẾT BỊ', '', '', '', '', '', '', '']),
                $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                $this->createRow(['Mã tài sản nhập', 'Mã tài sản', 'Tên tài sản', 'Trạng thái', 'Ngày nhập', 'Ngày hết hạn BH', 'Người sử dụng', 'Bộ phận sử dụng']), 
            ]);

            return $reportHeader->concat($this->data);
        }

        private function createRow($data)
        {
            return (object)[
                'MaTaiSanNhap' => $data[0] ?? '',
                'MaTaiSan' => $data[1] ?? '',
                'TenTaiSan' => $data[2] ?? '',
                'MaTrangThai' => $data[3] ?? '',
                'NgayNhap' => $data[4] ?? '',
                'NgayHetHanBaoHanh' => $data[5] ?? '',
                'NguoiSuDung' => $data[6] ?? '',
                'TenBoPhan' => $data[7] ?? ''
            ];
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
                'Bộ phận sử dụng'
            ];
        }

        public function map($row): array
        {
            // Kiểm tra nếu là dòng dữ liệu thực tế (có MaTrangThai)
            if (isset($row->MaTrangThai) && in_array($row->MaTrangThai, ['TT001', 'TT002', 'TT003', 'TT004', 'TT005'])) {
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
                    $row->NguoiSuDung ?? '-',
                    $row->TenBoPhan ?? '-'
                ];
            }

            // Trả về dữ liệu header
            return [
                $row->MaTaiSanNhap,
                $row->MaTaiSan,
                $row->TenTaiSan,
                $row->MaTrangThai,
                $row->NgayNhap,
                $row->NgayHetHanBaoHanh,
                $row->NguoiSuDung,
                $row->TenBoPhan
            ];
        }

        public function styles(Worksheet $sheet)
        {
            return [
                // Style cho thông tin công ty
                1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1565C0']]],
                2 => ['font' => ['size' => 11, 'color' => ['rgb' => '666666']]],
                3 => ['font' => ['size' => 11, 'color' => ['rgb' => '666666']]],
                
                // Style cho tiêu đề báo cáo
                5 => [
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ],
                
                // Style cho phần thông tin báo cáo
                7 => ['font' => ['bold' => true, 'size' => 11]],
                8 => ['font' => ['bold' => true, 'size' => 11]],
                9 => ['font' => ['bold' => true, 'size' => 11]],
                
                // Style cho tóm tắt thống kê
                11 => [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1565C0']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
                ],
                
                // Style cho phần chi tiết
                19 => [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1565C0']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
                ],
            ];
        }

        public function title(): string
        {
            return 'Báo cáo thống kê tài sản';
        }

        public function columnWidths(): array
        {
            return [
                'A' => 20,
                'B' => 15,
                'C' => 35,
                'D' => 18,
                'E' => 15,
                'F' => 18,
                'G' => 25,
                'H' => 25,
            ];
        }

        public function registerEvents(): array
        {
            return [
                AfterSheet::class => function(AfterSheet $event) {
                    $sheet = $event->sheet->getDelegate();
                    $lastRow = $sheet->getHighestRow();
                    
                    // Merge cells cho các tiêu đề
                    $sheet->mergeCells('A1:H1'); // Tên công ty
                    $sheet->mergeCells('A2:H2'); // Địa chỉ
                    $sheet->mergeCells('A3:H3'); // Liên hệ
                    $sheet->mergeCells('A5:H5'); // Tiêu đề báo cáo
                    $sheet->mergeCells('A11:H11'); // Tóm tắt thống kê
                    $sheet->mergeCells('A19:H19'); // Chi tiết thiết bị
                    
                    // Tìm dòng bắt đầu của bảng dữ liệu
                    $dataStartRow = 21; // Dòng header của bảng
                    
                    // Style cho header bảng dữ liệu
                    $sheet->getStyle("A{$dataStartRow}:H{$dataStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '42A5F5']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                    ]);
                    
                    // Style cho dữ liệu
                    if ($lastRow > $dataStartRow) {
                        $sheet->getStyle("A" . ($dataStartRow + 1) . ":H{$lastRow}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                        ]);
                        
                        // Highlight dòng chẵn
                        for ($i = $dataStartRow + 1; $i <= $lastRow; $i += 2) {
                            $sheet->getStyle("A{$i}:H{$i}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]
                            ]);
                        }
                    }
                    
                    // Style cho cột trạng thái với màu sắc
                    for ($i = $dataStartRow + 1; $i <= $lastRow; $i++) {
                        $status = $sheet->getCell("D{$i}")->getValue();
                        $color = match($status) {
                            'Đang sử dụng' => '4CAF50',
                            'Chưa sử dụng' => '2196F3',
                            'Hư hỏng' => 'FF5722',
                            'Mất' => 'F44336',
                            'Sắp hết hạn' => 'FF9800',
                            default => '757575'
                        };
                        
                        if ($color !== '757575') {
                            $sheet->getStyle("D{$i}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => $color]]
                            ]);
                        }
                    }
                    
                    // Auto filter cho bảng dữ liệu
                    if ($lastRow > $dataStartRow) {
                        $sheet->setAutoFilter("A{$dataStartRow}:H{$lastRow}");
                    }
                    
                    // Freeze panes
                    $sheet->freezePane('A' . ($dataStartRow + 1));
                    
                    // Thêm footer
                    $footerRow = $lastRow + 2;
                    $sheet->setCellValue("A{$footerRow}", "Ghi chú: Báo cáo được tạo tự động từ hệ thống quản lý tài sản");
                    $sheet->getStyle("A{$footerRow}")->applyFromArray([
                        'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']]
                    ]);
                    $sheet->mergeCells("A{$footerRow}:H{$footerRow}");
                    
                    $signatureRow = $footerRow + 3;
                    $sheet->setCellValue("F{$signatureRow}", "Người lập báo cáo");
                    $sheet->setCellValue("F" . ($signatureRow + 1), "(Ký tên và đóng dấu)");
                    $sheet->getStyle("F{$signatureRow}:F" . ($signatureRow + 1))->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                    ]);
                }
            ];
        }
        
    }, 'bao-cao-thong-ke-tai-san-thiet-bi-' . date('Y-m-d-H-i-s') . '.xlsx');
}

 public function exportAndSendEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'subject' => 'required|string',
        'message' => 'nullable|string'
    ]);

    try {
        // Tạo file Excel với báo cáo chuyên nghiệp
        $fileName = 'bao-cao-thong-ke-tai-san-thiet-bi-' . date('Y-m-d-H-i-s') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);
        
        Excel::store(new class($request) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithColumnWidths, WithEvents {
            protected $request;
            protected $data;
            protected $statistics = [];
            protected $reportDate;
            protected $filterInfo;

            public function __construct($request)
            {
                $this->request = $request;
                $this->reportDate = now();
                $this->data = $this->buildQuery()->get();
                $this->calculateStatistics();
                $this->buildFilterInfo();
            }

            protected function calculateStatistics()
            {
                $this->statistics = [
                    'total' => $this->data->count(),
                    'in_use' => $this->data->where('MaTrangThai', 'TT001')->count(),
                    'available' => $this->data->where('MaTrangThai', 'TT002')->count(),
                    'broken' => $this->data->where('MaTrangThai', 'TT003')->count(),
                    'lost' => $this->data->where('MaTrangThai', 'TT004')->count(),
                    'expiring' => $this->data->where('MaTrangThai', 'TT005')->count(),
                    'departments' => $this->data->whereNotNull('TenBoPhan')->pluck('TenBoPhan')->unique()->count(),
                ];
            }

            protected function buildFilterInfo()
        {
           $filters = [];
    
            if ($this->request->status && $this->request->status !== 'all') {
                $statusName = match($this->request->status) {
                    'TT001' => 'Đang sử dụng',
                    'TT002' => 'Chưa sử dụng',
                    'TT003' => 'Hư hỏng',
                    'TT004' => 'Mất',
                    'TT005' => 'Sắp hết hạn',
                    default => 'Không xác định'
                };
                $filters[] = "Trạng thái: {$statusName}";
            }

            if ($this->request->fromDate && $this->request->toDate) {
                $filters[] = "Từ ngày: {$this->request->fromDate} đến {$this->request->toDate}";
            }

            if ($this->request->nhan_vien) {
                $filters[] = "Nhân viên: {$this->request->nhan_vien}";
            }

            if ($this->request->bo_phan) {
                $filters[] = "Bộ phận: {$this->request->bo_phan}";
            }

            if ($this->request->ten_tai_san) {
                $filters[] = "Tên tài sản: {$this->request->ten_tai_san}";
            }

            if ($this->request->ma_tai_san_nhap) {
                $filters[] = "Mã tài sản nhập: {$this->request->ma_tai_san_nhap}";
            }

            if ($this->request->ma_tai_san) {
                $filters[] = "Mã tài sản: {$this->request->ma_tai_san}";
            }

            $this->filterInfo = !empty($filters) ? implode(' | ', $filters) : 'Tất cả thiết bị';
        }

            protected function buildQuery()
            {
                 $query = DB::table('tai_san_nhap_ct as tsn')
        ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
        ->leftJoin(DB::raw('(
            SELECT 
                pctb1.MaTaiSanNhap,
                pctb1.MaNVNhanTB,
                pctb1.MaBoPhan,
                pctb1.created_at as NgayCapThietBi
            FROM phieu_cap_thiet_bi pctb1
            WHERE pctb1.id = (
                SELECT MAX(pctb2.id) 
                FROM phieu_cap_thiet_bi pctb2 
                WHERE pctb2.MaTaiSanNhap = pctb1.MaTaiSanNhap
            )
        ) as pctb_latest'), 'pctb_latest.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
        ->leftJoin('nhan_vien as nv', 'pctb_latest.MaNVNhanTB', '=', 'nv.MaDuPhong')
        ->leftJoin('bo_phan as bp', 'bp.MaDuPhong', '=', 'pctb_latest.MaBoPhan')
        ->select([
            'tsn.MaTaiSanNhap',
            'tsn.MaTaiSan',
            'ts.TenTaiSan',
            'tsn.MaTrangThai',
            'tsn.SoLuong',
            'tsn.created_at',
            DB::raw("FORMAT(tsn.created_at, 'dd/MM/yyyy') as NgayNhap"),
            DB::raw("FORMAT(tsn.NgayHetHanBaoHanh, 'dd/MM/yyyy') as NgayHetHanBaoHanh"),
            DB::raw("CASE WHEN tsn.MaTrangThai = 'TT001' THEN nv.HoTen ELSE NULL END as NguoiSuDung"),
            DB::raw("CASE WHEN tsn.MaTrangThai = 'TT001' THEN bp.TenBoPhan ELSE NULL END as TenBoPhan"),
            DB::raw("FORMAT(pctb_latest.NgayCapThietBi, 'dd/MM/yyyy') as NgayCapThietBiCuoi")
        ]);

                // Filter theo trạng thái
                if ($this->request->filled('status') && $this->request->status !== 'all') {
					if ($this->request->status === 'TT005') {
						// Lọc các thiết bị sắp hết hạn bảo hành (còn <= 30 ngày)
						$query->whereNotNull('tsn.NgayHetHanBaoHanh')
							  ->whereDate('tsn.NgayHetHanBaoHanh', '>', now())
							  ->whereDate('tsn.NgayHetHanBaoHanh', '<=', now()->addDays(30));
					} else {
						$query->where('tsn.MaTrangThai', $this->request->status);
					}
				}

                // Filter theo ngày nhập
                if ($this->request->filled('fromDate') && $this->request->filled('toDate')) {
                    try {
                        $fromDate = Carbon::parse($this->request->fromDate)->startOfDay();
                        $toDate = Carbon::parse($this->request->toDate)->endOfDay();
                        $query->whereBetween('tsn.created_at', [$fromDate, $toDate]);
                    } catch (\Exception $e) {
                        // Bỏ qua filter nếu lỗi parse date
                    }
                }

                // Filter theo nhân viên sử dụng
                if ($this->request->filled('nhan_vien')) {
                    $query->where('nv.HoTen', 'like', '%' . $this->request->nhan_vien . '%');
                }

                // Filter theo bộ phận
                if ($this->request->filled('bo_phan')) {
                    $query->where('bp.TenBoPhan', 'like', '%' . $this->request->bo_phan . '%');
                }

                // Filter theo tên tài sản
                if ($this->request->filled('ten_tai_san')) {
                    $query->where('ts.TenTaiSan', 'like', '%' . $this->request->ten_tai_san . '%');
                }

                // Filter theo mã tài sản nhập
                if ($this->request->filled('ma_tai_san_nhap')) {
                    $query->where('tsn.MaTaiSanNhap', $this->request->ma_tai_san_nhap);
                }

                // Filter theo mã tài sản
                if ($this->request->filled('ma_tai_san')) {
                    $query->where('tsn.MaTaiSan', $this->request->ma_tai_san);
                }
                $this->filterInfo = !empty($filters) ? implode(' | ', $filters) : 'Tất cả thiết bị';
        
                return $query->orderBy('tsn.created_at', 'desc');
            }
            public function collection()
            {
                $reportHeader = collect([
                    // Header chính
                    $this->createRow(['CÔNG TY TNHH ABC', '', '', '', '', '', '', '']),
                    $this->createRow(['Địa chỉ: 123 Đường XYZ, Quận 1, TP.HCM', '', '', '', '', '', '', '']),
                    $this->createRow(['Điện thoại: (028) 1234 5678 | Email: info@company.com', '', '', '', '', '', '', '']),
                    $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                    
                    // Tiêu đề báo cáo
                    $this->createRow(['BÁO CÁO THỐNG KÊ QUẢN LÝ TÀI SẢN THIẾT BỊ', '', '', '', '', '', '', '']),
                    $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                    
                    // Thông tin báo cáo
                    $this->createRow(['Ngày lập báo cáo:', $this->reportDate->format('d/m/Y H:i:s'), '', '', '', '', '', '']),
                    $this->createRow(['Người lập:', 'Hệ thống quản lý tài sản', '', '', '', '', '', '']),
                    $this->createRow(['Phạm vi báo cáo:', $this->filterInfo, '', '', '', '', '', '']),
                    $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                    
                    // Tóm tắt thống kê
                    $this->createRow(['I. TÓM TẮT THỐNG KÊ', '', '', '', '', '', '', '']),
                    $this->createRow(['Tổng số thiết bị:', $this->statistics['total'], 'thiết bị', '', '', '', '', '']),
                    $this->createRow(['Thiết bị đang sử dụng:', $this->statistics['in_use'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['in_use']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                    $this->createRow(['Thiết bị chưa sử dụng:', $this->statistics['available'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['available']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                    $this->createRow(['Thiết bị hư hỏng:', $this->statistics['broken'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['broken']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                    $this->createRow(['Thiết bị bị mất:', $this->statistics['lost'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['lost']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                    $this->createRow(['Thiết bị sắp hết hạn BH:', $this->statistics['expiring'], 'thiết bị', '(' . ($this->statistics['total'] > 0 ? round($this->statistics['expiring']/$this->statistics['total']*100, 1) : 0) . '%)', '', '', '', '']),
                    $this->createRow(['Số bộ phận đang sử dụng:', $this->statistics['departments'], 'bộ phận', '', '', '', '', '']),
                    $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                    
                    // Phần chi tiết
                    $this->createRow(['II. CHI TIẾT THIẾT BỊ', '', '', '', '', '', '', '']),
                    $this->createRow(['', '', '', '', '', '', '', '']), // Dòng trống
                    $this->createRow(['Mã tài sản nhập', 'Mã tài sản', 'Tên tài sản', 'Trạng thái', 'Ngày nhập', 'Ngày hết hạn BH', 'Người sử dụng', 'Bộ phận sử dụng']), 

                ]); 

                return $reportHeader->concat($this->data);
            }

            private function createRow($data)
            {
                return (object)[
                    'MaTaiSanNhap' => $data[0] ?? '',
                    'MaTaiSan' => $data[1] ?? '',
                    'TenTaiSan' => $data[2] ?? '',
                    'MaTrangThai' => $data[3] ?? '',
                    'NgayNhap' => $data[4] ?? '',
                    'NgayHetHanBaoHanh' => $data[5] ?? '',
                    'NguoiSuDung' => $data[6] ?? '',
                    'TenBoPhan' => $data[7] ?? ''
                ];
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
                    'Bộ phận sử dụng'
                ];
            }

            public function map($row): array
            {
                // Kiểm tra nếu là dòng dữ liệu thực tế
                if (isset($row->MaTrangThai) && in_array($row->MaTrangThai, ['TT001', 'TT002', 'TT003', 'TT004', 'TT005'])) {
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
                        $row->NguoiSuDung ?? '-',
                        $row->TenBoPhan ?? '-'
                    ];
                }

                return [
                    $row->MaTaiSanNhap,
                    $row->MaTaiSan,
                    $row->TenTaiSan,
                    $row->MaTrangThai,
                    $row->NgayNhap,
                    $row->NgayHetHanBaoHanh,
                    $row->NguoiSuDung,
                    $row->TenBoPhan
                ];
            }

            public function styles(Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1565C0']]],
                    2 => ['font' => ['size' => 11, 'color' => ['rgb' => '666666']]],
                    3 => ['font' => ['size' => 11, 'color' => ['rgb' => '666666']]],
                    5 => [
                        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                    ],
                    7 => ['font' => ['bold' => true, 'size' => 11]],
                    8 => ['font' => ['bold' => true, 'size' => 11]],
                    9 => ['font' => ['bold' => true, 'size' => 11]],
                    11 => [
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1565C0']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
                    ],
                    20 => [
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1565C0']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
                    ],
                ];
            }

            public function title(): string
            {
                return 'Báo cáo thống kê tài sản';
            }

            public function columnWidths(): array
            {
                return [
                    'A' => 20, 'B' => 15, 'C' => 35, 'D' => 18,
                    'E' => 15, 'F' => 18, 'G' => 25, 'H' => 25,
                ];
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function(AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();
                        $lastRow = $sheet->getHighestRow();
                        
                        // Merge cells cho các tiêu đề
                        $sheet->mergeCells('A1:H1');
                        $sheet->mergeCells('A2:H2');
                        $sheet->mergeCells('A3:H3');
                        $sheet->mergeCells('A5:H5');
                        $sheet->mergeCells('A11:H11');
                        $sheet->mergeCells('A20:H20');
                        
                        $dataStartRow = 22;
                        
                        // Style cho header bảng dữ liệu
                        $sheet->getStyle("A{$dataStartRow}:H{$dataStartRow}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '42A5F5']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                        ]);
                        
                        // Style cho dữ liệu
                        if ($lastRow > $dataStartRow) {
                            $sheet->getStyle("A" . ($dataStartRow + 1) . ":H{$lastRow}")->applyFromArray([
                                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                            ]);
                            
                            // Highlight dòng chẵn
                            for ($i = $dataStartRow + 1; $i <= $lastRow; $i += 2) {
                                $sheet->getStyle("A{$i}:H{$i}")->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]
                                ]);
                            }
                        }
                        
                        // Style màu sắc cho trạng thái
                        for ($i = $dataStartRow + 1; $i <= $lastRow; $i++) {
                            $status = $sheet->getCell("D{$i}")->getValue();
                            $color = match($status) {
                                'Đang sử dụng' => '4CAF50',
                                'Chưa sử dụng' => '2196F3',
                                'Hư hỏng' => 'FF5722',
                                'Mất' => 'F44336',
                                'Sắp hết hạn' => 'FF9800',
                                default => '757575'
                            };
                            
                            if ($color !== '757575') {
                                $sheet->getStyle("D{$i}")->applyFromArray([
                                    'font' => ['bold' => true, 'color' => ['rgb' => $color]]
                                ]);
                            }
                        }
                        
                        // Auto filter và freeze panes
                        if ($lastRow > $dataStartRow) {
                            $sheet->setAutoFilter("A{$dataStartRow}:H{$lastRow}");
                        }
                        $sheet->freezePane('A' . ($dataStartRow + 1));
                        
                        // Footer
                        $footerRow = $lastRow + 2;
                        $sheet->setCellValue("A{$footerRow}", "Ghi chú: Báo cáo được tạo tự động từ hệ thống quản lý tài sản - " . now()->format('d/m/Y H:i:s'));
                        $sheet->getStyle("A{$footerRow}")->applyFromArray([
                            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']]
                        ]);
                        $sheet->mergeCells("A{$footerRow}:H{$footerRow}");
                        
                        
                    }
                ];
            }
        }, $fileName, 'public');

        // Tạo thông tin thống kê để gửi trong email
        $data = DB::table('tai_san_nhap_ct as tsn')
            ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
            ->leftJoin('phieu_cap_thiet_bi as pctb', 'pctb.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
            ->leftJoin('nhan_vien as nv', 'pctb.MaNVNhanTB', '=', 'nv.MaDuPhong')
            ->leftJoin('bo_phan as bp', 'bp.MaDuPhong', '=', 'pctb.MaBoPhan')
            ->select('tsn.MaTrangThai')
            ->get();

        $statistics = [
            'total' => $data->count(),
            'in_use' => $data->where('MaTrangThai', 'TT001')->count(),
            'available' => $data->where('MaTrangThai', 'TT002')->count(),
            'broken' => $data->where('MaTrangThai', 'TT003')->count(),
            'lost' => $data->where('MaTrangThai', 'TT004')->count(),
            'expiring' => $data->where('MaTrangThai', 'TT005')->count(),
        ];

        // Gửi email với template chuyên nghiệp
        Mail::to($request->email)->send(new class($fileName, $filePath, $request->subject, $request->message, $statistics) extends Mailable {
            use Queueable, SerializesModels;

            protected $fileName;
            protected $filePath;
            protected $subjectText;
            protected $messageText;
            protected $statistics;

            public function __construct($fileName, $filePath, $subject, $message, $statistics)
            {
                $this->fileName = $fileName;
                $this->filePath = $filePath;
                $this->subjectText = $subject;
                $this->messageText = $message;
                $this->statistics = $statistics;
            }

            public function build()
            {
                return $this->subject($this->subjectText)
                    ->markdown('emails.statistics')
                    ->with([
                        'messageText' => $this->messageText,
                        'statistics' => $this->statistics,
                        'reportDate' => now()->format('d/m/Y H:i:s'),
                        'fileName' => $this->fileName
                    ])
                    ->attach($this->filePath, [
                        'as' => $this->fileName,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            }
        });

        // Xóa file tạm sau khi gửi
        if (Storage::exists('public/' . $fileName)) {
            Storage::delete('public/' . $fileName);
        }

        return response()->json([
            'success' => true,
            'message' => 'Báo cáo thống kê tài sản thiết bị đã được gửi qua email thành công',
            'details' => [
                'email' => $request->email,
                'file_name' => $fileName,
                'sent_at' => now()->format('d/m/Y H:i:s'),
                'total_devices' => $statistics['total']
            ]
        ]);

    } catch (\Exception $e) {
        // Xóa file nếu có lỗi
        if (isset($fileName) && Storage::exists('public/' . $fileName)) {
            Storage::delete('public/' . $fileName);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gửi email thất bại: ' . $e->getMessage(),
            'error' => [
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]
        ], 500);
    }
}

    private function buildDeviceQuery(Request $request)
{
     $query = DB::table('tai_san_nhap_ct as tsn')
        ->join('tai_san as ts', 'tsn.MaTaiSan', '=', 'ts.MaDuPhong')
        ->leftJoin(DB::raw('(
            SELECT 
                pctb1.MaTaiSanNhap,
                pctb1.MaNVNhanTB,
                pctb1.MaBoPhan,
                pctb1.created_at as NgayCapThietBi
            FROM phieu_cap_thiet_bi pctb1
            WHERE pctb1.id = (
                SELECT MAX(pctb2.id) 
                FROM phieu_cap_thiet_bi pctb2 
                WHERE pctb2.MaTaiSanNhap = pctb1.MaTaiSanNhap
            )
        ) as pctb_latest'), 'pctb_latest.MaTaiSanNhap', '=', 'tsn.MaTaiSanNhap')
        ->leftJoin('nhan_vien as nv', 'pctb_latest.MaNVNhanTB', '=', 'nv.MaDuPhong')
        ->leftJoin('bo_phan as bp', 'bp.MaDuPhong', '=', 'pctb_latest.MaBoPhan')
        ->select([
            'tsn.MaTaiSanNhap',
            'tsn.MaTaiSan',
            'ts.TenTaiSan',
            'tsn.MaTrangThai',
            'tsn.SoLuong',
            'tsn.created_at',
            DB::raw("FORMAT(tsn.created_at, 'dd/MM/yyyy') as NgayNhap"),
            DB::raw("FORMAT(tsn.NgayHetHanBaoHanh, 'dd/MM/yyyy') as NgayHetHanBaoHanh"),
            DB::raw("CASE WHEN tsn.MaTrangThai = 'TT001' THEN nv.HoTen ELSE NULL END as NguoiSuDung"),
            DB::raw("CASE WHEN tsn.MaTrangThai = 'TT001' THEN bp.TenBoPhan ELSE NULL END as TenBoPhan"),
            DB::raw("FORMAT(pctb_latest.NgayCapThietBi, 'dd/MM/yyyy') as NgayCapThietBiCuoi")
        ]);

    // Filter theo trạng thái (ví dụ: TT002 = hỏng, TT003 = mất)
    if ($request->filled('status') && $request->status !== 'all') {
    if ($request->status === 'TT005') {
        $query->whereNotNull('tsn.NgayHetHanBaoHanh')
              ->whereDate('tsn.NgayHetHanBaoHanh', '>', now())
              ->whereDate('tsn.NgayHetHanBaoHanh', '<=', now()->addDays(30));
    } else {
        $query->where('tsn.MaTrangThai', $request->status);
    }
}

    // Filter theo ngày nhập
    if ($request->filled('fromDate') && $request->filled('toDate')) {
        try {
            $fromDate = \Carbon\Carbon::parse($request->fromDate)->startOfDay();
            $toDate = \Carbon\Carbon::parse($request->toDate)->endOfDay();
            $query->whereBetween('tsn.created_at', [$fromDate, $toDate]);
        } catch (\Exception $e) {
            \Log::error('Lỗi parse date trong filter: ' . $e->getMessage());
        }
    }

    // Filter theo nhân viên sử dụng
    if ($request->filled('nhan_vien')) {
        $query->where('nv.HoTen', 'like', '%' . $request->nhan_vien . '%');
    }

    // Filter theo bộ phận
    if ($request->filled('bo_phan')) {
        $query->where('bp.TenBoPhan', 'like', '%' . $request->bo_phan . '%');
    }

    // Filter theo tên tài sản
    if ($request->filled('ten_tai_san')) {
        $query->where('ts.TenTaiSan', 'like', '%' . $request->ten_tai_san . '%');
    }

    // Filter theo mã tài sản nhập
    if ($request->filled('ma_tai_san_nhap')) {
        $query->where('tsn.MaTaiSanNhap', $request->ma_tai_san_nhap);
    }

    // Filter theo mã tài sản
    if ($request->filled('ma_tai_san')) {
        $query->where('tsn.MaTaiSan', $request->ma_tai_san);
    }

    return $query->orderBy('tsn.created_at', 'desc');
}
public function getDeviceLifecycle(Request $request, $maTaiSanNhap)
{
    // Lấy tất cả phiếu cấp phát liên quan, loại bỏ phiếu TrangThaiDeXuat = 0
    $capPhats = DB::table('phieu_cap_thiet_bi')
        ->where('MaTaiSanNhap', $maTaiSanNhap)
        ->where(function($q) {
            $q->whereNull('TrangThaiDeXuat')
              ->orWhere('TrangThaiDeXuat', '!=', 0);
        })
        ->orderBy('NgayCap', 'asc')
        ->get();

    // Lấy tất cả phiếu thu hồi liên quan, loại bỏ phiếu TrangThaiDeXuat = 0
    $thuHois = DB::table('phieu_tra_thiet_bi')
        ->whereIn('MaPhieuCapThietBi', function($q) use ($maTaiSanNhap) {
            $q->select('MaDuPhong')
              ->from('phieu_cap_thiet_bi')
              ->where('MaTaiSanNhap', $maTaiSanNhap)
              ->where(function($q2) {
                  $q2->whereNull('TrangThaiDeXuat')
                     ->orWhere('TrangThaiDeXuat', '!=', 0);
              });
        })
        ->where(function($q) {
            $q->whereNull('TrangThaiDeXuat')
              ->orWhere('TrangThaiDeXuat', '!=', 0);
        })
        ->orderBy('NgayTra', 'asc')
        ->get();

    return response()->json([
        'capPhats' => $capPhats,
        'thuHois' => $thuHois,
    ]);
}
}