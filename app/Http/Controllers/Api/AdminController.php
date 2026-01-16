<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Pengumuman;
use App\Models\TugasAkhir;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        
        $user = User::where('username', $request->username)
            ->where('role', 'admin')
            ->first();

        // if($user->role != 'admin')
        // {
        //     \Log::info('Role salah');
        //     return redirect()->back()->with('error', 'Role tidak diizinkan');
        // }

        if(!$user) 
        {
            return redirect()->back()->with('error', 'username atau password salah');
        }


        if($user->isBlocked())
        {
            return redirect()->back()->with('error', 'Akun diblokir');
        }

        if(! Hash::check($request->password, $user->password))
        {
            $user->incrementLoginAttempts();

            $remainingAttempts = $user->getRemainingAttempts();

            if($user->is_blocked)
            {
                return redirect()->back()->with('error', 'Akun anda telah diblokir karena terlalu banyak percobaan');
            }

            return redirect()->back()->with(
            'error',
            $remainingAttempts <= 3 ? "Perhatian! Akun akan diblokir setelah $remainingAttempts percobaan lagi." : "Password  salah");
        }

        $user->resetLoginAttempts();

        Auth::login($user);
        return redirect()->route('dashboard');

        // return back()->withErrors(['username' => 'Login Gagal']);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::info('User logged out successfully.');
        return redirect()->route('login');
    }

    
    public function getPengumuman() 
    {
        // $pengumuman = Pengumuman::all();

        $pengumuman = Pengumuman::orderBy('updated_at', 'desc')->get();
        
        return view('informasi', compact('pengumuman'));
    }

    public function index()
    {
        // $pengumuman = Pengumuman::orderBy('updated_at', 'desc')->get();
        $pengumuman = Pengumuman::orderBy('updated_at', 'desc')->limit(3)->get();
        $totalDosen = Dosen::count();
        $totalMahasiswa = Mahasiswa::count();
        $totalTugasAkhir = TugasAkhir::count();
        $totalProdi = ProgramStudi::count();
        $activityLogs = ActivityLog::with('user')->orderBy('created_at', 'desc')->limit(5)->get();
        return view('dashboard', compact('pengumuman', 'totalDosen', 'totalMahasiswa', 'totalTugasAkhir', 'totalProdi', 'activityLogs'));
    }



    public function getMahasiswa()
    {
        $mahasiswa = Mahasiswa::with('programStudi' , 'user', 'tugasAkhir.dosen.user')
        ->orderBy('updated_at', 'asc')
        ->get();

        $dosen = Dosen::with('user', 'programStudi')
        ->orderBy('updated_at', 'asc')
        ->get();

        $prodi = ProgramStudi::all();
        
        Log::info("Data Dosen: $dosen");
        
        return view('mahasiswa', compact('mahasiswa','dosen', 'prodi'));
    }

    public function getDosen(Request $request)
    {
        $search = $request->input('dosenSearch');
        $perPage = $request->per_page ?? 10;
        $dosen = Dosen::with(['user', 'programStudi'])
           ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('nama_lengkap', 'like', "%{$search}%");
                    })
                    ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $prodi = ProgramStudi::all();


        return view('dosen', compact('dosen', "prodi", 'search', 'perPage'));
    }



    // public function create()
    // {
    // $dosen = User::where(column: 'role', 'dosen')->get();

    // return view('mahasiswa', compact('dosen'));
    // }
    
    public function simpanMahasiswa(Request $request)
    {
        $user = Auth::user();
        Log::info("User login $user->username dengan ID: $user->id");
        Log::info('Request simpan Mahasiswa', $request->all());
        try {

            $request->validate([
                'nim'             => 'required|string|unique:mahasiswa,nim',
                'nama_lengkap'    => 'required|string',
                'prodi_id'        => 'required|exists:program_studis,id',
                'dosen_pembimbing' => 'sometimes|exists:dosen,id',
            ]);

            $newUser = User::create(attributes: [
                'username'       => $request->nim,
                'nama_lengkap'   => $request->nama_lengkap,
                'password'       => Hash::make('mahasiswa123'),
                'role'           => 'mahasiswa',
            ]);


            $mahasiswa = Mahasiswa::create([
                'user_id'          => $newUser->id,
                'nim'              => $request->nim,
                'prodi_id'         => $request->prodi_id,
                'portofolio'       => '',
            ]);

            Log::info("Data Mahasiswa baru: $mahasiswa");

            $dosenPembimbingId = $request->input('dosen_pembimbing');
            if ($dosenPembimbingId) {
                TugasAkhir::create([
                'id_mahasiswa'     => $mahasiswa->id,
                'id_dosen'        => $dosenPembimbingId,
                'judul_tugas'     => '',
                'deskripsi'       => '',
                'alasan'          => '',
            ]);
            }

            ActivityLog::create(attributes: [
                'id_user' => $user->id,
                'activity' => "Menambahkan mahasiswa : $request->nama_lengkap ($request->nim)",
            ]);


        return redirect()
            ->route('mahasiswa')
            ->with('success', 'Data mahasiswa berhasil ditambahkan');

        } catch (\Exception $e) {

            Log::error('Gagal simpan mahasiswa', [
                'message' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data mahasiswa ');
        }

    }
    
    public function simpanDosen(Request $request)
    {
        $user = Auth::user();
        Log::info("User login $user->username dengan ID: $user->id");
        Log::info('Request simpan dosen', $request->all());
        try {

            $request->validate([
                'nik'             => 'required|string|unique:dosen,nik',
                'nama_lengkap'    => 'required|string',
                'email'           => 'required|email|unique:users,email',
                'prodi_id'        => 'required|exists:program_studis,id',
            ]);

            $newUser = User::create([
                'username'       => $request->nik,
                'nama_lengkap'   => $request->nama_lengkap,
                'email'          => $request->email,
                'password'       => Hash::make('dosen123'),
                'role'           => 'dosen',
            ]);

            

            $dosen = Dosen::create([
                'user_id'          => $newUser->id,
                'nik'              => $request->nik,
                'prodi_id'         => $request->prodi_id,
            ]);
            


        return redirect()
            ->route('dosen')
            ->with('success', 'Data dosen berhasil ditambahkan');

        } catch (\Exception $e) {

            Log::error('Gagal simpan dosen', [
                'message' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data dosen');
        }
    }

    public function updateDosen(Request $request, $id)
    {
        $dosen = Dosen::findOrFail($id);
        $user = $dosen->user;

        $request->validate([
            'nik' => 'required|string|unique:dosen,nik,' . $id,
            'nama_lengkap' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'prodi_id' => 'required|exists:program_studis,id',
        ]);

        $user->update([
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
        ]);

        $dosen->update([
            'nik' => $request->nik,
            'prodi_id' => $request->prodi_id,
        ]);

        return redirect()->route('dosen')->with('success', 'Data dosen berhasil diperbarui');
    }    

    public function destroyDosen($id)
{
    $dosen = Dosen::findOrFail($id);
    $user = $dosen->user;

    $dosen->delete();

    $user->delete();

    return redirect()
        ->route('dosen')
        ->with('success', 'Data dosen berhasil dihapus');
}


   public function simpanPengumuman(Request $request)
{
    Log::info('Menyimpan pengumuman baru');
    $user = Auth::user();

    try{
        Log::info("Mengambil data user login $user->username dengan ID: $user->id");
        $request->validate([
            'judul' => 'required|string',
            'isi' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',      
            'tgl_mulai' => 'nullable|date',
            'tgl_selesai' => 'nullable|date'
        ]);

        $filePath = null;
        $originalName = null; // ← TAMBAHKAN INI
        
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName(); // ← AMBIL NAMA ASLI
            $fileName = time() . '_' . $originalName; // ← BUAT NAMA UNIK
            $filePath = $file->storeAs('lampiran', $fileName, 'public'); // ← SIMPAN DENGAN NAMA UNIK
        }

        Log::info("hasil request $request");
        $pengumuman = Pengumuman::create([
            'user_id' => $user->id,
            'judul' => $request->judul,
            'isi' => $request->isi,
            'attachment' => $filePath,
            'attachment_name' => $originalName, // ← TAMBAHKAN INI
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai
        ]);

        Log::info('Pengumuman berhasil disimpan dengan ID: ' . $pengumuman->id);

        return redirect()->back()->with('success', 'Pengumuman berhasil disimpan');
    } catch (\Exception $e) {
        Log::error('Error menyimpan pengumuman: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan pengumuman');
    }
}
   public function updatePengumuman(Request $request, $id)
{
    $pengumuman = Pengumuman::findOrFail($id);

    $request->validate([
        'judul' => 'required|string',
        'isi' => 'required|string',
        'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        'tgl_mulai' => 'required|date',
        'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
    ]);

    // Update data dasar
    $pengumuman->judul = $request->judul;
    $pengumuman->isi = $request->isi;
    $pengumuman->tgl_mulai = $request->tgl_mulai;
    $pengumuman->tgl_selesai = $request->tgl_selesai;

    // Handle upload file baru (jika ada)
    if ($request->hasFile('attachment')) {
        // Hapus file lama
        if ($pengumuman->attachment) {
            \Storage::disk('public')->delete($pengumuman->attachment);
        }

        $file = $request->file('attachment');
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . $originalName;
        $filePath = $file->storeAs('lampiran', $fileName, 'public');

        $pengumuman->attachment = $filePath;
        $pengumuman->attachment_name = $originalName; // ← TAMBAHKAN INI
    }

    $pengumuman->save();

    return redirect()->back()->with('success', 'Pengumuman berhasil diperbarui');
}
    public function destroyPengumuman($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->delete();

        return redirect()->back()->with('success', 'Pengumuman berhasil dihapus');
    }


    public function updateMahasiswa(Request $request, $id)
{
    $mahasiswa = Mahasiswa::findOrFail($id);
    $user = $mahasiswa->user;

    $request->validate([
        'nim' => 'required|string|unique:mahasiswa,nim,' . $id,
        'nama_lengkap' => 'required|string',
        'prodi_id' => 'required|exists:program_studis,id',
        'id_dosen' => 'nullable|exists:dosen,id',
    ]);

    $user->update(['nama_lengkap' => $request->nama_lengkap]);

    $mahasiswa->update([
        'nim' => $request->nim,
        'prodi_id' => $request->prodi_id,
        // 'dosen_pembimbing_id' => $request->dosenPembimbingId ?? null
    ]);

    $mahasiswa->tugasAkhir()->update([
        'id_dosen' => $request->id_dosen
    ]);

    return redirect()->route('mahasiswa')->with('success', 'Data mahasiswa berhasil diperbarui');
}


    public function destroyMahasiswa($id)
    {
        $mahasiswa = Mahasiswa::findOrFail($id);

        // Hapus tugas akhir terkait
        $mahasiswa->tugasAkhir()->delete(); // pastikan relasi tugasAkhir() ada di model Mahasiswa

        // Hapus mahasiswa
        $mahasiswa->delete();

        // Hapus user terkait jika mau
        $user = $mahasiswa->user;
        if ($user) {
            $user->delete();
        }

        return redirect()->route('mahasiswa')
                        ->with('success', 'Data mahasiswa berhasil dihapus');
    }


    public function viewDashboard()
    {
        $totalDosen = Dosen::count();
        $totalMahasiswa = Mahasiswa::count();
        $totalTugasAkhir = TugasAkhir::count();
        $totalProdi = ProgramStudi::count();
        
    }

}

