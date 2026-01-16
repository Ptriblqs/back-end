<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
                    // ✅ AMBIL attachment_name DARI DATABASE (PRIORITAS UTAMA)
                    $attachmentName = $item->attachment_name ?? basename($item->attachment);
                    
                    $possiblePaths = [
                        $item->attachment,
                        'lampiran/' . $item->attachment,
                    ];

                    // ✅ CEK FILE DI STORAGE
                    foreach ($possiblePaths as $path) {
                        if (Storage::disk('public')->exists($path)) {
                            $attachmentUrl = asset('storage/' . $path);
                            Log::info("File found at: $path");
                            break;
                        }
                    }
                    
                    // ✅ JIKA FILE TIDAK DITEMUKAN, TETAP BUILD URL
                    if ($attachmentUrl === null) {
                        // Coba build URL langsung dari database path
                        $attachmentUrl = asset('storage/' . $item->attachment);
                        Log::warning("File not found in storage, using direct path: {$item->attachment}");
                    }
                }

                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'isi' => $item->isi,
                    'attachment' => $attachmentUrl, // ← TETAP KIRIM MESKIPUN NULL
                    'attachment_name' => $attachmentName, // ← TETAP KIRIM
                    'tgl_mulai' => $item->tgl_mulai,
                    'tgl_selesai' => $item->tgl_selesai,
                    'created_at' => $item->created_at->format('Y-m-d'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in PengumumanController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengumuman',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}