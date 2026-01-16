<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\BimbinganController;
    use App\Http\Controllers\Api\TugasAkhirController;
    use App\Http\Controllers\Api\UserProfileController;
    use App\Http\Controllers\Api\PasswordResetController;
    use App\Http\Controllers\Api\AccountRecoveryController;
    use App\Http\Controllers\Api\KanbanController;
    use App\Http\Controllers\Api\DokumenController;
    use App\Http\Controllers\Api\NotifikasiController;
    use App\Http\Controllers\Api\AjuanDospemController;
    use App\Http\Controllers\Api\DosenController;
    use App\Http\Controllers\Api\DosenHomeController;
    use App\Http\Controllers\Api\MahasiswaController;
    use App\Http\Controllers\Api\ProgramStudiController;
    use App\Http\Controllers\Api\JadwalController;
    use App\Http\Controllers\Api\PengumumanController;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES
    |--------------------------------------------------------------------------
    */
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
        
    Route::post('/register', [AuthController::class, 'register']);



    // Public route untuk get program studi (untuk dropdown, dll)
    Route::get('/program-studi-public', function () {
        return \App\Models\ProgramStudi::select('id', 'nama_prodi')->get();
    });



    // Route::prefix('account-recovery')->group(function () {
    //     Route::get('/verify', [AccountRecoveryController::class, 'verifyToken']);
    //     Route::post('/reset-password', [AccountRecoveryController::class, 'resetPassword']);
    //     Route::post('/resend-email', [AccountRecoveryController::class, 'resendRecoveryEmail']);
    // });

    /*
    |--------------------------------------------------------------------------
    | PROTECTED ROUTES (auth:sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        /*
        | AUTH & PROFILE
        */
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/profile', [UserProfileController::class, 'showProfile']);
        Route::post('/profile', [UserProfileController::class, 'updateProfile']);

        // Dospem aktif untuk mahasiswa tertentu (by user id)
        Route::get('/mahasiswa/{user_id}/dospem-aktif', [AjuanDospemController::class, 'getDospemAktifMahasiswa']);

        /*
        |--------------------------------------------------------------------------
        | PROGRAM STUDI (CRUD - Protected)
        |--------------------------------------------------------------------------
        */
    

        /*
        |--------------------------------------------------------------------------
        | DOSEN (CRUD - Protected)
        |--------------------------------------------------------------------------
        */
        Route::prefix('dosen')->group(function () {
            Route::get('/', [DosenController::class, 'index']);
            Route::get('/{id}', [DosenController::class, 'show']);
            Route::post('/', [DosenController::class, 'store']);
            Route::put('/{id}', [DosenController::class, 'update']);
            Route::delete('/{id}', [DosenController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | MAHASISWA (CRUD - Protected)
        |--------------------------------------------------------------------------
        */
        Route::prefix('mahasiswa')->group(function () {
            Route::get('/', [MahasiswaController::class, 'index']);
            Route::get('/{id}', [MahasiswaController::class, 'show']);
            Route::post('/', [MahasiswaController::class, 'store']);
            Route::put('/{id}', [MahasiswaController::class, 'update']);
            Route::delete('/{id}', [MahasiswaController::class, 'destroy']);
        });

        /*
        | NOTIFIKASI
        */
        Route::prefix('notifikasi')->group(function () {
            Route::get('/', [NotifikasiController::class, 'index']);
            Route::get('/unread-count', [NotifikasiController::class, 'unreadCount']);
            Route::delete('/{id}', [NotifikasiController::class, 'destroy']);
            Route::delete('/', [NotifikasiController::class, 'destroyAll']);
            Route::post('/{id}/read', [NotifikasiController::class, 'markAsRead']);
            Route::post('/read-all', [NotifikasiController::class, 'markAllAsRead']);
        });

        /*
        | ADMIN
        */
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/dashboard-admin', fn () =>
                response()->json(['message' => 'Login sukses'], 200)
            );
        });

        /*
        | DOSEN
        */
        Route::middleware('role:dosen')->prefix('dosen')->group(function () {
            Route::get('/dashboard-dosen', fn () =>
                response()->json(['message' => 'Login sukses'], 200)
            );
            Route::get('/home', [DosenHomeController::class, 'index']);
        });

        // Alias endpoint untuk mengakomodasi klien yang memanggil /api/home
        Route::middleware('role:dosen')->get('/home', [DosenHomeController::class, 'index']);

        /*
        | MAHASISWA
        */
        Route::middleware('role:mahasiswa')->prefix('mahasiswa')->group(function () {
            Route::get('/dashboard-mahasiswa', fn () =>
                response()->json(['message' => 'Ini mahasiswa'], 200)
            );
        });

        /*
        |--------------------------------------------------------------------------
        | BIMBINGAN (Controller Bimbingan)
        |--------------------------------------------------------------------------
        */
        Route::prefix('bimbingan')->group(function () {

            // Shared (Mahasiswa & Dosen)
            Route::middleware('role:mahasiswa,dosen')->group(function () {
                Route::get('/', [BimbinganController::class, 'index']);
                Route::get('/jadwal', [JadwalController::class, 'index']);
                Route::get('/kalender', [BimbinganController::class, 'kalenderMahasiswa']);
            });

            // Mahasiswa
            Route::middleware('role:mahasiswa')->group(function () {
                Route::post('/ajukan', [BimbinganController::class, 'storeMahasiswa']);
                Route::put('/{id}/terimamhs', [BimbinganController::class, 'terimaMahasiswa']);
                Route::put('/{id}/tolakmhs', [BimbinganController::class, 'tolakMahasiswa']);
            });

            // Dosen (approve / reject bimbingan)
            Route::middleware('role:dosen')->group(function () {
                Route::put('/{id}/terima', [BimbinganController::class, 'terimaDosen']);
                Route::put('/{id}/tolak', [BimbinganController::class, 'tolakDosen']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | KANBAN
        |--------------------------------------------------------------------------
        */
        Route::prefix('kanban')->group(function () {
            Route::get('/', [KanbanController::class, 'index']);
            Route::post('/', [KanbanController::class, 'store']);
            Route::put('/{id}', [KanbanController::class, 'update']);
            Route::delete('/{id}', [KanbanController::class, 'destroy']);
            Route::patch('/{id}/move', [KanbanController::class, 'moveStatus']);
        });

        /*
        |--------------------------------------------------------------------------
        | DOKUMEN - MAHASISWA
        |--------------------------------------------------------------------------
        */
        Route::prefix('dokumen')->group(function () {
            // Get semua dokumen mahasiswa
            Route::get('/', [DokumenController::class, 'index']);
                Route::get('/progress', [DokumenController::class, 'progress']);
            
            // Upload dokumen baru
            Route::post('/', [DokumenController::class, 'store']);
            
            // Get detail dokumen
            Route::get('/{id}', [DokumenController::class, 'show']);
            
            // Update dokumen (hanya status Menunggu)
            Route::put('/{id}', [DokumenController::class, 'update']);
            Route::post('/{id}', [DokumenController::class, 'update']); // Untuk form-data
            
            // Hapus dokumen
            Route::delete('/{id}', [DokumenController::class, 'destroy']);
            
            // Upload revisi dokumen
            Route::post('/{id}/revisi', [DokumenController::class, 'uploadRevisi']);
            
            // Download dokumen
            Route::get('/{id}/download', [DokumenController::class, 'download']);
        });

        /*
        |--------------------------------------------------------------------------
        | DOKUMEN - DOSEN
        |--------------------------------------------------------------------------
        */
    Route::middleware(['auth:sanctum', 'role:dosen'])
        ->prefix('dosen/dokumen')
        ->group(function () {

            // ✅ List mahasiswa yang upload dokumen ke dosen ini
            Route::get('/mahasiswa', [DokumenController::class, 'getMahasiswaList']);

            // ✅ Semua dokumen dari mahasiswa tertentu
            Route::get(
                '/mahasiswa/{mahasiswaId}',
                [DokumenController::class, 'getDokumenMahasiswa']
            );

            // ✅ Update status dokumen
            Route::put(
                '/{id}/status',
                [DokumenController::class, 'updateStatus']
            );

            // ✅ Download dokumen
            Route::get(
                '/{id}/download',
                [DokumenController::class, 'download']
            );
        }
    );
        /*
        |--------------------------------------------------------------------------
        | AJUAN DOSPEM - DOSEN
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:dosen')->group(function () {

            // Ajuan dospem masuk
            Route::get('/ajuan-dospem/debug-status', [AjuanDospemController::class, 'debugDosenStatus']);
            Route::get('/ajuan-dospem/masuk', [AjuanDospemController::class, 'dosenIndex']);
            Route::get('/ajuan-dospem/{id}', [AjuanDospemController::class, 'show']);
            Route::post('/ajuan-dospem/{id}/terima', [AjuanDospemController::class, 'approve']);
            Route::post('/ajuan-dospem/{id}/tolak', [AjuanDospemController::class, 'reject']);
            Route::get('/ajuan-dospem/{id}/download', [AjuanDospemController::class, 'downloadPortofolio']);

            // 🔹 Bimbingan oleh dosen (SESUIAI FLUTTER)
            Route::get('/bimbingan/mahasiswa', [AjuanDospemController::class, 'getDaftarMahasiswa']);
            Route::post('/bimbingan/jadwal', [AjuanDospemController::class, 'createJadwalBimbingan']);
        });

        /*
        |--------------------------------------------------------------------------
        | AJUAN DOSPEM - MAHASISWA
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:mahasiswa')->group(function () {
            Route::get('/ajuan-dospem', [AjuanDospemController::class, 'index']);
            Route::get('/dospem-aktif', [AjuanDospemController::class, 'getDospemAktifMahasiswa']);
            Route::post('/ajuan-dospem', [AjuanDospemController::class, 'store']);
            Route::get(
                '/ajuan-dospem/dosen/prodi/{prodiId}',
                [AjuanDospemController::class, 'getDosenByProdi']
            );
        });
    });


    Route::prefix('program-studi')->group(function () {
            Route::get('/', [ProgramStudiController::class, 'index']);
            Route::get('/{id}', [ProgramStudiController::class, 'show']);
            Route::post('/', [ProgramStudiController::class, 'store']);
            Route::put('/{id}', [ProgramStudiController::class, 'update']);
            Route::delete('/{id}', [ProgramStudiController::class, 'destroy']);
        });


        /*
|--------------------------------------------------------------------------
| PASSWORD RESET (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1');

Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:5,1');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1');

// Pengumuman
Route::get('/pengumuman', [PengumumanController::class, 'index']);
Route::get('/pengumuman/download/{id}', [PengumumanController::class, 'download']);