<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TugasAkhir;
use Illuminate\Http\Request;

class TugasAkhirController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        // pastikan user adalah mahasiswa
        if ($user->role !== 'mahasiswa') {
            return response()->json(['message' => 'Hanya mahasiswa yang dapat mengajukan bimbingan'], 403);
        }

        $mahasiswa = $user->mahasiswa;

        $request->validate([
            'id_dosen' => 'required|exists:dosen,id',
            'judul_tugas' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tenggat_waktu' => 'nullable|date',
        ]);

        $jumlahDisetujui = TugasAkhir::where('id_dosen', $request->id_dosen)
            ->where('status', 'disetujui')
            ->count();

        if ($jumlahDisetujui >= 10) {
            return response()->json([
                'message' => 'Dosen ini sudah memiliki 10 mahasiswa bimbingan yang disetujui. Tidak bisa menambah lagi.',
            ], 400);
        }

        $sudahAjukan = TugasAkhir::where('id_mahasiswa', $request->id_mahasiswa)
            ->where('id_dosen', $request->id_dosen)
            ->whereIn('status', ['pending', 'disetujui'])
            ->exists();

        if ($sudahAjukan) {
            return response()->json([
                'message' => 'Anda sudah memiliki pengajuan aktif ke dosen ini.',
            ], 400);
        }
        $tugasAkhir = TugasAkhir::create([
            'id_mahasiswa' => $mahasiswa->id,
            'id_dosen' => $request->id_dosen,
            'judul_tugas' => $request->judul_tugas,
            'deskripsi' => $request->deskripsi,
            'status' => 'menunggu',
            'tenggat_waktu' => $request->tenggat_waktu,
        ]);

        return response()->json([
            'message' => 'Pengajuan bimbingan berhasil dikirim',
            'data' => $tugasAkhir,
        ], 201);

    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // pastikan user adalah mahasiswa
        if ($user->role !== 'dosen') {
            return response()->json(['message' => 'Hanya dosen yang dapat melihat'], 403);
        }

        $dosen = $user->dosen;

        $tugasAkhir = TugasAkhir::with(['mahasiswa.user'])
        ->where('id_dosen', $dosen->id)
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'judul_tugas' => $item->judul_tugas,
                'id_dosen' => $item->id_dosen,
                'id_mahasiswa' => $item->id_mahasiswa,
                'nama_mahasiswa' => $item->mahasiswa->user->nama_lengkap,
            ];
        });
        // dd($tugasAkhir);

        return response()->json([
            'status' => 'success',
            'data' => $tugasAkhir
        ]);
    }

    // Dosen menyetujui atau menolak bimbingan
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:menunggu,setuju,tolak',
        ]);

        $tugasAkhir = TugasAkhir::findOrFail($id);

        $tugasAkhir->update([
            'status' => $request->status,
            'status_updated_at'=>now()
        ]);
        $tugasAkhir->status = $request->status;
        $tugasAkhir->save();

        return response()->json([
            'message' => 'Status bimbingan berhasil diperbarui',
            'data' => $tugasAkhir,
        ]);
    }

    // Mahasiswa melihat daftar pengajuan miliknya
    public function mahasiswaBimbingan(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'mahasiswa') {
            return response()->json(['message' => 'Hanya mahasiswa yang dapat melihat'], 403);
        }

        $mahasiswa = $user->mahasiswa;
        $tugasAkhir = TugasAkhir::where('id_mahasiswa', $mahasiswa->id)->first();

        return response()->json([
            "message"=> "berhasil",
            "data"=> $tugasAkhir
        ]);
    }
}
