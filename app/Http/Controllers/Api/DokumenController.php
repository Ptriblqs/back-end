<?php

namespace App\Http\Controllers\Api;

use App\Models\Dosen;
use App\Models\Dokumen;
use App\Models\Mahasiswa;
use App\Models\AjuanDospem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DokumenController extends Controller
{
    /**
     * MAHASISWA: Get semua dokumen milik mahasiswa yang login
     */
 public function index()
{
    $mahasiswaId = Auth::user()->id;

    $dokumen = Dokumen::where('mahasiswa_id', $mahasiswaId)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $dokumen,
    ]);


    
}

    /**
     * MAHASISWA: Get progress dokumen per BAB (suitable for bar chart)
     */
    public function progress()
    {
        $mahasiswaId = Auth::id();

        $subBabMap = [
            'BAB I' => [
                '1.1 Latar Belakang',
                '1.2 Rumusan Masalah',
                '1.3 Tujuan',
                '1.4 Batasan Masalah',
                '1.5 Manfaat',
            ],
            'BAB II' => [
                '2.1 Penelitian Terkait',
                '2.2 Landasan Teori',
                '2.3 Metode Pengembangan Produk',
            ],
            'BAB III' => [
                '3.1 Analisis Kebutuhan',
                '3.2 Perancangan',
            ],
            'BAB IV' => [
                '4.1 Hasil Implementasi',
                '4.2 Pengujian User Acceptance Testing (UAT)',
            ],
            'BAB V' => [
                '5.1 Kesimpulan',
                '5.2 Saran',
            ],
        ];

        $perBab = [];
        $overallTotal = 0;
        $overallApproved = 0;

        foreach ($subBabMap as $bab => $subs) {
            $total = count($subs);
            $approved = 0;

            foreach ($subs as $sub) {
                $isApproved = Dokumen::where('mahasiswa_id', $mahasiswaId)
                    ->where('judul', $sub)
                    ->where('status', 'Disetujui')
                    ->exists();

                if ($isApproved) {
                    $approved++;
                }
            }

            $percent = $total ? (int) round(($approved / $total) * 100) : 0;

            $perBab[] = [
                'bab' => $bab,
                'total_sub' => $total,
                'approved' => $approved,
                'percent' => $percent,
            ];

            $overallTotal += $total;
            $overallApproved += $approved;
        }

        $overallPercent = $overallTotal ? (int) round(($overallApproved / $overallTotal) * 100) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'per_bab' => $perBab,
                'labels' => array_keys($subBabMap),
                'dataset' => array_map(function ($r) {
                    return $r['percent'];
                }, $perBab),
                'overall_percent' => $overallPercent,
            ],
        ]);
    }

    /**
     * DOSEN: Get list mahasiswa yang sudah upload dokumen ke dosen ini
     */
