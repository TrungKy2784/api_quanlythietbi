<?php

namespace App\Http\Controllers;

use App\Models\TaiSan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\LoaiTaiSan;
use Illuminate\Support\Facades\Storage;
class TaiSanController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = DB::table('tai_san')
            ->join('loai_tai_san', 'tai_san.IdLoaiTaiSan', '=', 'loai_tai_san.MaDuPhong')
            ->select(
                'tai_san.*',           // Lấy tất cả trường từ bảng tài sản
                'loai_tai_san.TenLoai' // Lấy tên loại từ bảng loại tài sản
        );

        if ($status) {
            switch ($status) {
                case 'expiring':
                    $query->whereDate('ngay_het_han', '<=', now());
                    break;
                case 'awaiting_approval':
                    $query->where('trang_thai', 'Chờ Duyệt');
                    break;
                case 'awaiting_return':
                    $query->where('trang_thai', 'Chờ Thu Hồi');
                    break;
            }
        }

        return response()->json($query->get());
    }

    private function generateMaDuPhong()
    {
        $last = TaiSan::orderByDesc('MaDuPhong')->first();

        if ($last && preg_match('/MTS(\d+)/', $last->MaDuPhong, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }

        return 'MTS' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    private function generateMaSo()
    {
        $last = TaiSan::orderByDesc('MaSo')->first();

        if ($last && preg_match('/MSTS(\d+)/', $last->MaSo, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }

        return 'MSTS' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'IDLoaiTaiSan' => 'required|string|max:14',
            'TenTaiSan' => 'required|string|max:255',
            'DonViTinh' => 'nullable|string|max:50',
            'GhiChu' => 'nullable|string|max:255',
            'HinhAnh' => 'nullable|image|max:2048',
        ]);

        // Tự động sinh mã trên backend
        $data['MaDuPhong'] = $this->generateMaDuPhong();
        $data['MaSo'] = $this->generateMaSo();

        if ($request->hasFile('HinhAnh')) {
            $file = $request->file('HinhAnh');
            $filename = $file->getClientOriginalName(); 
            $file->move(public_path('img'), $filename);
            $data['HinhAnh'] = $filename;
        }

        $taiSan = TaiSan::create($data);
        return response()->json($taiSan, 201);
    }

    public function show($id)
    {
        $taiSan = TaiSan::findOrFail($id);
        return response()->json($taiSan);
    }

    public function update(Request $request, $id)
    {
        $taiSan = TaiSan::findOrFail($id);

        $validatedData = $request->validate([
            'TenTaiSan' => 'required|string|max:255',
            'IDLoaiTaiSan' => 'required|string|max:14',
            'DonViTinh' => 'nullable|string|max:50',
            'GhiChu' => 'nullable|string|max:255',
            'HinhAnh' => 'nullable|image|max:2048',
        ]);

        $updateData = $request->only([
            'TenTaiSan', 'IDLoaiTaiSan', 'DonViTinh', 'GhiChu'
        ]);

        if ($request->hasFile('HinhAnh')) {
            $file = $request->file('HinhAnh');
             $filename = $file->getClientOriginalName();
            $file->move(public_path('img'), $filename);

            if ($taiSan->HinhAnh && file_exists(public_path('img/' . $taiSan->HinhAnh))) {
                unlink(public_path('img/' . $taiSan->HinhAnh));
            }

            $updateData['HinhAnh'] = $filename;
        }

        $taiSan->update($updateData);
        return response()->json($taiSan, 200);
    }

    public function destroy($id)
    {
        $taiSan = TaiSan::findOrFail($id);
        $taiSan->delete();

        return response()->json(['message' => 'Tài sản đã được xóa thành công']);
    }
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*.TenTaiSan' => 'required|string|max:255',
            '*.IDLoaiTaiSan' => 'required|string|max:14|exists:loai_tai_san,MaDuPhong',
            '*.DonViTinh' => 'nullable|string|max:50',
            '*.GhiChu' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $importedCount = 0;
        $errors = [];
        
        foreach ($request->all() as $index => $item) {
            try {
                $data = [
                    'TenTaiSan' => $item['TenTaiSan'],
                    'IDLoaiTaiSan' => $item['IDLoaiTaiSan'],
                    'DonViTinh' => $item['DonViTinh'] ?? null,
                    'GhiChu' => $item['GhiChu'] ?? null,
                    'MaDuPhong' => $this->generateMaDuPhong(),
                    'MaSo' => $this->generateMaSo(),
                ];

                TaiSan::create($data);
                $importedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Import hoàn thành',
            'count' => $importedCount,
            'errors' => $errors
        ]);
    }
    public function export()
    {
        $assets = TaiSan::with('loaiTaiSan')->get();
        
        $data = $assets->map(function ($item) {
            return [
                'Tên tài sản' => $item->TenTaiSan,
                'ID Loại' => $item->IDLoaiTaiSan,
                'Loại tài sản' => $item->loaiTaiSan->TenLoai ?? '',
                'Đơn vị tính' => $item->DonViTinh,
                'Ghi chú' => $item->GhiChu,
                'Hình ảnh' => $item->HinhAnh ? asset('img/' . $item->HinhAnh) : '',
            ];
        });

        return response()->json($data);
    }
