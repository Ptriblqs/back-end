<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AjuanDospem;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class AjuanDospemController extends Controller
{
    /**
     * ===============================
     * DEBUG ENDPOINT
     * ===============================
     */
    public function debugDosenStatus()
    {
        try {
            $user = Auth::user();
            
            $dosenRaw = DB::table('dosen')
                ->where('user_id', $user->id)
                ->first();
            
            $allAjuan = DB::table('ajuan_dospem')
                ->select('id', 'user_id', 'dosen_id', 'status')
                ->get();
            
            $allDosen = DB::table('dosen')
                ->select('id', 'user_id')
                ->get();
            
            // Query dengan JOIN untuk debug
            $ajuanWithUsers = [];
            if ($dosenRaw) {
                $ajuanWithUsers = DB::table('ajuan_dospem as a')
                    ->join('users as u', 'a.user_id', '=', 'u.id')
                    ->where('a.dosen_id', $dosenRaw->id)
                    ->select(
                        'a.id',
                        'a.user_id',
                        'a.status',
                        'u.nama_lengkap',
                        'u.email',
                        'u.role'
                    )
                    ->get();
            }
            
            return response()->json([
                'debug_info' => [
                    'current_user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                    'dosen_record' => $dosenRaw,
                    'all_dosen_count' => count($allDosen),
                    'all_dosen' => $allDosen,
                    'all_ajuan_count' => count($allAjuan),
                    'all_ajuan' => $allAjuan,
                    'ajuan_with_users' => $ajuanWithUsers,
                    'ajuan_for_this_dosen' => $dosenRaw ? 
                        $allAjuan->where('dosen_id', $dosenRaw->id)->values() : 
                        [],
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * ===============================
     * GET Ajuan Masuk (DOSEN) - USING RAW JOIN
     * ===============================
     */
    public function dosenIndex()
    {
        try {
            $user = Auth::user();
            
            Log::info('🔍 dosenIndex - User ID: ' . $user->id);
            
            // Cek dosen di database
            $dosen = DB::table('dosen')
                ->where('user_id', $user->id)
                ->first();
            
            if (!$dosen) {
                Log::warning('❌ User tidak terdaftar sebagai dosen');
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum terdaftar sebagai dosen',
                    'data' => [],
                ], 403);
            }
            
            Log::info('✅ Dosen ID: ' . $dosen->id);
            
            // Query dengan JOIN langsung
            $ajuan = DB::table('ajuan_dospem as a')
                ->join('users as u', 'a.user_id', '=', 'u.id')  // UBAH leftJoin jadi join
                ->leftJoin('mahasiswa as m', 'a.user_id', '=', 'm.user_id')
                ->leftJoin('program_studis as ps', 'a.program_studis_id', '=', 'ps.id')
                ->where('a.dosen_id', $dosen->id)
                ->select(
                    'a.id',
                    'a.nim as nim_encrypted',
                    'a.judul_ta',
                    'a.deskripsi_ta',
                    'a.alasan',
                    'a.status',
                    'a.portofolio',
                    'a.catatan_dosen',
                    'a.created_at',
                    'a.reviewed_at',
                    'u.nama_lengkap as nama_mahasiswa',
                    'u.email as email_mahasiswa',
                    'u.id as user_id',
                    'ps.nama_prodi as program_studi'
                )
                ->orderBy('a.created_at', 'desc')
                ->get();
            
            Log::info('📊 Total ajuan: ' . $ajuan->count());
            
            // Debug: Cek data mentah
            if ($ajuan->isNotEmpty()) {
                Log::info('📋 Sample data: ' . json_encode($ajuan->first()));
            }
            
            if ($ajuan->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Belum ada ajuan masuk',
                    'data' => [],
                ], 200);
            }
            
            // Format data dengan PROPER DECRYPTION
            $result = [];
            foreach ($ajuan as $item) {
                // Decrypt NIM dari ajuan_dospem
                $nim = $item->nim_encrypted;
                try {
                    if ($nim && strlen($nim) > 20) {
                        $nim = Crypt::decryptString($nim);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to decrypt NIM: ' . $e->getMessage());
                    $nim = '[encrypted]';
                }
                
                // Decrypt NAMA LENGKAP dari users (INI YANG PENTING!)
                $namaMahasiswa = $item->nama_mahasiswa;
                try {
                    // Cek apakah nama terenkripsi (biasanya panjang > 50 karakter)
                    if ($namaMahasiswa && strlen($namaMahasiswa) > 50) {
                        $namaMahasiswa = Crypt::decryptString($namaMahasiswa);
                        Log::info("✅ Decrypted nama for ajuan {$item->id}: {$namaMahasiswa}");
                    } else {
                        Log::info("ℹ️ Nama tidak terenkripsi untuk ajuan {$item->id}: {$namaMahasiswa}");
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Failed to decrypt nama for ajuan {$item->id}: " . $e->getMessage());
                    $namaMahasiswa = 'Tidak Diketahui';
                }
                
                // Decrypt EMAIL jika terenkripsi
                $email = $item->email_mahasiswa;
                try {
                    if ($email && strlen($email) > 50) {
                        $email = Crypt::decryptString($email);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to decrypt email: ' . $e->getMessage());
                    $email = '-';
                }
                
                Log::info("📋 Ajuan {$item->id}: nama={$namaMahasiswa}, nim={$nim}, email={$email}");
                
                $result[] = [
                    'id' => $item->id,
                    'nim' => $nim ?: '-',
                    'nama_mahasiswa' => $namaMahasiswa ?: 'Tidak Diketahui',
                    'email_mahasiswa' => $email ?: '-',
                    'program_studi' => $item->program_studi ?: '-',
                    'judul_ta' => $item->judul_ta,
                    'deskripsi_ta' => $item->deskripsi_ta,
                    'alasan' => $item->alasan,
                    'portofolio' => $item->portofolio,
                    'portofolio_name' => basename($item->portofolio),
                    'portofolio_url' => asset('storage/' . $item->portofolio),
                    'status' => strtolower($item->status),
                    'catatan_dosen' => $item->catatan_dosen,
                    'created_at' => date('d M Y H:i', strtotime($item->created_at)),
                    'reviewed_at' => $item->reviewed_at ? 
                        date('d M Y H:i', strtotime($item->reviewed_at)) : null,
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Data ajuan berhasil diambil',
                'data' => $result,
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('❌ Exception: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * ===============================
     * GET List Ajuan (Mahasiswa)
     * ===============================
     */
    public function index()
    {
        $ajuan = AjuanDospem::where('user_id', Auth::id())
            ->with([
                'dosen.user:id,nama_lengkap',
                'programStudi:id,nama_prodi',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'judul_ta' => $item->judul_ta,
                    'deskripsi_ta' => $item->deskripsi_ta,
                    'alasan' => $item->alasan,
                    'status' => strtolower($item->status),
                    'portofolio_name' => basename($item->portofolio),
                    'portofolio_url'  => asset('storage/' . $item->portofolio),
                    'created_at' => $item->created_at,
                    'dosen' => $item->dosen,
                    'program_studi' => $item->programStudi,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan berhasil diambil',
            'data' => $ajuan
        ], 200);
    }

    /**
     * ===============================
     * GET Detail Ajuan - USING JOIN
     * ===============================
     */
    public function show($id)
    {
        try {
            // Query dengan JOIN
            $ajuan = DB::table('ajuan_dospem as a')
                ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
                ->leftJoin('mahasiswa as m', 'a.user_id', '=', 'm.user_id')
                ->leftJoin('program_studis as ps', 'a.program_studis_id', '=', 'ps.id')
                ->leftJoin('dosen as d', 'a.dosen_id', '=', 'd.id')
                ->where('a.id', $id)
                ->select(
                    'a.*',
                    'u.nama_lengkap as nama_mahasiswa',
                    'u.email as email_mahasiswa',
                    'm.nim as nim_mahasiswa',
                    'ps.nama_prodi as program_studi',
                    'd.id as dosen_id_check'
                )
                ->first();

            if (!$ajuan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data ajuan tidak ditemukan'
                ], 404);
            }

            // Mahasiswa hanya boleh lihat ajuan miliknya
            if (Auth::user()->role === 'mahasiswa' && $ajuan->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Dosen hanya boleh lihat ajuan miliknya
            if (Auth::user()->role === 'dosen') {
                $dosen = Auth::user()->dosen;
                if (!$dosen || $ajuan->dosen_id !== $dosen->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized'
                    ], 403);
                }
            }

            // Decrypt NIM
            $nim = $ajuan->nim;
            try {
                if ($nim && strlen($nim) > 20) {
                    $nim = Crypt::decryptString($nim);
                }
            } catch (\Exception $e) {
                $nim = $ajuan->nim_mahasiswa ?? '[encrypted]';
            }

            // Decrypt NAMA LENGKAP
            $namaMahasiswa = $ajuan->nama_mahasiswa;
            try {
                if ($namaMahasiswa && strlen($namaMahasiswa) > 50) {
                    $namaMahasiswa = Crypt::decryptString($namaMahasiswa);
                }
            } catch (\Exception $e) {
                $namaMahasiswa = 'Tidak Diketahui';
            }

            // Decrypt EMAIL
            $emailMahasiswa = $ajuan->email_mahasiswa;
            try {
                if ($emailMahasiswa && strlen($emailMahasiswa) > 50) {
                    $emailMahasiswa = Crypt::decryptString($emailMahasiswa);
                }
            } catch (\Exception $e) {
                $emailMahasiswa = '-';
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail pengajuan berhasil diambil',
                'data' => [
                    'id' => $ajuan->id,
                    'nim' => $nim,
                    'judul_ta' => $ajuan->judul_ta,
                    'deskripsi_ta' => $ajuan->deskripsi_ta,
                    'alasan' => $ajuan->alasan,
                    'status' => strtolower($ajuan->status),
                    'portofolio' => $ajuan->portofolio,
                    'portofolio_name' => basename($ajuan->portofolio),
                    'portofolio_url' => asset('storage/' . $ajuan->portofolio),
                    'catatan_dosen' => $ajuan->catatan_dosen,
                    'mahasiswa' => [
                        'nama_lengkap' => $namaMahasiswa,
                        'email' => $emailMahasiswa,
                        'nim' => $nim,
                    ],
                    'program_studi' => $ajuan->program_studi ?? '-',
                    'created_at' => $ajuan->created_at,
                    'reviewed_at' => $ajuan->reviewed_at,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error in show: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ===============================
     * POST Create Ajuan (Mahasiswa)
     * ===============================
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'program_studis_id' => 'required|exists:program_studis,id',
            'id_mahasiswa'      => 'required|exists:mahasiswa,id',
            'id_dosen'          => 'required|exists:dosen,id',
            'judul_ta'          => 'required|string|max:255',
            'deskripsi_ta'      => 'required|string',
            'alasan'            => 'required|string',
            'portofolio'        => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $mahasiswa = Mahasiswa::find($request->id_mahasiswa);

        if (!$mahasiswa) {
            return response()->json([
                'success' => false,
                'message' => 'Mahasiswa tidak ditemukan'
            ], 404);
        }

        // Cegah ajuan ganda
        $existing = AjuanDospem::where('user_id', $mahasiswa->user_id)
            ->whereIn('status', ['menunggu', 'diterima'])
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki pengajuan aktif'
            ], 400);
        }

        $dosen = Dosen::with('user')->find($request->id_dosen);

         if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Dosen tidak ditemukan'
            ], 404);
        }

        /**
         * ===============================
         * UPLOAD FILE (FIX UTAMA)
         * ===============================
         */
        if (!$request->hasFile('portofolio')) {
            return response()->json([
                'success' => false,
                'message' => 'File portofolio tidak ditemukan'
            ], 400);
        }

        $file = $request->file('portofolio');

        $fileName = 'portofolio_' . time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

        // 🔥 SIMPAN KE storage/app/public/portofolio
        $path = $file->storeAs('portofolio', $fileName, 'public');

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan file'
            ], 500);
        }

        /**
         * ===============================
         * SIMPAN KE DATABASE
         * ===============================
         */
        $ajuan = AjuanDospem::create([
            'user_id'           => $mahasiswa->user_id,
            'nim'               => $mahasiswa->nim,
            'program_studis_id' => $request->program_studis_id,
            'dosen_id'          => $dosen->id,
            'dosen_nik'         => $dosen->nik,
            'dosen_nama'        => $dosen->user->nama_lengkap ?? '-',
            'judul_ta'          => $request->judul_ta,
            'deskripsi_ta'      => $request->deskripsi_ta,
            'alasan'            => $request->alasan,
            'portofolio'        => $path, // ← PATH RELATIF (BENAR)
            'status'            => 'menunggu',
        ]);

        // Kirim notifikasi ke dosen terkait bahwa ada ajuan baru
        try {
            $namaMahasiswa = $mahasiswa->user?->nama_lengkap ?? null;
            if ($namaMahasiswa && strlen($namaMahasiswa) > 50) {
                // jika terenkripsi, coba decrypt
                try {
                    $namaMahasiswa = Crypt::decryptString($namaMahasiswa);
                } catch (\Exception $e) {
                    // biarkan apa adanya
                }
            }
            \App\Http\Controllers\Api\NotifikasiController::sendNotifikasiAjuanBaru(
                $dosen->user->id,
                $namaMahasiswa ?? 'Mahasiswa'
            );
        } catch (\Exception $e) {
            // jangan ganggu alur utama jika notifikasi gagal
            Log::warning('Gagal mengirim notifikasi ajuan: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim',
            'data' => [
                'id' => $ajuan->id,
                'status' => $ajuan->status,
                'portofolio_name' => basename($path),
                'portofolio_url'  => asset('storage/' . $path),
            ]
        ], 201);
    }

    /**
     * ===============================
     * DOWNLOAD PORTOFOLIO (DOSEN)
     * ===============================
     */
    public function downloadPortofolio($id)
    {
        $ajuan = AjuanDospem::findOrFail($id);

        if (!$ajuan->portofolio || !Storage::disk('public')->exists($ajuan->portofolio)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return Storage::disk('public')->download($ajuan->portofolio);
    }
    
    /**
     * ===============================
     * POST Approve (DOSEN)
     * ===============================
     */
    public function approve(Request $request, $id)
    {
        $dosen = Auth::user()->dosen;

        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $ajuan = AjuanDospem::where('id', $id)
            ->where('dosen_id', $dosen->id)
            ->first();

        if (!$ajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Ajuan tidak ditemukan'
            ], 404);
        }

        if (strtolower($ajuan->status) !== 'menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Ajuan sudah diproses'
            ], 400);
        }

        $ajuan->update([
            'status' => 'diterima',
            'catatan_dosen' => $request->catatan_dosen,
            'reviewed_at' => now()
        ]);

        // Kirim notifikasi ke mahasiswa bahwa ajuan diterima
        try {
            $namaDosen = $dosen->user?->nama_lengkap ?? 'Dosen';
            if ($namaDosen && strlen($namaDosen) > 50) {
                try {
                    $namaDosen = Crypt::decryptString($namaDosen);
                } catch (\Exception $e) {
                }
            }
            \App\Http\Controllers\Api\NotifikasiController::sendNotifikasiAjuanDiterima(
                $ajuan->user_id,
                $namaDosen
            );
        } catch (\Exception $e) {
            Log::warning('Gagal mengirim notifikasi ajuan diterima: ' . $e->getMessage());
        }
        return response()->json([
            'success' => true,
            'message' => 'Ajuan berhasil diterima'
        ], 200);
    }

    /**
     * ===============================
     * POST Reject (DOSEN)
     * ===============================
     */
    public function reject(Request $request, $id)
    {
        $dosen = Auth::user()->dosen;

        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'catatan_dosen' => 'required|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $ajuan = AjuanDospem::where('id', $id)
            ->where('dosen_id', $dosen->id)
            ->first();

        if (!$ajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Ajuan tidak ditemukan'
            ], 404);
        }

        $ajuan->update([
            'status' => 'ditolak',
            'catatan_dosen' => $request->catatan_dosen,
            'reviewed_at' => now()
        ]);

        // Kirim notifikasi ke mahasiswa bahwa ajuan ditolak
        try {
            $namaDosen = $dosen->user?->nama_lengkap ?? 'Dosen';
            if ($namaDosen && strlen($namaDosen) > 50) {
                try {
                    $namaDosen = Crypt::decryptString($namaDosen);
                } catch (\Exception $e) {
                }
            }
            \App\Http\Controllers\Api\NotifikasiController::sendNotifikasiAjuanDitolak(
                $ajuan->user_id,
                $namaDosen,
                $request->catatan_dosen
            );
        } catch (\Exception $e) {
            Log::warning('Gagal mengirim notifikasi ajuan ditolak: ' . $e->getMessage());
        }
        return response()->json([
            'success' => true,
            'message' => 'Ajuan berhasil ditolak'
        ], 200);
    }

    /**
 * ===============================
 * GET Daftar Dosen by Prodi (DENGAN KUOTA & BIDANG KEAHLIAN)
 * ===============================
 */
