<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * GET semua notifikasi user yang sedang login
     */
    public function index()
    {
        $notifikasi = Notifikasi::where('id_user', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'jenis' => $item->jenis,
                    'pesan' => $item->pesan,
                    'waktu' => $item->created_at->diffForHumans(),
                    'is_read' => $item->is_read,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifikasi
        ], 200);
    }

    /**
     * DELETE notifikasi tertentu
     */
    public function destroy($id)
    {
        $notifikasi = Notifikasi::where('id_user', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        $notifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dihapus'
        ], 200);
    }

    /**
     * DELETE semua notifikasi user
     */
    public function destroyAll()
    {
        $count = Notifikasi::where('id_user', Auth::id())->count();

        if ($count === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada notifikasi untuk dihapus'
            ], 404);
        }

        Notifikasi::where('id_user', Auth::id())->delete();

        return response()->json([
            'success' => true,
            'message' => "Berhasil menghapus $count notifikasi"
        ], 200);
    }

    /**
     * POST Mark as read
     */
    public function markAsRead($id)
    {
        $notifikasi = Notifikasi::where('id_user', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        $notifikasi->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca'
        ], 200);
    }

    /**
     * POST Mark all as read
     */
    public function markAllAsRead()
    {
        Notifikasi::where('id_user', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca'
        ], 200);
    }

    /**
     * ✅ HELPER: Kirim notifikasi ke dosen saat ada ajuan baru
     */
    public static function sendNotifikasiAjuanBaru($dosenUserId, $namaMahasiswa)
    {
        Notifikasi::create([
            'id_user' => $dosenUserId,
            'jenis' => 'ajuan',
            'pesan' => "Mahasiswa $namaMahasiswa mengajukan diri sebagai bimbingan Anda. Silakan tinjau pengajuan di menu Ajuan.",
            'is_read' => false,
        ]);
    }

    /**
     * ✅ HELPER: Kirim notifikasi ke mahasiswa saat ajuan diterima
     */
    public static function sendNotifikasiAjuanDiterima($mahasiswaUserId, $namaDosen)
    {
        Notifikasi::create([
            'id_user' => $mahasiswaUserId,
            'jenis' => 'diterima',
            'pesan' => "Selamat! Dosen $namaDosen telah menerima ajuan Anda sebagai dosen pembimbing.",
            'is_read' => false,
        ]);
    }

    /**
     * ✅ HELPER: Kirim notifikasi ke mahasiswa saat ajuan ditolak
     */
    public static function sendNotifikasiAjuanDitolak($mahasiswaUserId, $namaDosen, $alasan)
    {
        Notifikasi::create([
            'id_user' => $mahasiswaUserId,
            'jenis' => 'ditolak',
            'pesan' => "Ajuan pembimbing Anda ditolak oleh $namaDosen. Alasan: $alasan",
            'is_read' => false,
        ]);
    }
}