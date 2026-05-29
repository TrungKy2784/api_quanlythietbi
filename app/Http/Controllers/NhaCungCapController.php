<?php

namespace App\Http\Controllers;

use App\Models\NhaCungCap;
use Illuminate\Http\Request;

class NhaCungCapController extends Controller
{
    public function index()
    {
        return NhaCungCap::all();
    }
private function generateMaDuPhong()
{
    $last = NhaCungCap::orderByDesc('MaDuPhong')->first();

    if ($last && preg_match('/NCC(\d+)/', $last->MaDuPhong, $matches)) {
        $number = (int) $matches[1] + 1;
    } else {
        $number = 1;
    }

    return 'NCC' . str_pad($number, 3, '0', STR_PAD_LEFT);
}
   public function store(Request $request)
{
    $request->validate([
        'TenNCC' => 'required|string|max:255',
        'DiaChiNCC' => 'nullable|string|max:255',
        'GhiChu' => 'nullable|string',
    ]);

    $nhaCungCap = new NhaCungCap();
    $nhaCungCap->TenNCC = $request->TenNCC;
    $nhaCungCap->MaDuPhong = $this->generateMaDuPhong(); // Tự động sinh mã
    $nhaCungCap->DiaChiNCC = $request->DiaChiNCC;
    $nhaCungCap->GhiChu = $request->GhiChu;
    $nhaCungCap->save();

    return response()->json([
        'message' => 'Nhà cung cấp đã được thêm thành công!',
        'data' => $nhaCungCap
    ], 201);
}

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'TenNCC' => 'required|string|max:255',
            'MaDuPhong' => 'required|string|max:255',
            'DiaChiNCC' => 'required|string|max:255',
            'GhiChu' => 'nullable|string',
        ]);

        $supplier = NhaCungCap::findOrFail($id);
        $supplier->update($data);

        return response()->json($supplier);
    }





    public function show($id)
    {
        return NhaCungCap::findOrFail($id);
    }

    public function destroy($id)
    {
        NhaCungCap::destroy($id);
        return response()->json(null, 204);
    }
}