public function getDosenByProdi()
{
    try {
        // Ambil dosen dengan hitung jumlah mahasiswa bimbingan yang diterima
        $dosenList = Dosen::withCount([
                'ajuanDospem as jumlah_bimbingan' => function ($query) {
                    $query->where('status', 'diterima'); // Hanya hitung yang diterima
                }
            ])
            ->with(['user:id,nama_lengkap,email,foto_profil', 'prodi:id,nama_prodi'])
            ->get();

        $data = $dosenList->map(function ($dosen) {
            // Decrypt nama lengkap jika terenkripsi
            $namaLengkap = $dosen->user->nama_lengkap ?? 'Tanpa Nama';
            try {
                if (strlen($namaLengkap) > 50) {
                    $namaLengkap = Crypt::decryptString($namaLengkap);
                }
            } catch (\Exception $e) {
                // Jika gagal decrypt, pakai apa adanya
            }

            // Decrypt email jika terenkripsi
            $email = $dosen->user->email ?? '-';
            try {
                if ($email && strlen($email) > 50) {
                    $email = Crypt::decryptString($email);
                }
            } catch (\Exception $e) {
                $email = '-';
            }

            $jumlahBimbingan = $dosen->jumlah_bimbingan ?? 0;
            $maksimalBimbingan = $dosen->kuota_total ?? 10; // Default 10 jika tidak ada field kuota_total
            $sisaKuota = $maksimalBimbingan - $jumlahBimbingan;

            return [
                'id' => $dosen->id,
                'nama_lengkap' => $namaLengkap,
                'email' => $email,
                'nik' => $dosen->nik ?? '-',
                'foto_profil' => $dosen->user->foto_profil 
                    ? asset('storage/' . $dosen->user->foto_profil) 
                    : null,
                'program_studi' => $dosen->prodi->nama_prodi ?? '-',
                'prodi' => $dosen->prodi->nama_prodi ?? '-',
                
                // ✅ PENTING: Bidang keahlian
                'bidang_keahlian' => $dosen->bidang_keahlian ?? '-',
                
                // ✅ PENTING: Kuota dalam format yang benar
                'jumlah_bimbingan' => $jumlahBimbingan,
                'maksimal_bimbingan' => $maksimalBimbingan,
                'bimbingan' => "{$jumlahBimbingan} dari {$maksimalBimbingan} Mahasiswa",
                'kuota' => $sisaKuota, // Sisa kuota
                
                // Status apakah masih bisa menerima mahasiswa
                'dapat_menerima' => $sisaKuota > 0,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar dosen berhasil diambil',
            'data' => $data,
        ], 200);

    } catch (\Exception $e) {
        Log::error('❌ getDosenByProdi error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            'data' => [],
        ], 500);
    }
}

        /**
 * ===============================
 * GET Daftar Mahasiswa Bimbingan (DOSEN)
 * ===============================
 */
