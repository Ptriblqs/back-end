<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | InTA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* Menggunakan style DOSEN.HTML untuk konsistensi */
        :root {
            --primary-color: #88BDF2; /* Biru Muda */
            --danger-color: #384959; /* Biru Tua/Abu-abu Tua */
            --save-color: #4CAF50; /* Hijau untuk Simpan */
            --cancel-color: #F44336; /* Merah untuk Batal/Hapus */
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            margin: 0;
            background-color: #f9f9f9;
        }

        /* --- SIDEBAR STYLES --- */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-color), var(--danger-color));
            position: fixed;
            left: 0;
            top: 0;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom-right-radius: 16px;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .logo {
            text-align: center;
            margin-top: 20px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            width: 80%;
        }

        .logo img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 20px;
        }

        .menu {
            width: 100%;
            padding-top: 15px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 18px;
            color: #fff;
            padding: 14px 24px;
            text-decoration: none;
            transition: 0.3s;
            font-size: 15px;
            border-radius: 10px;
            margin: 6px 12px;
        }

        .menu a i { width: 24px; font-size: 18px; text-align: center; }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,0.25);
            border-left: 4px solid #fff;
            transform: translateX(5px);
        }

        /* --- TOPBAR STYLES (Style Dosen.html) --- */
        .topbar {
            position: fixed;
            top: 30px;
            left: 300px;
            right: 40px;
            height: 90px;
            background: #88BDF2;
            color: var(--danger-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 999;
            border-radius: 16px; /* Sudut melengkung */
        }

        /* Breadcrumbs (Style Dosen.html) */
        .topbar .breadcrumbs {
            font-size: 14px;
            color: #555;
            font-weight: 600;
        }

        /* User Info dan Logout Button Group */
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: var(--danger-color);
            line-height: 1.2;
            font-size: 13px;
            text-align: right;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background-color: #5d7593;
        }

        /* --- CONTENT STYLES --- */
        .content {
            margin-left: 260px;
            margin-top: 120px; /* Disesuaikan dengan Topbar baru */
            padding: 30px 40px;
        }

        h2 {
            margin-bottom: 30px;
            color: #333;
        }

        /* ===== CARD DATA (Menggunakan style Dosen.html) ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card-stat {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s, background-color 0.3s;
        }

        .card-stat:hover {
            transform: translateY(-5px);
            background-color: #fcfcfc;
        }

        .stat-icon {
            font-size: 40px;
            color: var(--primary-color);
        }

        .stat-info h4 {
            margin: 0;
            font-size: 32px;
            color: var(--danger-color);
            text-align: right;
            font-weight: 700;
        }

        .stat-info p {
            margin: 0;
            font-size: 14px;
            color: #777;
            text-align: right;
            font-weight: 500;
        }

        /* ===== AKTIVITAS & PENGUMUMAN (Menggunakan style Dosen.html) ===== */
        .dashboard-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-top: 40px;
        }

        .card-info {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
        }

        .card-info h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #88BDF2;
        }

        .aktivitas table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .aktivitas th {
            text-align: left;
            color: #384959;
            font-weight: 600;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--primary-color);
        }

        .aktivitas td {
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
            color: #444;
            vertical-align: top;
        }

        .pengumuman-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .pengumuman-item:last-child {
            border-bottom: none;
        }

        .pengumuman-item strong {
            color: #333;
            display: block;
        }

        .pengumuman-item p {
            color: #666;
            font-size: 0.9rem;
            margin: 3px 0 0;
        }

        .lihat-semua {
            display: block;
            text-align: right;
            color: #0047ff;
            font-weight: 600;
            margin-top: 10px;
            text-decoration: none;
        }

        .lihat-semua:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <img src="{{ asset('images/logo_inta_remove.png') }}" alt="Logo InTA">
        </div>

        <div class="menu">
            <a href="{{ route('dashboard') }}" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>

            <a href="{{ route('dosen') }}"><i class="fa-solid fa-user-tie"></i> Kelola Dosen</a>

            <a href="{{ route('mahasiswa') }}"><i class="fa-solid fa-user-graduate"></i> Kelola Mahasiswa</a>
            <a href="{{ route('informasi') }}"><i class="fa-solid fa-bullhorn"></i> Informasi</a>
        </div>
    </div>

    <div class="topbar">
        <div class="breadcrumbs">
            Dashboard
        </div>
        <div class="user-info">
            <span><strong>{{ auth()->user()->nama_lengkap }}</strong><br><small>{{ auth()->user()->username }} | {{ auth()->user()->role }}</small></span>
            <button class="logout-btn" onclick="logout()">Keluar</button>
        </div>
    </div>

    <div class="content">
        <h2>Selamat Datang, {{ auth()->user()->nama_lengkap }}!</h2>

        <div class="dashboard-grid">

            <div class="card-stat">
                <div class="stat-icon"><i class="fa-solid fa-user-tie"></i></div>
                <div class="stat-info">
                    <h4>{{ $totalDosen }}</h4>
                    <p>Total Dosen Pembimbing</p>
                </div>
            </div>

            <div class="card-stat">
                <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h4>{{ $totalMahasiswa }}</h4>
                    <p>Total Mahasiswa</p>
                </div>
            </div>

            <div class="card-stat">
                <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                <div class="stat-info">
                    <h4>{{ $totalTugasAkhir }}</h4>
                    <p>Total Judul TA</p>
                </div>
            </div>

            <div class="card-stat">
                <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="stat-info">
                    <h4>{{ $totalProdi }}</h4>
                    <p>Total Prodi</p>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="card-info aktivitas">
                <h3>Aktivitas Terbaru <i class="fa-solid fa-clock-rotate-left"></i></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Datetime</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- <tr>
                            <td>Rahayu Suci</td>
                            <td>Menambahkan pengguna baru: Alya Putri (4342401030)</td>
                            <td>11 Okt 2025, 15:21</td>
                        </tr>
                        <tr>
                            <td>Putri Balqis</td>
                            <td>Menghapus pengguna: Tengku Radhi (4342411074)</td>
                            <td>29 Okt 2025, 14:19</td>
                        </tr>
                        <tr>
                            <td>Miftahur Rahma</td>
                            <td>Menambahkan Dosen Pembimbing: Haqqi Ghafur (4342401028)</td>
                            <td>05 Nov 2025, 15:18</td>
                        </tr>
                        <tr>
                            <td>Juan Fadhil</td>
                            <td>Mengupload Jadwal Sidang</td>
                            <td>10 Nov 2025, 13:17</td>
                        </tr> --}}
                        @foreach ($activityLogs as $log)
                            <tr>
                                <td>{{ $log->user->nama_lengkap }}</td>
                                <td>{{ $log->activity }}</td>
                                <td>{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-info pengumuman">
                <h3>Pengumuman Terbaru <i class="fa-solid fa-bell"></i></h3>
                @foreach ($pengumuman as $item)
                <div class="informasi">
                    <strong>{{ $item->judul }}</strong>
                    <p>Periode: {{ \Carbon\Carbon::parse($item->tgl_mulai)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($item->tgl_selesai)->translatedFormat('d M Y') }}</p>
                </div>
                @endforeach
                {{-- <div class="pengumuman-item">
                    <strong>Pendaftaran Sidang TA I</strong>
                    <p>Periode: 01 Jul 2025 – 30 Jul 2025</p>
                </div>
                <div class="pengumuman-item">
                    <strong>Panduan Pengerjaan Tugas Akhir</strong>
                    <p>Periode: 20 Jun 2025 – 30 Jun 2025</p>
                </div> --}}
                <a href="{{ route('informasi') }}" class="lihat-semua">Lihat Semua →</a>
            </div>
        </div>
    </div>

    <script>
        // FUNGSI LOGOUT MENGGUNAKAN LARAVEL ROUTING
        function logout() {
            alert('Anda telah keluar.');
            fetch("{{ route('logout') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': "application/json",
                }
            }).then(() => {
                window.location.href = "{{ route('login') }}";
            })
        }
    </script>

</body>
</html>
