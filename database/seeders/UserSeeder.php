<?php 

namespace Database\Seeders;

use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Pengumuman;
use App\Models\ProgramStudi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil salah satu prodi yang sudah ada
        $prodi = ProgramStudi::first(); // ambil prodi pertama dari tabel program_studis
        
        // Buat Admin
        $admin = User::create([
            'username' => 'admin1',
            'nama_lengkap' => 'Admin pertama',
            'foto_profil' => '',
            'email' => 'admin1@example.com',
            'password' => 'admin123',
            'role' => 'admin'
        ]);
        $admin->generateIntegrityHash();
        $admin->saveQuietly();
        
        // Buat Dosen
        $dosen = User::create([
            'username' => 'dosen1',
            'nama_lengkap' => 'Dosen pertama',
            'foto_profil' => '',
            'email' => 'gamersmhg1234@gmail.com',
            'password' => 'dosen123',
            'role' => 'dosen'
        ]);
        $dosen->generateIntegrityHash();
        $dosen->saveQuietly();
        
        Dosen::create([
            'user_id' => $dosen->id,
            'nik' => '123457890',
            'bidang_keahlian' => 'AI',
            'prodi_id' => $prodi->id, // wajib diisi
        ]);
        
        // Buat Mahasiswa
        $mahasiswa = User::create([
            'username' => 'mahasiswa1',
            'nama_lengkap' => 'Mahasiswa pertama',
            'foto_profil' => '',
            'email' => 'mhaqqi1234@gmail.com',
            'password' => 'mahasiswa123',
            'role' => 'mahasiswa'
        ]);
        $mahasiswa->generateIntegrityHash();
        $mahasiswa->saveQuietly();

        Mahasiswa::create([
            'user_id' => $mahasiswa->id,
            'prodi_id' => $prodi->id, // wajib diisi
            'nim' => '4342401001',
            'portofolio' => ''
        ]);

        // Buat Pengumuman
        Pengumuman::create([
            'user_id' => $admin->id,
            'judul' => 'Pengumuman mahasiswa yang mengikuti tugas akhir',
            'isi' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Sit amet consectetur adipiscing elit quisque faucibus ex. Adipiscing elit quisque faucibus ex sapien vitae pellentesque.',
            'attachment' => 'tugasAkhir.exe',
        ]);
    }
}