public function getDaftarMahasiswa()
{
    try {
        $user = Auth::user();

        // Ambil dosen dari user login
        $dosen = DB::table('dosen')
            ->where('user_id', $user->id)
            ->first();

        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum terdaftar sebagai dosen',
                'data' => [],
            ], 403);
        }

       $rows = DB::table('ajuan_dospem as a')
    ->join('mahasiswa as m', 'm.user_id', '=', 'a.user_id')
    ->leftJoin('program_studis as ps', 'a.program_studis_id', '=', 'ps.id')
    ->where('a.dosen_id', $dosen->id)
    ->where('a.status', 'diterima')
    ->select(
        'm.id as mahasiswa_id',
        'm.nim',
        'ps.nama_prodi',
        'a.user_id'
    )
    ->distinct()
    ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada mahasiswa bimbingan',
                'data' => [],
            ], 200);
        }

        $result = [];

        foreach ($rows as $row) {
            // Decrypt NIM
            $nim = $row->nim;
            try {
                if ($nim && strlen($nim) > 20) {
                    $nim = Crypt::decryptString($nim);
                }
            } catch (\Exception $e) {
                $nim = '-';
            }

            // Ambil & decrypt nama mahasiswa dari users
            $userRow = DB::table('users')->where('id', $row->user_id)->first();
            $nama = $userRow?->nama_lengkap ?? '-';

            try {
                if ($nama && strlen($nama) > 50) {
                    $nama = Crypt::decryptString($nama);
                }
            } catch (\Exception $e) {
                $nama = 'Tidak Diketahui';
            }

            $result[] = [
                'mahasiswa_id'   => $row->mahasiswa_id,
                'nama_mahasiswa' => $nama,
                'nim'            => $nim,
                'program_studi'  => $row->nama_prodi ?? '-',
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Data mahasiswa berhasil diambil',
            'data' => $result,
        ], 200);

    } catch (\Exception $e) {
        Log::error('❌ getDaftarMahasiswa error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server',
            'data' => [],
        ], 500);
    }
}

