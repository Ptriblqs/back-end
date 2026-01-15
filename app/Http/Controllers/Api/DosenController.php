<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DosenController extends Controller
{
    /**
     * MAHASISWA: Get semua dokumen milik mahasiswa yang login
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Pastikan user adalah mahasiswa
            if ($user->role !== 'mahasiswa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya mahasiswa yang dapat mengakses endpoint ini.',
                ], 403);
            }

            $dokumen = Dokumen::where('mahasiswa_id', $user->id)
                ->with(['dosen:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $dokumen,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getDokumen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dokumen',
            ], 500);
        }
    }

    /**
     * DOSEN: Get list mahasiswa yang sudah upload dokumen ke dosen ini
     */
    public function getMahasiswaList()
    {
        try {
            $user = Auth::user();
            
            // Pastikan user adalah dosen
            if ($user->role !== 'dosen') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya dosen yang dapat mengakses endpoint ini.',
                ], 403);
            }

            $mahasiswaList = Dokumen::where('dosen_id', $user->id)
                ->with('mahasiswa:id,name,nim,jurusan')
                ->select('mahasiswa_id')
                ->distinct()
                ->get()
                ->map(function ($item) {
                    if (!$item->mahasiswa) {
                        return null;
                    }
                    return [
                        'id' => $item->mahasiswa->id,
                        'nama' => $item->mahasiswa->name,
                        'nim' => $item->mahasiswa->nim ?? '-',
                        'jurusan' => $item->mahasiswa->jurusan ?? 'Teknik Informatika',
                    ];
                })
                ->filter() // Remove null values
                ->values(); // Reindex array

            return response()->json([
                'success' => true,
                'data' => $mahasiswaList,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getMahasiswaList: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat list mahasiswa',
            ], 500);
        }
    }

    /**
     * DOSEN: Get semua dokumen dari mahasiswa tertentu
     */
    public function getDokumenMahasiswa($mahasiswaId)
    {
        try {
            $user = Auth::user();
            
            // Pastikan user adalah dosen
            if ($user->role !== 'dosen') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya dosen yang dapat mengakses endpoint ini.',
                ], 403);
            }

            $dokumen = Dokumen::where('dosen_id', $user->id)
                ->where('mahasiswa_id', $mahasiswaId)
                ->with('mahasiswa:id,name,nim')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $dokumen,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getDokumenMahasiswa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dokumen mahasiswa',
            ], 500);
        }
    }

    /**
     * MAHASISWA: Upload dokumen baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dosen_id' => 'required|exists:users,id',
            'judul' => 'required|string|max:255',
            'bab' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // max 10MB
        ], [
            'dosen_id.required' => 'Dosen pembimbing harus dipilih',
            'dosen_id.exists' => 'Dosen tidak ditemukan',
            'judul.required' => 'Judul dokumen harus diisi',
            'file.required' => 'File dokumen harus diupload',
            'file.mimes' => 'File harus berformat PDF, DOC, atau DOCX',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Verifikasi dosen_id adalah user dengan role dosen
            $dosen = User::where('id', $request->dosen_id)
                ->where('role', 'dosen')
                ->first();
                
            if (!$dosen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dosen tidak ditemukan atau bukan dosen yang valid',
                ], 404);
            }

            // Simpan file ke storage/app/public/dokumen
            $filePath = $request->file('file')->store('dokumen', 'public');

            $dokumen = Dokumen::create([
                'mahasiswa_id' => $user->id,
                'dosen_id' => $request->dosen_id,
                'judul' => $request->judul,
                'bab' => $request->bab,
                'deskripsi' => $request->deskripsi,
                'file_path' => $filePath,
                'status' => 'Menunggu',
                'tanggal_upload' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload',
                'data' => $dokumen,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error uploadDokumen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload dokumen',
            ], 500);
        }
    }

    /**
     * DOSEN: Update status dokumen (Disetujui atau Revisi)
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Menunggu,Revisi,Disetujui',
            'catatan_revisi' => 'required_if:status,Revisi|nullable|string',
        ], [
            'status.required' => 'Status harus dipilih',
            'status.in' => 'Status tidak valid',
            'catatan_revisi.required_if' => 'Catatan revisi harus diisi jika status Revisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Pastikan dokumen ini milik mahasiswa bimbingan dosen ini
            $dokumen = Dokumen::where('dosen_id', $user->id)->find($id);

            if (!$dokumen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dokumen tidak ditemukan atau bukan mahasiswa bimbingan Anda',
                ], 404);
            }

            $dokumen->update([
                'status' => $request->status,
                'catatan_revisi' => $request->catatan_revisi,
                'tanggal_revisi' => $request->status == 'Revisi' ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status dokumen berhasil diperbarui',
                'data' => $dokumen,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updateStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status dokumen',
            ], 500);
        }
    }

    /**
     * MAHASISWA: Upload ulang dokumen yang direvisi
     */
    public function uploadRevisi(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'deskripsi' => 'nullable|string',
        ], [
            'file.required' => 'File revisi harus diupload',
            'file.mimes' => 'File harus berformat PDF, DOC, atau DOCX',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $dokumen = Dokumen::where('mahasiswa_id', $user->id)->find($id);

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

            // Backup file lama ke file_revisi_path
            if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
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
        } catch (\Exception $e) {
            Log::error('Error uploadRevisi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload dokumen revisi',
            ], 500);
        }
    }

    /**
     * Download dokumen (Mahasiswa & Dosen)
     */
    public function download($id)
    {
        try {
            $user = Auth::user();
            $dokumen = Dokumen::find($id);

            if (!$dokumen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dokumen tidak ditemukan',
                ], 404);
            }

            // Cek authorization: hanya mahasiswa pemilik atau dosen pembimbing
            if ($user->id != $dokumen->mahasiswa_id && $user->id != $dokumen->dosen_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak memiliki akses untuk mengunduh dokumen ini',
                ], 403);
            }

            $filePath = storage_path('app/public/' . $dokumen->file_path);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan di server',
                ], 404);
            }

            return response()->download($filePath);
        } catch (\Exception $e) {
            Log::error('Error downloadDokumen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh dokumen',
            ], 500);
        }
    }

    /**
     * Get detail dokumen
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $dokumen = Dokumen::with(['mahasiswa:id,name,nim', 'dosen:id,name'])->find($id);

            if (!$dokumen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dokumen tidak ditemukan',
                ], 404);
            }

            // Cek authorization
            if ($user->id != $dokumen->mahasiswa_id && $user->id != $dokumen->dosen_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak memiliki akses',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $dokumen,
            ]);
        } catch (\Exception $e) {
            Log::error('Error showDokumen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail dokumen',
            ], 500);
        }
    }

    /**
     * MAHASISWA: Hapus dokumen (hanya jika belum disetujui)
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $dokumen = Dokumen::where('mahasiswa_id', $user->id)->find($id);

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
        } catch (\Exception $e) {
            Log::error('Error deleteDokumen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus dokumen',
            ], 500);
        }
    }

    /**
     * MAHASISWA: Update dokumen (hanya jika status Menunggu)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $dokumen = Dokumen::where('mahasiswa_id', $user->id)->find($id);

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
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
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
        } catch (\Exception $e) {
            Log::error('Error updateDokumen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui dokumen',
            ], 500);
        }
    }
}