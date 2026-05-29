<?php

namespace App\Http\Controllers;

use App\Models\DeXuat; // Đảm bảo import đúng model DeXuat
use Illuminate\Http\Request;

class DeXuatController extends Controller
{
    public function index()
    {
        return DeXuat::all(); // Sửa Proposal thành DeXuat
    }
    private function generateMaDuPhong()
{
    $last = DeXuat::orderByDesc('MaDuPhong')->first();

    if ($last && preg_match('/DX(\d+)/', $last->MaDuPhong, $matches)) {
        $number = (int) $matches[1] + 1;
    } else {
        $number = 1;
    }

    return 'DX' . str_pad($number, 3, '0', STR_PAD_LEFT);
}
    public function store(Request $request)
    {
        $data = $request->validate([
            'TenDeXuat' => 'required|string|max:255',
            'GhiChu' => 'nullable|string',
        ]);

        // Tự sinh MaDuPhong
        $data['MaDuPhong'] = $this->generateMaDuPhong();

        $proposal = DeXuat::create($data);

        return response()->json($proposal, 201);
    }

    public function show($id)
    {
        return DeXuat::findOrFail($id); // Sửa Proposal thành DeXuat
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'TenDeXuat' => 'required|string|max:255',
            'MaDuPhong' => 'required|string|max:255',
            'GhiChu' => 'nullable|string',
        ]);

        $proposal = DeXuat::findOrFail($id); // Sửa Proposal thành DeXuat
        $proposal->update($data);
        return response()->json($proposal);
    }

    public function destroy($id)
    {
        DeXuat::destroy($id); // Sửa Proposal thành DeXuat
        return response()->json(null, 204);
    }
}
