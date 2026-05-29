<?php

namespace App\Http\Controllers;

use App\Models\BoPhan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BoPhanController extends Controller
{
    // Lấy danh sách tất cả bộ phận
    public function index()
    {
        $bophan = DB::table('bo_phan')
            ->join('co_so', 'bo_phan.IDCoSo', '=', 'co_so.MaDuPhong')
            ->select('bo_phan.*', 'co_so.TenCoSo')
            ->get();

        return response()->json($bophan);
    }
    private function generateMaDuPhong()
{
    $last = BoPhan::orderByDesc('MaDuPhong')->first();

    if ($last && preg_match('/BP(\d+)/', $last->MaDuPhong, $matches)) {
        $number = (int) $matches[1] + 1;
    } else {
        $number = 1;
    }

    return 'BP' . str_pad($number, 3, '0', STR_PAD_LEFT);
}

    // Tạo mới bộ phận
  public function store(Request $request)
{
    $request->validate([
        'IDCoSo' => 'required',
        'TenBoPhan' => 'required',
    ]);

    $maDuPhong = $this->generateMaDuPhong();

    $bophan = BoPhan::create([
        'MaDuPhong' => $maDuPhong,
        'IDCoSo' => $request->IDCoSo,
        'TenBoPhan' => $request->TenBoPhan,
        'GhiChu' => $request->GhiChu,
    ]);

    return response()->json($bophan, 201);
}


    // Lấy bộ phận theo ID
    public function show($id)
    {
        $bophan = BoPhan::find($id);

        if (!$bophan) {
            return response()->json(['message' => 'Bộ phận không tồn tại'], 404);
        }

        return response()->json($bophan);
    }

    // Cập nhật bộ phận
    public function update(Request $request, $id)
    {
        $bophan = BoPhan::find($id);

        if (!$bophan) {
            return response()->json(['message' => 'Bộ phận không tồn tại'], 404);
        }

        $request->validate([
            'MaDuPhong' => 'required|unique:bo_phan,MaDuPhong,' . $id,
            'IDCoSo' => 'required',
            'TenBoPhan' => 'required|unique:bo_phan,TenBoPhan,' . $id,
        ]);

        $bophan->update([
            'MaDuPhong' => $request->MaDuPhong,
            'IDCoSo' => $request->IDCoSo,
            'TenBoPhan' => $request->TenBoPhan,
            'GhiChu' => $request->GhiChu,
        ]);

        return response()->json($bophan);
    }

    // Xóa bộ phận
    public function destroy($id)
    {
        $bophan = BoPhan::find($id);

        if (!$bophan) {
            return response()->json(['message' => 'Bộ phận không tồn tại'], 404);
        }

        $bophan->delete();
        return response()->json(['message' => 'Bộ phận đã bị xóa']);
    }
}
