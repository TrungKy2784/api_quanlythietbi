<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Milon\Barcode\DNS1D;

class BarcodeController extends Controller
{
    public function barcodeImage($maTaiSanNhap)
{
    $barcode = new DNS1D();
    $barcode->setStorPath(storage_path('app/barcode_cache/'));

    // Tạo mã vạch PNG (width=3, height=70)
    $png = $barcode->getBarcodePNG($maTaiSanNhap, 'C128', 3, 70);

    $img = base64_decode($png);

    return Response::make($img, 200, [
        'Content-Type' => 'image/png',
        'Content-Disposition' => 'inline; filename="barcode.png"',
    ]);
}
}