/**
 * ===============================
 * POST Create Jadwal Bimbingan (DOSEN)
 * ===============================
 */
public function createJadwalBimbingan(Request $request)
{
    try {
        $dosen = Auth::user()->dosen;
        
        if (!$dosen) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'judul_bimbingan' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'waktu' => 'required|string',
            'lokasi' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Cek apakah mahasiswa adalah bimbingan dosen ini
        $mahasiswa = DB::table('mahasiswa')
            ->where('id', $request->mahasiswa_id)
            ->first();
        
        $isBimbingan = DB::table('ajuan_dospem')
            ->where('user_id', $mahasiswa->user_id)
            ->where('dosen_id', $dosen->id)
            ->where('status', 'diterima')
            ->exists();
        
        if (!$isBimbingan) {
            return response()->json([
                'success' => false,
                'message' => 'Mahasiswa bukan bimbingan Anda'
            ], 403);
        }
        
        // Insert ke tabel bimbingan (menggunakan tabel existing 'bimbingan')
        $jadwal = DB::table('bimbingan')->insert([
            'dosen_id' => $dosen->id,
            'mahasiswa_id' => $request->mahasiswa_id,
            'judul' => $request->judul_bimbingan,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu,
            'lokasi' => $request->lokasi,
            'status' => 'menunggu',
            'pengaju' => 'dosen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Jadwal bimbingan berhasil dibuat'
        ], 201);
        
    } catch (\Exception $e) {
        Log::error('Error createJadwalBimbingan: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}
public function getDospemAktifMahasiswa()
{
    try {
        $ajuan = AjuanDospem::where('user_id', Auth::id())
            ->where('status', 'diterima')
                ->with([
                    'dosen.user',
                ])
            ->latest()
            ->first();

        if (!$ajuan) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada dosen pembimbing aktif',
                'data' => null
            ], 200);
        }

            $dosen = $ajuan->dosen;
            $dosenData = $dosen ? $dosen->toArray() : null;
            if ($dosen && $dosen->relationLoaded('user') && $dosen->user) {
                $dosenData['user'] = $dosen->user->toArray();
            }

            $dosenResponse = [
                'id' => $dosen?->id,
                'nama_lengkap' => $dosen?->user?->nama_lengkap ?? '-',
                'email' => $dosen?->user?->email ?? '-',
                'no_hp' => $dosen?->no_telepon ?? null,
                'bidang_keahlian' => $dosen->bidang_keahlian ?? '-',
                'kantor' => $dosen->kantor ?? '-',
                'foto_profil' => $dosen?->user?->foto_profil ?? null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Dosen pembimbing aktif ditemukan',
                'data' => $dosenResponse,
            ], 200);
    } catch (\Exception $e) {
        Log::error('❌ getDospemAktifMahasiswa error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server',
            'data' => null
        ], 500);
    }

}
}