public function importWithImages(Request $request)
{
    $validator = Validator::make($request->all(), [
        '*.TenTaiSan' => 'required|string|max:255',
        '*.IDLoaiTaiSan' => 'required|string|max:14|exists:loai_tai_san,MaDuPhong',
        '*.DonViTinh' => 'nullable|string|max:50',
        '*.GhiChu' => 'nullable|string|max:255',
        '*.HinhAnh' => 'nullable|url',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $validator->errors()
        ], 422);
    }

    $importedCount = 0;
    $errors = [];
    
foreach ($request->all() as $index => $item) {
        try {
            $data = [
                'TenTaiSan' => $item['TenTaiSan'],
                'IDLoaiTaiSan' => $item['IDLoaiTaiSan'],
                'DonViTinh' => $item['DonViTinh'] ?? null,
                'GhiChu' => $item['GhiChu'] ?? null,
                'MaDuPhong' => $this->generateMaDuPhong(),
                'MaSo' => $this->generateMaSo(),
            ];

            if (!empty($item['HinhAnh'])) {
                $url = $item['HinhAnh'];
                
                // Kiểm tra xem URL có phải từ hệ thống không
                if (strpos($url, asset('img/')) !== false) {
                    $filename = basename(parse_url($url, PHP_URL_PATH));
                    if (file_exists(public_path('img/' . $filename))) {
                        $data['HinhAnh'] = $filename;
                    }
                } else {
                    // Tải ảnh từ URL bên ngoài
                    $imageData = file_get_contents($url);
                    if ($imageData !== false) {
                        $filename = 'imported_' . time() . '_' . $index . '.jpg';
                        file_put_contents(public_path('img/' . $filename), $imageData);
                        $data['HinhAnh'] = $filename;
                    }
                }
            }


            TaiSan::create($data);
            $importedCount++;
            
        } catch (\Exception $e) {
            $errors[] = [
                'row' => $index + 1,
                'error' => $e->getMessage(),
                'item' => $item
            ];
        }
    }

    return response()->json([
        'message' => 'Import hoàn thành',
        'count' => $importedCount,
        'errors' => $errors
    ]);
}
public function exportWithImages()
{
    $assets = TaiSan::with('loaiTaiSan')->get();
    
    $data = [];
    
    foreach ($assets as $asset) {
        $imageBase64 = null;
        if ($asset->HinhAnh && file_exists(public_path('img/' . $asset->HinhAnh))) {
            $imageContent = file_get_contents(public_path('img/' . $asset->HinhAnh));
            $imageBase64 = 'data:image/' . pathinfo($asset->HinhAnh, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageContent);
        }
        
        $data[] = [
            'Tên tài sản' => $asset->TenTaiSan,
            'ID Loại' => $asset->IDLoaiTaiSan,
            'Loại tài sản' => $asset->loaiTaiSan->TenLoai ?? '',
            'Đơn vị tính' => $asset->DonViTinh,
            'Ghi chú' => $asset->GhiChu,
            'Hình ảnh' => $imageBase64, 
            'Tên file ảnh' => $asset->HinhAnh
        ];
    }

    return response()->json($data);
}
public function exportWithExternalImages()
{
    $assets = TaiSan::with('loaiTaiSan')->get();
    
    $data = $assets->map(function ($asset) {
        $imageBase64 = null;
        $imagePath = null;
        
        if ($asset->HinhAnh && file_exists(public_path('img/' . $asset->HinhAnh))) {
            $imagePath = 'img/' . $asset->HinhAnh;
            $imageContent = file_get_contents(public_path($imagePath));
            $imageBase64 = base64_encode($imageContent);
        }
        
        return [
            'Tên tài sản' => $asset->TenTaiSan,
            'ID Loại' => $asset->IDLoaiTaiSan,
            'Loại tài sản' => $asset->loaiTaiSan->TenLoai ?? '',
            'Đơn vị tính' => $asset->DonViTinh,
            'Ghi chú' => $asset->GhiChu,
            'Hình ảnh' => $imageBase64,
            'Tên file ảnh' => $asset->HinhAnh,
            'URL ảnh' => $imagePath ? asset($imagePath) : null
        ];
    });

    return response()->json($data);
}
}
