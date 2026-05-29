<?php

namespace App\Http\Controllers;

use App\Models\LoaiTaiSan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoaiTaiSanController extends Controller
{
    /**
     * Lấy danh sách loại tài sản.
     */
    public function index()
    {
        $loaitaisans = LoaiTaiSan::all();
        return response()->json($loaitaisans);
    }
private function generateMaDuPhong()
{
    $last = LoaiTaiSan::orderByDesc('MaDuPhong')->first();

    if ($last && preg_match('/LTS(\d+)/', $last->MaDuPhong, $matches)) {
        $number = (int) $matches[1] + 1;
    } else {
        $number = 1;
    }

    return 'LTS' . str_pad($number, 3, '0', STR_PAD_LEFT);
}

    /**
     * Tạo mới loại tài sản.
     */
public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'TenLoai' => 'required|string|max:255',
            'GhiChu' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maDuPhong = $this->generateMaDuPhong();

        $loaitaisan = LoaiTaiSan::create([
            'MaDuPhong' => $maDuPhong,
            'TenLoai' => $request->TenLoai,
            'GhiChu' => $request->GhiChu,
        ]);

        return response()->json([
            'message' => 'Loại tài sản đã được tạo thành công',
            'data' => $loaitaisan
        ], 201);
    }


    /**
     * Lấy chi tiết loại tài sản.
     */
    public function show($id)
    {
        $loaitaisans = LoaiTaiSan::findOrFail($id);
        return response()->json($loaitaisans);
    }

    /**
     * Cập nhật loại tài sản.
     */
    public function update(Request $request, $id)
    {
        $loaitaisans = LoaiTaiSan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'TenLoai' => 'required|string|max:255',
            'GhiChu' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loaitaisans->update([
            'TenLoai' => $request->TenLoai,
            'GhiChu' => $request->GhiChu,
        ]);

        return response()->json(['message' => 'Loại tài sản đã được cập nhật thành công', 'data' => $loaitaisans]);
    }


    /**
     * Xóa loại tài sản.
     */
    public function destroy($id)
    {
        $loaitaisans = LoaiTaiSan::findOrFail($id);
        $loaitaisans->delete();

        return response()->json(['message' => 'Loại tài sản đã được xóa thành công']);
    }
}
