<?php

use App\Models\User;
use App\Models\Pengumuman;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

// LOGIN
Route::get('/', function () {
    return view('login');
})->name('login');

Route::post('/', [AdminController::class, 'login'])->name('auth');


// DASHBOARD
Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

// KELOLA DOSEN
route::middleware(['auth'])->group(function() {
Route::get('/dosen', [AdminController::class, 'getDosen'])->name('dosen');
Route::post('/dosen/store', [AdminController::class, 'simpanDosen'])->name('dosen.store');
Route::patch('/dosen/{id}', [AdminController::class, 'updateDosen'])->name('dosen.update');
Route::delete('/dosen/{id}', [AdminController::class, 'destroyDosen'])->name('dosen.destroy');
$dosen = User::where('role', 'dosen')->get();
});

// Kelola Mahasiswa
//tambah mahasiswa
Route::middleware(['auth'])->group(function () {
Route::get('/mahasiswa', [AdminController::class, 'getMahasiswa'])->name('mahasiswa');


Route::post('/mahasiswa/store', [AdminController::class, 'simpanMahasiswa'])->name('mahasiswa.store');


//edit mahasiswa
Route::get('/mahasiswa/{id}/edit', [AdminController::class, 'editMahasiswa'])->name('mahasiswa.edit');
Route::patch('/mahasiswa/{id}', [AdminController::class, 'updateMahasiswa'])->name('mahasiswa.update');

//hapus mahasiswa
Route::delete('/mahasiswa/{id}', [AdminController::class, 'destroyMahasiswa'])->name('mahasiswa.destroy');
});


// INFORMASI
Route::middleware(['auth'])->group(function() {
    Route::get('/informasi', [AdminController::class, 'getPengumuman'])->name('informasi');
    Route::post('/informasi/store', [AdminController::class, 'simpanPengumuman'])->name('informasi.store');
    Route::patch('/informasi/{id}', [AdminController::class, 'updatePengumuman'])->name('informasi.update');
    Route::delete('/informasi/{id}', [AdminController::class, 'destroyPengumuman'])->name('informasi.destroy');
});



// LOGOUT → kembali ke login
Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
