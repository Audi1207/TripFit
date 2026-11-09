<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    // Mengambil semua destinasi
    public function index()
    {
        $destinations = Destination::with(['city', 'categories'])
            ->orderBy('destination_name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data destinasi berhasil diambil',
            'data' => $destinations
        ], 200);
    }

    // Mengambil detail satu destinasi
    public function show($id)
    {
        $destination = Destination::with(['city', 'categories'])
            ->find($id);

        if (!$destination) {
            return response()->json([
                'success' => false,
                'message' => 'Destinasi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail destinasi berhasil diambil',
            'data' => $destination
        ], 200);
    }
}
