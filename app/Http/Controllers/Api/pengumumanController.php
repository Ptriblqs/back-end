<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengumumanController extends Controller
{
    public function index()
    {
        try {
            $pengumuman = Pengumuman::with('user')
                ->orderBy('created_at', 'desc')
                ->get();

        $data = $pengumuman->map(function ($item) {

    $attachmentUrl = null;
    $attachmentName = null;

    if (!empty($item->attachment)) {

        // Coba 2 kemungkinan path
        $possiblePaths = [
            $item->attachment,
            'pengumuman/' . $item->attachment,
        ];

        foreach ($possiblePaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                $attachmentUrl = asset('storage/' . $path);
                $attachmentName = basename($path);
                break;
            }
        }
    }

    return [
        'id' => $item->id,
        'judul' => $item->judul,
        'isi' => $item->isi,
        'attachment' => $attachmentUrl,
        'attachment_name' => $attachmentName,
        'tgl_mulai' => $item->tgl_mulai,
        'tgl_selesai' => $item->tgl_selesai,
        'created_at' => $item->created_at->format('Y-m-d'),
    ];
});

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengumuman',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}