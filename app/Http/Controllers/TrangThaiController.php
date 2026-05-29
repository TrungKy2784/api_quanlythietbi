<?php

namespace App\Http\Controllers;

use App\Models\TrangThai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrangThaiController extends Controller
{
    /**
     * Lấy danh sách trạng thái.
     */
    public function index()
    {
        $trangthais = TrangThai::all();
        return response()->json($trangthais);
    }
    private function generateMaDuPhong()
    {
        $last = TrangThai::orderByDesc('MaDuPhong')->first();

        if ($last && preg_match('/TT(\d+)/', $last->MaDuPhong, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }

        return 'TT' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    /**
     * Tạo mới trạng thái.
     */
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'TenTrangThai' => 'required|string|max:50',
        'GhiChu' => 'nullable|string|max:50',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Tự sinh MaDuPhong
    $maDuPhong = $this->generateMaDuPhong();

    $trangthais = TrangThai::create([
        'MaDuPhong' => $maDuPhong,
        'TenTrangThai' => $request->TenTrangThai,
        'GhiChu' => $request->GhiChu,
    ]);

    return response()->json(['message' => 'Trạng thái đã được tạo thành công', 'data' => $trangthais], 201);
}


    /**
     * Lấy chi tiết trạng thái.
     */
    public function show($id)
    {
        $trangthais = TrangThai::findOrFail($id);
        return response()->json($trangthais);
    }

    /**
     * Cập nhật trạng thái.
     */
    public function update(Request $request, $id)
        {
            $trangthais = TrangThai::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'TenTrangThai' => 'required|string|max:50',
                'GhiChu' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $trangthais->update([
                'TenTrangThai' => $request->TenTrangThai,
                'GhiChu' => $request->GhiChu,
            ]);

            return response()->json(['message' => 'Trạng thái đã được cập nhật thành công', 'data' => $trangthais]);
        }

    /**
     * Xóa trạng thái.
     */
    public function destroy($id)
    {
        $trangthais = TrangThai::findOrFail($id);
        $trangthais->delete();

        return response()->json(['message' => 'Trạng thái đã được xóa thành công']);
    }
}
