<?php

namespace App\Http\Controllers;

use App\Models\PhanQuyen;
use Illuminate\Http\Request;

class PhanQuyenController extends Controller
{
    // Hiển thị danh sách phân quyền
    public function index()
    {
        $roles = PhanQuyen::all(); // Lấy tất cả các phân quyền
        return response()->json($roles); // Trả về danh sách phân quyền dưới dạng JSON
    }


    // Hiển thị form tạo mới phân quyền (dành cho frontend)
    public function create()
    {
        // Không cần thiết trong API, chỉ cần gửi response nếu có giao diện frontend
    }

    // Lưu phân quyền mới
    public function store(Request $request)
    {
        $request->validate([
            'TenQuyen' => 'required|string|max:255',
        ]);

        $role = new PhanQuyen();
        $role->TenQuyen = $request->TenQuyen;
        $role->save();

        return response()->json(['message' => 'Tạo quyền thành công', 'role' => $role], 201);
    }



    // Hiển thị chi tiết phân quyền
    public function show($id)
    {
        $role = PhanQuyen::findOrFail($id); // Tìm phân quyền theo ID, nếu không có sẽ trả lỗi 404
        return response()->json($role); // Trả về phân quyền dưới dạng JSON
    }


    // Hiển thị form chỉnh sửa phân quyền (dành cho frontend)
    public function edit($id)
    {
        // Không cần thiết trong API, chỉ cần gửi response nếu có giao diện frontend
    }

    // Cập nhật thông tin phân quyền
    public function update(Request $request, $id)
    {
        $request->validate([
            'TenQuyen' => 'required|string|max:255',
        ]);

        $role = PhanQuyen::findOrFail($id);
        $role->TenQuyen = $request->TenQuyen;
        $role->save();

        return response()->json(['message' => 'Cập nhật thành công', 'role' => $role]);
    }

    // Xóa phân quyền
    public function destroy($id)
    {
        $role = PhanQuyen::findOrFail($id); // Tìm phân quyền theo ID
        $role->delete(); // Xóa phân quyền

        return response()->json(null, 204); // Trả về mã HTTP 204 (no content) sau khi xóa
    }

}
