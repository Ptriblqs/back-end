<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\AjuanDospem;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BimbinganController extends Controller
{
    protected $notifikasiService;

    public function __construct(NotifikasiService $notifikasiService)
    {
        $this->notifikasiService = $notifikasiService;
    }

    /**
     * GET /api/bimbingan/kalender
     * Return list of accepted jadwal dates for mahasiswa or dosen
     */
    public function kalenderMahasiswa()
    {
        $user = Auth::user();
        $role = strtolower(trim($user->role));

        if ($role === 'mahasiswa') {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data mahasiswa tidak ditemukan'
                ], 404);
            }

            $bimbingans = Bimbingan::where('mahasiswa_id', $mahasiswa->id)
                ->where('status', 'disetujui')
                ->get();
        } elseif ($role === 'dosen') {
            $dosen = Dosen::where('user_id', $user->id)->first();
            if (!$dosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dosen tidak ditemukan'
                ], 404);
            }

            $bimbingans = Bimbingan::where('dosen_id', $dosen->id)
                ->where('status', 'disetujui')
                ->get();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak dikenali'
            ], 403);
        }

        $dates = $bimbingans->map(fn($b) => ['date' => $b->tanggal])->unique()->values();

        return response()->json([
            'success' => true,
            'data' => $dates
        ]);
    }

    /**
     * GET /api/bimbingan
     */
    public function index()
    {
        $user = Auth::user();
        $role = strtolower(trim($user->role));

        return match ($role) {
            'mahasiswa' => $this->indexMahasiswa(),
            'dosen'     => $this->indexDosen(),
            default     => response()->json([
                'success' => false,
                'message' => 'Role tidak dikenali'
            ], 403),
        };
    }

    /**
     * GET jadwal mahasiswa
     */
    private function indexMahasiswa()
    {
        $user = Auth::user();

        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        if (!$mahasiswa) {
            return response()->json([
                'success' => false,
                'message' => 'Data mahasiswa tidak ditemukan'
            ], 404);
        }

        $bimbingans = Bimbingan::with('dosen.user')
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereIn('status', ['menunggu', 'ajuan_dosen'])
            ->latest()
            ->get()
            ->map(fn ($b) => [
                'jadwalId' => $b->id,
                'judul'   => $b->judul,
                'status'  => $this->getStatusLabel($b->status),
                'dosen'   => $b->dosen?->user?->nama_lengkap ?? '-',
                'tanggal'=> $b->tanggal,
                'waktu'  => $b->waktu,
                'lokasi' => $b->lokasi,
                'jenis_bimbingan' => $b->jenis_bimbingan,
                'catatan'=> $b->catatan,
                'alasan_penolakan' => $b->alasan_penolakan,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $bimbingans
        ]);
    }

    /**
     * GET jadwal dosen
     */
    private function indexDosen()
    {
        $user = Auth::user();

        $dosen = Dosen::where('user_id', $user->id)->first();
        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Data dosen tidak ditemukan'
            ], 404);
        }

        $bimbingans = Bimbingan::with(['mahasiswa.user'])
            ->where('dosen_id', $dosen->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($b) => [
                'jadwalId' => $b->id,
                'judul'   => $b->judul,
                'status'  => $this->getStatusLabel($b->status),
                'mahasiswa' => $b->mahasiswa?->user?->nama_lengkap ?? '-',
                'nim'     => $b->mahasiswa->nim ?? '-',
                'tanggal' => $b->tanggal,
                'waktu'   => $b->waktu,
                'lokasi'  => $b->lokasi,
                'jenis_bimbingan' => $b->jenis_bimbingan,
                'pengaju' => $b->pengaju,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $bimbingans
        ]);
    }

    /**
     * POST mahasiswa ajukan jadwal
     */
    public function storeMahasiswa(Request $request)
    {
        Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'waktu' => 'required',
            'lokasi' => 'required|string',
            'jenis_bimbingan' => 'required|in:online,offline',
            'catatan' => 'nullable|string',
        ])->validate();

        $user = Auth::user();

        // 1️⃣ Mahasiswa dari user login
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        if (!$mahasiswa) {
            return response()->json([
                'success' => false,
                'message' => 'Mahasiswa tidak ditemukan'
            ], 404);
        }

        // 2️⃣ Dosen dari ajuan dospem yang DITERIMA
        $ajuan = AjuanDospem::where('user_id', $user->id)
            ->where('status', 'diterima')
            ->latest()
            ->first();

        if (!$ajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki dosen pembimbing'
            ], 400);
        }

        // 3️⃣ Simpan bimbingan
        $bimbingan = Bimbingan::create([
            'mahasiswa_id' => $mahasiswa->id,
            'dosen_id'     => $ajuan->dosen_id,
            'judul'        => $request->judul,
            'tanggal'      => $request->tanggal,
            'waktu'        => $request->waktu,
            'lokasi'       => $request->lokasi,
            'jenis_bimbingan' => $request->jenis_bimbingan,
            'catatan'      => $request->catatan,
            'status'       => 'menunggu',
            'pengaju'      => 'mahasiswa',
        ]);

        // 4️⃣ Notifikasi ke dosen
        $dosen = Dosen::with('user')->find($ajuan->dosen_id);
        if ($dosen?->user_id) {
            $this->notifikasiService->kirimNotifikasi(
                $dosen->user_id,
                'ajuan',
                "Mahasiswa {$user->nama_lengkap} mengajukan jadwal bimbingan"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil diajukan',
            'data'    => $bimbingan
        ], 201);
    }

    /**
     * PUT dosen terima jadwal
     */
    public function terimaDosen($id)
    {
        $user = Auth::user();

        $dosen = Dosen::where('user_id', $user->id)->first();
        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Dosen tidak ditemukan'
            ], 404);
        }

        $bimbingan = Bimbingan::where('id', $id)
            ->where('dosen_id', $dosen->id)
            ->first();

        if (!$bimbingan) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        $bimbingan->update([
            'status' => 'disetujui',
            'alasan_penolakan' => null
        ]);

        // Kirim notifikasi ke mahasiswa bahwa jadwal disetujui
        try {
            $mahasiswaUserId = $bimbingan->mahasiswa?->user_id;
            $namaDosen = $dosen->user?->nama_lengkap ?? 'Dosen';
            $pesan = "Jadwal bimbingan Anda ('{$bimbingan->judul}') telah disetujui oleh {$namaDosen}.";
            if ($mahasiswaUserId) {
                $this->notifikasiService->kirimNotifikasi($mahasiswaUserId, 'bimbingan_disetujui', $pesan);
            }
        } catch (\Exception $e) {
            // non-blocking
        }
        return response()->json([
            'success' => true,
            'message' => 'Jadwal disetujui'
        ]);
    }

    /**
     * PUT dosen tolak jadwal
     */
    public function tolakDosen(Request $request, $id)
    {
        $request->validate([
            'alasan_penolakan' => 'required|string'
        ]);

        $user = Auth::user();
        $dosen = Dosen::where('user_id', $user->id)->first();

        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Dosen tidak ditemukan'
            ], 404);
        }

        $bimbingan = Bimbingan::where('id', $id)
            ->where('dosen_id', $dosen->id)
            ->first();

        if (!$bimbingan) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        $bimbingan->update([
            'status' => 'ditolak',
            'alasan_penolakan' => $request->alasan_penolakan
        ]);

        // Kirim notifikasi ke mahasiswa bahwa jadwal ditolak
        try {
            $mahasiswaUserId = $bimbingan->mahasiswa?->user_id;
            $namaDosen = $dosen->user?->nama_lengkap ?? 'Dosen';
            $pesan = "Jadwal bimbingan Anda ('{$bimbingan->judul}') ditolak oleh {$namaDosen}. Alasan: {$request->alasan_penolakan}";
            if ($mahasiswaUserId) {
                $this->notifikasiService->kirimNotifikasi($mahasiswaUserId, 'bimbingan_ditolak', $pesan);
            }
        } catch (\Exception $e) {
        }
        return response()->json([
            'success' => true,
            'message' => 'Jadwal ditolak'
        ]);
    }

    /**
     * PUT mahasiswa terima jadwal
     */
    public function terimaMahasiswa($id)
    {
        $user = Auth::user();

        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        if (!$mahasiswa) {
            return response()->json([
                'success' => false,
                'message' => 'Mahasiswa tidak ditemukan'
            ], 404);
        }

        $bimbingan = Bimbingan::where('id', $id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        if (!$bimbingan) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        $bimbingan->update([
            'status' => 'disetujui',
            'alasan_penolakan' => null
        ]);

        // Kirim notifikasi ke dosen bahwa jadwal disetujui oleh mahasiswa
        try {
            $dosenUserId = $bimbingan->dosen?->user_id;
            $namaMahasiswa = $mahasiswa->user?->nama_lengkap ?? 'Mahasiswa';
            $pesan = "Jadwal bimbingan ('{$bimbingan->judul}') telah disetujui oleh {$namaMahasiswa}.";
            if ($dosenUserId) {
                $this->notifikasiService->kirimNotifikasi($dosenUserId, 'bimbingan_disetujui', $pesan);
            }
        } catch (\Exception $e) {
            // non-blocking
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal disetujui'
        ]);
    }

    /**
     * PUT mahasiswa tolak jadwal
     */
    public function tolakMahasiswa(Request $request, $id)
    {
        $request->validate([
            'alasan_penolakan' => 'required|string'
        ]);

        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json([
                'success' => false,
                'message' => 'Mahasiswa tidak ditemukan'
            ], 404);
        }

        $bimbingan = Bimbingan::where('id', $id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        if (!$bimbingan) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        $bimbingan->update([
            'status' => 'ditolak',
            'alasan_penolakan' => $request->alasan_penolakan
        ]);

        // Kirim notifikasi ke dosen bahwa jadwal ditolak oleh mahasiswa
        try {
            $dosenUserId = $bimbingan->dosen?->user_id;
            $namaMahasiswa = $mahasiswa->user?->nama_lengkap ?? 'Mahasiswa';
            $pesan = "Jadwal bimbingan ('{$bimbingan->judul}') ditolak oleh {$namaMahasiswa}. Alasan: {$request->alasan_penolakan}";
            if ($dosenUserId) {
                $this->notifikasiService->kirimNotifikasi($dosenUserId, 'bimbingan_ditolak', $pesan);
            }
        } catch (\Exception $e) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal ditolak'
        ]);
    }

    /**
     * Helper status
     */
    private function getStatusLabel($status)
    {
        return match ($status) {
            'menunggu'     => 'Menunggu',
            'disetujui'    => 'Disetujui',
            'ditolak'      => 'Ditolak',
            'ajuan_dosen'  => 'Ajuan Dosen',
            default        => $status
        };
    }
}