public function getMahasiswaList()
{
    // Pastikan user adalah dosen terdaftar
    $dosen = Dosen::where('user_id', Auth::id())->first();
    if (! $dosen) {
        return response()->json(['success' => false, 'message' => 'Akun ini bukan dosen'], 403);
    }

    // Ambil daftar mahasiswa_id yang unik dari tabel dokumen untuk dosen ini
    $mahasiswaIds = Dokumen::where('dosen_id', Auth::id())
        ->distinct()
        ->pluck('mahasiswa_id')
        ->filter()
        ->values()
        ->toArray();

    if (empty($mahasiswaIds)) {
        return response()->json(['success' => true, 'data' => []]);
    }

    // Ambil data user + relasi mahasiswa (nim, prodi)
    $users = \App\Models\User::with('mahasiswa.programStudi')
        ->whereIn('id', $mahasiswaIds)
        ->get();

    $result = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'nama' => $user->nama_lengkap ?? $user->username,
            'nim' => optional($user->mahasiswa)->nim ?? '-',
            'jurusan' => optional($user->mahasiswa->programStudi)->nama_prodi ?? '-',
        ];
    })->values();

    return response()->json(['success' => true, 'data' => $result]);
}

    /**
     * DOSEN: Get semua dokumen dari mahasiswa tertentu
     */
    public function getDokumenMahasiswa($mahasiswaId) 
    {
        $dokumen = Dokumen::where('dosen_id', Auth::id())
            ->where('mahasiswa_id', $mahasiswaId)
            ->orderBy('created_at', 'desc')
            ->get();
;

        return response()->json([
            'success' => true,
            'data' => $dokumen,
        ]);
    }

    /**
     * MAHASISWA: Upload dokumen baru
     */
    public function store(Request $request)
{
    // 1️⃣ Validasi
    $validator = Validator::make($request->all(), [
        'judul' => 'required|string|max:255',
        'bab' => 'required|string|max:100',
        'deskripsi' => 'nullable|string',
        'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors(),
        ], 422);
    }

    // 2️⃣ Ambil mahasiswa.id
    $mahasiswaId = Auth::user()->id;

    // 3️⃣ Ambil dosen.id dari ajuan_dospem
    $dosenId = AjuanDospem::where('user_id', Auth::id())
        ->where('status', 'diterima')
        ->value('dosen_id');

    if (!$dosenId) {
        return response()->json([
            'success' => false,
            'message' => 'Dosen pembimbing belum ditentukan',
        ], 400);
    }

    // 4️⃣ Konversi dosen.id → dosen.user_id
    $dosenUserId = Dosen::where('id', $dosenId)->value('user_id');

    if (!$dosenUserId) {
        return response()->json([
            'success' => false,
            'message' => 'User dosen tidak ditemukan',
        ], 404);
    }

    // ===== Hardcoded sub-bab map (validate order here) =====
    $subBabMap = [
        'BAB I' => [
            '1.1 Latar Belakang',
            '1.2 Rumusan Masalah',
            '1.3 Tujuan',
            '1.4 Batasan Masalah',
            '1.5 Manfaat',
        ],
        'BAB II' => [
            '2.1 Penelitian Terkait',
            '2.2 Landasan Teori',
            '2.3 Metode Pengembangan Produk',
        ],
        'BAB III' => [
            '3.1 Analisis Kebutuhan',
            '3.2 Perancangan',
        ],
        'BAB IV' => [
            '4.1 Hasil Implementasi',
            '4.2 Pengujian User Acceptance Testing (UAT)',
        ],
        'BAB V' => [
            '5.1 Kesimpulan',
            '5.2 Saran',
        ],
    ];
   $bab = $request->input('bab');
    $judul = $request->input('judul');

    if (!isset($subBabMap[$bab])) {
        return response()->json(['success' => false, 'message' => 'Bab tidak valid', 'alert' => true], 400);
    }

    $list = $subBabMap[$bab];
    $pos = array_search($judul, $list);
    if ($pos === false) {
        return response()->json(['success' => false, 'message' => 'Judul (sub-bab) tidak valid untuk bab ini', 'alert' => true], 400);
    }

    // Pastikan semua bab sebelumnya selesai (setiap sub-bab harus berstatus Disetujui)
    $babOrder = array_keys($subBabMap);
    $babIndex = array_search($bab, $babOrder);
    for ($i = 0; $i < $babIndex; $i++) {
        $prevBab = $babOrder[$i];
        foreach ($subBabMap[$prevBab] as $prevSub) {
            $count = Dokumen::where('mahasiswa_id', $mahasiswaId)
                ->where('judul', $prevSub)
                ->where('status', 'Disetujui')
                ->count();
            if ($count < 1) {
                return response()->json(['success' => false, 'message' => "Selesaikan semua sub-bab di {$prevBab} terlebih dahulu", 'alert' => true], 403);
            }
        }
    }

    // Pastikan sub-bab sebelumnya di bab ini sudah selesai
    $requiredPrev = array_slice($list, 0, $pos);
    foreach ($requiredPrev as $prevSub) {
        $c = Dokumen::where('mahasiswa_id', $mahasiswaId)
            ->where('judul', $prevSub)
            ->where('status', 'Disetujui')
            ->count();
        if ($c < 1) {
            return response()->json(['success' => false, 'message' => "Selesaikan sub-bab sebelumnya: {$prevSub}", 'alert' => true], 403);
        }
    }
    
     // Jika sudah ada dokumen untuk sub-bab yang sama, siapkan increment revisi
    $existing = Dokumen::where('mahasiswa_id', $mahasiswaId)
        ->where('judul', $judul)
        ->orderBy('created_at', 'desc')
        ->first();

    $revisiCount = null;
    if (Schema::hasColumn('dokumen', 'revisi')) {
        if ($existing) {
            // Do NOT increment revisi when student uploads a new file while previous status is 'Menunggu'.
            // `revisi` should be incremented when lecturer sets status to 'Revisi' (handled in updateStatus).
            $revisiCount = $existing->revisi ?? 0;

            // Backup previous file only when the previous record was in 'Revisi' state
            if ($existing->status === 'Revisi' && Schema::hasColumn('dokumen', 'file_revisi_path')) {
                $existing->file_revisi_path = $existing->file_path;
                $existing->save();
            }
        } else {
            $revisiCount = 0;
        }
    }

    // 5️⃣ Upload file (preserve original filename with timestamp prefix to avoid collisions)
    $originalName = $request->file('file')->getClientOriginalName();
    $filename = time() . '_' . $originalName;
    $filePath = $request->file('file')->storeAs('dokumen', $filename, 'public');

    // 6️⃣ Simpan dokumen (INI BARU BENAR)
    $dokumen = Dokumen::create([
        'mahasiswa_id' => $mahasiswaId,   // mahasiswa.id
        'dosen_id' => $dosenUserId,        // users.id dosen ✅
        'judul' => $request->judul,
        'bab' => $request->bab,
        'deskripsi' => $request->deskripsi,
        'file_path' => $filePath,
        'status' => 'Menunggu',
        'tanggal_upload' => now(),
    ]);

    // Kirim notifikasi ke dosen bahwa ada dokumen baru
    try {
        $namaMahasiswa = Auth::user()->nama_lengkap ?? 'Mahasiswa';
        $pesan = "Mahasiswa {$namaMahasiswa} mengunggah dokumen: {$dokumen->judul}";
        app(\App\Services\NotifikasiService::class)->kirimNotifikasi($dosenUserId, 'dokumen_baru', $pesan);
    } catch (\Exception $e) {
        // jangan ganggu alur utama jika notifikasi gagal
    }

    return response()->json([
        'success' => true,
        'message' => 'Dokumen berhasil diupload',
        'data' => $dokumen,
    ], 201);
}


    /**
     * DOSEN: Update status dokumen (Disetujui atau Revisi)
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Menunggu,Revisi,Disetujui',
            'catatan_revisi' => 'required_if:status,Revisi|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Pastikan dokumen ini milik mahasiswa bimbingan dosen ini
        $dokumen = Dokumen::where('dosen_id', Auth::id())->find($id);

        if (!$dokumen) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan atau bukan mahasiswa bimbingan Anda',
            ], 404);
        }

        $updateData = [
            'status' => $request->status,
            'catatan_revisi' => $request->catatan_revisi,
            'tanggal_revisi' => $request->status == 'Revisi' ? now() : null,
        ];

        if ($request->status == 'Revisi' && Schema::hasColumn('dokumen', 'revisi')) {
            $updateData['revisi'] = ($dokumen->revisi ?? 0) + 1;
        }

        $dokumen->update($updateData);

        // Kirim notifikasi ke mahasiswa tentang perubahan status
        try {
            $mahasiswaUserId = $dokumen->mahasiswa_id;
            $pesan = '';
            if ($request->status == 'Disetujui') {
                $pesan = "Dokumen Anda ('{$dokumen->judul}') telah disetujui oleh pembimbing.";
            } elseif ($request->status == 'Revisi') {
                $catatan = $request->catatan_revisi ?? '';
                $pesan = "Dokumen Anda ('{$dokumen->judul}') perlu direvisi. Catatan: {$catatan}";
            } else {
                $pesan = "Status dokumen Anda ('{$dokumen->judul}') berubah menjadi: {$request->status}";
            }
            app(\App\Services\NotifikasiService::class)->kirimNotifikasi($mahasiswaUserId, 'dokumen_status', $pesan);
        } catch (\Exception $e) {
            // non-blocking
        }

        return response()->json([
            'success' => true,
            'message' => 'Status dokumen berhasil diperbarui',
            'data' => $dokumen,
        ]);
    }

    /**
     * MAHASISWA: Upload ulang dokumen yang direvisi
     */
    public function uploadRevisi(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $dokumen = Dokumen::where('mahasiswa_id', Auth::id())->find($id);

        if (!$dokumen) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan',
            ], 404);
        }

        if ($dokumen->status != 'Revisi') {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen tidak dalam status revisi',
            ], 400);
        }

        // Backup file lama ke file_revisi_path sebelum hapus
        if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
            // Optional: simpan path file lama untuk history
            $dokumen->file_revisi_path = $dokumen->file_path;
            
            // Hapus file lama dari storage
            Storage::disk('public')->delete($dokumen->file_path);
        }

        // Upload file baru
        $filePath = $request->file('file')->store('dokumen', 'public');

        $dokumen->update([
            'file_path' => $filePath,
            'status' => 'Menunggu', // Reset status ke Menunggu
            'deskripsi' => $request->deskripsi ?? $dokumen->deskripsi,
            'tanggal_upload' => now(),
            'catatan_revisi' => null, // Clear catatan revisi lama
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen revisi berhasil diupload',
            'data' => $dokumen,
        ]);
    }

    /**
     * Download dokumen (Mahasiswa & Dosen)
     */
  public function download($id)
{
    $dokumen = Dokumen::find($id);

    if (!$dokumen) {
        return response()->json([
            'success' => false,
            'message' => 'Dokumen tidak ditemukan',
        ], 404);
    }

    // Cek authorization
    if (Auth::id() != $dokumen->mahasiswa_id && Auth::id() != $dokumen->dosen_id) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak memiliki akses untuk mengunduh dokumen ini',
        ], 403);
    }

    $disk = Storage::disk('public');

    if (! $disk->exists($dokumen->file_path)) {
        return response()->json([
            'success' => false,
            'message' => 'File tidak ditemukan di server',
        ], 404);
    }

    $filePath = $disk->path($dokumen->file_path);
    $mimeType = mime_content_type($filePath);

    return response()->download(
        $filePath,
        basename($dokumen->file_path),
        [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="'.basename($dokumen->file_path).'"'
        ]
    );
}


    /**
     * Get detail dokumen
     */
    public function show($id)
    {
        $dokumen = Dokumen::find($id);

        if (!$dokumen) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan',
            ], 404);
        }

        // Cek authorization
        if (Auth::id() != $dokumen->mahasiswa_id && Auth::id() != $dokumen->dosen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $dokumen,
        ]);
    }

    /**
     * MAHASISWA: Hapus dokumen (hanya jika belum disetujui)
     */
    public function destroy($id)
    {
        $dokumen = Dokumen::where('mahasiswa_id', Auth::id())->find($id);

        if (!$dokumen) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan',
            ], 404);
        }

        // Cegah hapus jika sudah disetujui
        if ($dokumen->status == 'Disetujui') {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen yang sudah disetujui tidak dapat dihapus',
            ], 400);
        }

        // Hapus file dari storage
        if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
            Storage::disk('public')->delete($dokumen->file_path);
        }

        $dokumen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus',
        ]);
    }

    /**
     * MAHASISWA: Update dokumen (hanya jika status Menunggu)
     */
    public function update(Request $request, $id)
    {
        $dokumen = Dokumen::where('mahasiswa_id', Auth::id())->find($id);

        if (!$dokumen) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan',
            ], 404);
        }

        // Hanya bisa update jika status Menunggu
        if ($dokumen->status != 'Menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya dokumen dengan status Menunggu yang dapat diupdate',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string|max:255',
            'bab' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Update file jika ada file baru
        if ($request->hasFile('file')) {
            // Hapus file lama
            if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
                Storage::disk('public')->delete($dokumen->file_path);
            }

            // Upload file baru
            $filePath = $request->file('file')->store('dokumen', 'public');
            $dokumen->file_path = $filePath;
        }

        // Update field lainnya
        $dokumen->update([
            'judul' => $request->judul ?? $dokumen->judul,
            'bab' => $request->bab ?? $dokumen->bab,
            'deskripsi' => $request->deskripsi ?? $dokumen->deskripsi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diperbarui',
            'data' => $dokumen,
        ]);
    }
}