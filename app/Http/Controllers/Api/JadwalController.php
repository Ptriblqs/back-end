<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JadwalController extends Controller
{
    /**
     * Return jadwal bimbingan for authenticated user.
     * - Mahasiswa: return jadwal where mahasiswa_id matches their mahasiswa record
     * - Dosen: return jadwal where dosen_id matches their dosen record
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $role = $user->role ?? null;
            $result = [];
            $hasDosen = false;

            if ($role === 'mahasiswa') {
                $mahasiswa = DB::table('mahasiswa')->where('user_id', $user->id)->first();
                if (!$mahasiswa) {
                    return response()->json(['success' => true, 'message' => 'Belum ada data mahasiswa', 'data' => [], 'has_dosen' => false], 200);
                }

                // does mahasiswa have an accepted dosen?
                $hasDosen = DB::table('ajuan_dospem')
                    ->where('user_id', $user->id)
                    ->where('status', 'diterima')
                    ->exists();

                $rows = DB::table('bimbingan as j')
                    ->leftJoin('dosen as d', 'j.dosen_id', '=', 'd.id')
                    ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
                    ->where('j.mahasiswa_id', $mahasiswa->id)
                    ->select(
                        'j.id',
                        'j.judul as judul',
                        'j.tanggal',
                        'j.waktu',
                        'j.lokasi',
                        'j.status',
                        'j.pengaju',
                        'j.created_at',
                        'j.updated_at',
                        'u.nama_lengkap as dosen_nama',
                        'd.id as dosen_id'
                    )
                    ->orderBy('j.tanggal', 'asc')
                    ->get();

                foreach ($rows as $r) {
                    $result[] = [
                        'id' => $r->id,
                        'judul' => $r->judul,
                        'tanggal' => $r->tanggal,
                        'waktu' => $r->waktu,
                        'lokasi' => $r->lokasi,
                        'status' => $r->status,
                        'pengaju' => $r->pengaju,
                        'dosen_nama' => $r->dosen_nama,
                        'dosen_id' => $r->dosen_id,
                    ];
                }

                return response()->json(['success' => true, 'message' => 'Data jadwal mahasiswa', 'data' => $result, 'has_dosen' => $hasDosen], 200);
            }

            if ($role === 'dosen') {
                $dosen = DB::table('dosen')->where('user_id', $user->id)->first();
                if (!$dosen) {
                    return response()->json(['success' => false, 'message' => 'Anda belum terdaftar sebagai dosen', 'data' => []], 403);
                }

                $rows = DB::table('bimbingan as j')
                    ->leftJoin('mahasiswa as m', 'j.mahasiswa_id', '=', 'm.id')
                    ->leftJoin('users as u', 'm.user_id', '=', 'u.id')
                    ->where('j.dosen_id', $dosen->id)
                    ->select(
                        'j.id',
                        'j.judul as judul',
                        'j.tanggal',
                        'j.waktu',
                        'j.lokasi',
                        'j.status',
                        'j.created_at',
                        'j.updated_at',
                        'u.nama_lengkap as mahasiswa_nama',
                        'm.id as mahasiswa_id'
                    )
                    ->orderBy('j.tanggal', 'asc')
                    ->get();

                foreach ($rows as $r) {
                    $result[] = [
                        'id' => $r->id,
                        'judul' => $r->judul,
                        'tanggal' => $r->tanggal,
                        'waktu' => $r->waktu,
                        'lokasi' => $r->lokasi,
                        'status' => $r->status,
                        'mahasiswa_nama' => $r->mahasiswa_nama,
                        'mahasiswa_id' => $r->mahasiswa_id,
                    ];
                }

                return response()->json(['success' => true, 'message' => 'Data jadwal dosen', 'data' => $result], 200);
            }

            return response()->json(['success' => false, 'message' => 'Role tidak dikenali'], 403);
        } catch (\Exception $e) {
            Log::error('Error JadwalController@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
