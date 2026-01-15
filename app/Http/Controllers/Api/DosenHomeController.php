<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Dosen;
use App\Models\AjuanDospem;
use App\Models\Dokumen;
use App\Models\Bimbingan;

class DosenHomeController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if (! $user || ($user->role ?? '') !== 'dosen') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya dosen yang dapat mengakses endpoint ini.'
                ], 403);
            }

            $dosen = Dosen::where('user_id', $user->id)->first();
            if (! $dosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dosen tidak ditemukan'
                ], 404);
            }

            // Jumlah mahasiswa yang disetujui sebagai bimbingan (distinct mahasiswa/user)
            $mahasiswaCount = AjuanDospem::where('dosen_id', $dosen->id)
                ->whereRaw("LOWER(status) = ?", ['diterima'])
                ->distinct()
                ->count('user_id');

            // Jumlah dokumen dengan status menunggu untuk dosen ini
            $dokumenMenunggu = Dokumen::where('dosen_id', $user->id)
                ->whereRaw("LOWER(status) = ?", ['menunggu'])
                ->count();

            // Jumlah bimbingan yang diajukan oleh mahasiswa dan disetujui
            $bimbinganDisetujui = Bimbingan::where('dosen_id', $dosen->id)
                ->where('pengaju', 'mahasiswa')
                ->whereRaw("LOWER(status) = ?", ['disetujui'])
                ->count();

            // Daftar bimbingan disetujui (terbaru, limit 10) untuk card di home dosen
            $bimbinganDisetujuiList = Bimbingan::with(['mahasiswa.user'])
                ->where('dosen_id', $dosen->id)
                ->where('pengaju', 'mahasiswa')
                ->whereRaw("LOWER(status) = ?", ['disetujui'])
                ->orderBy('tanggal', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'judul' => $b->judul,
                        'mahasiswa' => $b->mahasiswa?->user?->nama_lengkap ?? '-',
                        'nim' => $b->mahasiswa->nim ?? '-',
                        'tanggal' => $b->tanggal?->format('d-m-Y'),
                        'waktu' => $b->waktu,
                        'lokasi' => $b->lokasi,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'mahasiswa_dibimbing' => $mahasiswaCount,
                    'dokumen_menunggu' => $dokumenMenunggu,
                    'bimbingan_disetujui' => $bimbinganDisetujui,
                    'bimbingan_disetujui_list' => $bimbinganDisetujuiList,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('DosenHomeController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data dashboard dosen'
            ], 500);
        }
    }
}
