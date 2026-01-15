<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mahasiswa | InTA</title> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
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

        /* --- TOPBAR STYLES --- */
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
            border-radius: 16px; /* Simplifikasi border-radius */
        }

        .topbar .breadcrumbs {
            font-size: 14px;
            color: #555;
            font-weight: 600;
        }

        .topbar .breadcrumbs a {
            text-decoration: none;
            color: var(--danger-color);
            margin-right: 5px;
        }

        .topbar .breadcrumbs a:hover {
            text-decoration: underline;
        }

        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar .user-info img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* --- CONTENT STYLES --- */
        .content {
            margin-left: 260px;
            margin-top: 140px; /* Diperbesar agar tidak tertutup topbar */
            padding: 30px 40px;
        }

        .card-info {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Header Kontrol Tabel */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-controls .input-group {
            position: relative;
        }

        .search-controls .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-controls .input-group input {
            padding: 10px 15px;
            padding-left: 40px; /* Ruang untuk ikon */
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 300px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .search-controls .input-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }


        .add-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-button:hover {
            background-color: #6a96c9;
        }

        .rows-per-page {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        /* --- TABLE STYLES --- */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 14px;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        thead tr:first-child th:first-child { border-top-left-radius: 10px; }
        thead tr:first-child th:last-child { border-top-right-radius: 10px; }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #f5f5f5; }

        .action-buttons button {
            background: none;
            color: var(--danger-color);
            border: none;
            padding: 5px;
            margin: 0 3px;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.2s;
        }

        .action-buttons button:hover {
            color: var(--primary-color);
        }

        .action-buttons button[title="Hapus"]:hover {
            color: var(--cancel-color);
        }


        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 15px;
            gap: 5px;
        }

        .pagination button {
            background-color: #fff;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
            font-size: 14px;
        }

        .pagination button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination button:not(.active):hover {
            background-color: #f0f0f0;
        }

        /* --- MODAL STYLES --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 600px;
            animation: fadeIn 0.3s;
        }

        #deleteModal .modal-content {
            max-width: 400px;
            text-align: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 25px;
            color: var(--danger-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            font-weight: 600;
            font-size: 1.5em;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
        }


        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        #deleteModal .form-actions {
            justify-content: center;
        }

        .form-actions button {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .form-actions .btn-batal {
            background-color: var(--cancel-color);
            color: white;
        }

        .form-actions .btn-batal:hover {
            background-color: #d32f2f;
        }

        .form-actions .btn-simpan {
            background-color: var(--save-color);
            color: white;
        }

        .form-actions .btn-hapus-modal {
            background-color: var(--cancel-color) !important;
        }

        .form-actions .btn-hapus-modal:hover {
            background-color: #d32f2f !important;
        }


        .form-actions .btn-simpan:hover {
            background-color: #388E3C;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <img src="{{ asset('images/logo_inta_remove.png') }}" alt="Logo InTA">
        </div>

        <div class="menu">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            <a href="{{ route('dosen') }}" class="{{ request()->routeIs('dosen') ? 'active' : '' }}">
                <i class="fa-solid fa-user-tie"></i> Kelola Dosen
            </a>
            <a href="{{ route('mahasiswa') }}" class="{{ request()->routeIs('mahasiswa') ? 'active' : '' }}">
                <i class="fa-solid fa-user-graduate"></i> Kelola Mahasiswa
            </a>
            <a href="{{ route('informasi') }}" class="{{ request()->routeIs('informasi') ? 'active' : '' }}">
                <i class="fa-solid fa-bullhorn"></i> Informasi
            </a>
        </div>

    </div>

    <div class="topbar">
        <div class="breadcrumbs">
            <a href="{{ route('dashboard') }}">Dashboard</a>> Kelola Mahasiswa
        </div>
  <div class="user-info">
            <span>{{ auth()->user()->nama_lengkap }}<br><small>{{ auth()->user()->username }} | {{ auth()->user()->role }}</small></span>
            <img src="{{ asset('images/image.png') }}" alt="User Avatar">
        </div>
    </div>

    <div class="content">

        <div class="card-info">
            <h2><i class="fa-solid fa-user-graduate"></i> Daftar Mahasiswa</h2>
            <hr>
            <div class="table-header">
                <div class="search-controls">
                    <button class="add-button" id="openModalBtn"><i class="fa-solid fa-plus"></i> Tambah Mahasiswa</button>
                    <div class="input-group">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" placeholder="Cari Mahasiswa...">
                    </div>
                </div>
                <select class="rows-per-page">
                    <option value="10">10 v</option>
                    <option value="25">25 v</option>
                    <option value="50">50 v</option>
                </select>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIM</th> <th>Nama</th>
                        <th>Program Studi</th> <th>Dosen Pembimbing</th> <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mahasiswa as $item)

                        <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->nim ?? '-'}}</td>
                        <td>{{ $item->user->nama_lengkap ?? '-'}}</td>
                        <td>{{ $item->programStudi->nama_prodi ?? '-'}}</td>
                        <td>{{ $item->tugasAkhir?->dosen?->user?->nama_lengkap ?? '-' }}</td>
                        <td class="action-buttons">
                                    <button class="btn-edit" data-id="{{ $item->id }}" 
                                    data-nim="{{ $item->nim }}" 
                                    data-nama="{{ $item->user->nama_lengkap }}" 
                                    data-prodi_id="{{ $item->prodi_id }}"
                                    data-prodi_nama="{{ $item->programStudi->nama_prodi ?? '' }}"
                                    data-dosen_id="{{ $item->tugasAkhir?->dosen?->id ?? '' }}"
                                    data-dosen_nama="{{ $item->tugasAkhir?->dosen?->user?->nama_lengkap ?? '' }}">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                            <button class="btn-delete" data-id="{{ $item->id }}" data-nama="{{ $item->user->nama_lengkap }}" title="Hapus">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>

                    @endforeach
                    {{-- <tr>
                        <td>201</td>
                        <td>331230001</td>
                        <td>Rudi Hermawan</td>
                        <td>Teknik Informatika</td>
                        <td>Sukma Evadini S.T., M.Kom.</td>
                        <td class="action-buttons">
                            <button title="Edit" onclick="openEditModal('201')"><i class="fa-solid fa-edit"></i></button>
                            <button title="Hapus" onclick="openDeleteModal()"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>202</td>
                        <td>331230002</td>
                        <td>Siti Aisyah</td>
                        <td>Sistem Informasi</td>
                        <td>Radhi S.Kom</td>
                        <td class="action-buttons">
                             <button title="Edit" onclick="openEditModal('202')"><i class="fa-solid fa-edit"></i></button>
                            <button title="Hapus" onclick="openDeleteModal()"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>203</td>
                        <td>331230003</td>
                        <td>Bambang Wijaya</td>
                        <td>Teknik Informatika</td>
                        <td>Alya S.Kom., M.Kom</td>
                        <td class="action-buttons">
                             <button title="Edit" onclick="openEditModal('203')"><i class="fa-solid fa-edit"></i></button>
                            <button title="Hapus" onclick="openDeleteModal()"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>204</td>
                        <td>331230004</td>
                        <td>Dewi Sartika</td>
                        <td>Sistem Informasi</td>
                        <td>Dian Pratiwi S.E., M.M.</td>
                        <td class="action-buttons">
                            <button title="Edit" onclick="openEditModal('204')"><i class="fa-solid fa-edit"></i></button>
                            <button title="Hapus" onclick="openDeleteModal()"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr> --}}
                </tbody>
            </table>

            <div class="pagination">
                <button title="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="active">1</button>
                <button title="Berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
            </div>

        </div>
    </div>

    <div id="tambahMahasiswaModal" class="modal">
        <div class="modal-content">
            <h3>Tambah Mahasiswa</h3> 
            <form id="tambahMahasiswaForm" method="POST" action="{{ route('mahasiswa.store') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="nim">NIM</label> 
                        <input type="text" id="nim" name="nim" placeholder="Masukkan NIM" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Masukkan Nama Lengkap" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="prodi_id">Program Studi</label> <select id="prodi_id" name="prodi_id" required>
                            <option value="">Pilih Program Studi</option>
                            @foreach ($prodi as $item)
                            <option value="{{ $item->id }}">{{ $item->nama_prodi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dosen_pembimbing">Dosen Pembimbing</label> 
                        <select id="dosen_pembimbing" name="dosen_pembimbing" required>
                            <option value="">Pilih Dosen</option>

                            @foreach ($dosen as $item)
                             <option value="{{ $item->id }}">
                            {{ $item->user->nama_lengkap }}
                            </option>
                            @endforeach

                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-batal" id="closeAddModalBtn">Batal</button>
                    <button type="submit" class="btn-simpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editMahasiswaModal" class="modal">
        <div class="modal-content">
            <h3>Edit Mahasiswa</h3> 
            <form id="editMahasiswaForm" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" id="edit_mahasiswa_id" name="id_mahasiswa">

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_nim">NIM</label>
                        <input type="text" id="edit_nim" name="nim" value="{{ old('nim') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="edit_nama_lengkap" name="nama_lengkap" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_prodi">Program Studi</label>
                        <select id="edit_prodi" name="prodi_id" required>
                            <option value="">Pilih Program Studi</option>
                            @foreach($prodi as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_prodi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_dosen_pembimbing">Dosen Pembimbing</label>
                        <select id="edit_dosen_pembimbing" name="id_dosen">
                            <option value="">Pilih Dosen</option>
                            @foreach($dosen as $d)
                                <option value="{{ $d->id }}">{{ $d->user->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-batal" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn-simpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>


    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Hapus</h3>
            <p id="deleteMessage">Anda yakin ingin menghapus data mahasiswa ini? Aksi ini tidak dapat dibatalkan.</p>
            <div class="form-actions">
                <button type="button" class="btn-batal" onclick="closeDeleteModal()">Batal</button>
                <form id="deleteMahasiswaForm" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-simpan">Hapus</button>
                </form>
            </div>
        </div>
    </div>


    <script>
        // Ambil elemen modal
        var addMahasiswaModal = document.getElementById("tambahMahasiswaModal");
        var editMahasiswaModal = document.getElementById("editMahasiswaModal");
        var deleteModal = document.getElementById("deleteModal");

        // Ambil tombol yang membuka modal Tambah Mahasiswa
        var openAddModalBtn = document.getElementById("openModalBtn");

        // Ambil tombol Batal dari modal Tambah Mahasiswa
        var closeAddModalBtn = document.getElementById("closeAddModalBtn");

        // Tombol konfirmasi hapus
        var confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

        // --- Data Simulasi Mahasiswa ---
        const dataMahasiswaSimulasi = {
            '201': { nim: '331230001', nama: 'Rudi Hermawan', prodi: 'ti', dosen: '101' },
            '202': { nim: '331230002', nama: 'Siti Aisyah', prodi: 'si', dosen: '102' },
            '203': { nim: '331230003', nama: 'Bambang Wijaya', prodi: 'ti', dosen: '103' },
            '204': { nim: '331230004', nama: 'Dewi Sartika', prodi: 'si', dosen: '104' },
        };
        // ---------------------------------------------------------------------

        // Logika membuka modal Tambah Mahasiswa
        openAddModalBtn.onclick = function() {
            addMahasiswaModal.style.display = "flex";
            
        }

        // Logika menutup modal Tambah Mahasiswa
        closeAddModalBtn.onclick = function() {
            addMahasiswaModal.style.display = "none";
        }

        // FUNGSI UNTUK MODAL EDIT MAHASISWA
// buka modal dan isi value
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nim = this.dataset.nim;
            const nama_lengkap = this.dataset.nama;
            const prodi_nama = this.dataset.prodi_nama;
            const prodi_id = this.dataset.prodi_id;
            const dosen_id = this.dataset.dosen_id;
            const dosen_nama = this.dataset.dosen_nama;

            document.getElementById('edit_mahasiswa_id').value = id;
            document.getElementById('edit_nim').value = nim;
            document.getElementById('edit_nama_lengkap').value = nama_lengkap;
            document.getElementById('edit_prodi').value = prodi_id;
            document.getElementById('edit_dosen_pembimbing').value = dosen_id;

            document.getElementById('editMahasiswaModal').style.display = 'flex';

            // ubah action form
            document.getElementById('editMahasiswaForm').action = `/mahasiswa/${id}`;
            });
        });

        // FUNGSI UNTUK MENUTUP MODAL EDIT MAHASISWA
        function closeEditModal() {
            document.getElementById('editMahasiswaModal').style.display = 'none';
        }


        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nama = this.dataset.nama;

                // Set pesan di modal
                document.getElementById('deleteMessage').textContent = `Anda yakin ingin menghapus mahasiswa "${nama}"?`;

                // Set action form delete
                const form = document.getElementById('deleteMahasiswaForm');
                form.action = `/mahasiswa/${id}`;

                // Tampilkan modal
                document.getElementById('deleteModal').style.display = 'flex';
            });
        });

        // Tutup modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // // Fungsi untuk membuka modal Hapus
        // function openDeleteModal() {
        //     deleteModal.style.display = "flex";
        // }

        // // Fungsi untuk menutup modal Hapus
        // function closeDeleteModal() {
        //     deleteModal.style.display = "none";
        // }

        // // Logika konfirmasi hapus (simulasi)
        // confirmDeleteBtn.onclick = function() {
        //     alert('Data Mahasiswa berhasil dihapus (Simulasi)');
        //     closeDeleteModal();
        // }


        // Logika menutup modal ketika klik di luar area modal
        window.onclick = function(event) {
            if (event.target == addMahasiswaModal) {
                addMahasiswaModal.style.display = "none";
            }
            if (event.target == editMahasiswaModal) {
                editMahasiswaModal.style.display = "none";
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = "none";
            }
        }

        // Opsional: Handle submit form edit
        document.getElementById('editMahasiswaForm').addEventListener('submit', function(e) {
            const idToUpdate = document.getElementById('edit_mahasiswa_id').value;
            console.log("Form Edit disubmit untuk ID: " + idToUpdate);
            alert('Data Mahasiswa ID ' + idToUpdate + ' berhasil diupdate (Simulasi)');
            closeEditModal();
        });

        // Opsional: Handle submit form tambah mahasiswa
        document.querySelector('#tambahMahasiswaModal form').addEventListener('submit', function(e) {
            alert('Data Mahasiswa baru berhasil ditambahkan (Simulasi)');
            // this.reset();
            addMahasiswaModal.style.display = "none";
        });

    </script>

</body>
</html>
