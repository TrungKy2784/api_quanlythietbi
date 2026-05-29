<?php

namespace App\Http\Controllers;

use App\Models\CoSo;
use Illuminate\Http\Request;

class CoSoController extends Controller
{
    // Lấy tất cả các cơ sở
    public function index()
    {
        $coso = CoSo::all();
        return response()->json($coso);
    }
    private function generateMaDuPhong()
{
    $lastMa = CoSo::orderByDesc('MaDuPhong')->first();

    if ($lastMa && preg_match('/CS(\d+)/', $lastMa->MaDuPhong, $matches)) {
        $number = (int) $matches[1] + 1;
    } else {
        $number = 1;
    }

    return 'CS' . str_pad($number, 3, '0', STR_PAD_LEFT);
}

    // Tạo mới cơ sở
public function store(Request $request)
{
    $request->validate([
        'TenCoSo' => 'required',
        'DiaChi' => 'required',
    ]);

    $maTuDong = $this->generateMaDuPhong(); // Sử dụng hàm generateMaDuPhong

    $coso = CoSo::create([
        'MaDuPhong' => $maTuDong,
        'TenCoSo' => $request->TenCoSo,
        'DiaChi' => $request->DiaChi,
    ]);

    return response()->json($coso, 201);
}

    // Lấy cơ sở theo ID
    public function show($id)
    {
        $coso = CoSo::find($id);

        if (!$coso) {
            return response()->json(['message' => 'Cơ sở không tồn tại'], 404);
        }

        return response()->json($coso);
    }

    // Cập nhật cơ sở
   public function update(Request $request, $id)
{
    $request->validate([
        'MaDuPhong' => 'required|string|unique:co_so,MaDuPhong,' . $id,
        'TenCoSo' => 'required|string',
        'DiaChi' => 'nullable|string',
    ]);

    $coSo = CoSo::findOrFail($id);
    $coSo->MaDuPhong = $request->MaDuPhong;
    $coSo->TenCoSo = $request->TenCoSo;
    $coSo->DiaChi = $request->DiaChi;
    $coSo->save();

    return response()->json($coSo);
}


    // Xóa cơ sở
    public function destroy($id)
    {
        $coso = CoSo::find($id);

        if (!$coso) {
            return response()->json(['message' => 'Cơ sở không tồn tại'], 404);
        }

        $coso->delete();
        return response()->json(['message' => 'Cơ sở đã bị xóa']);
    }
}
