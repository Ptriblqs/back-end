<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Informasi & Pengumuman | InTA</title> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #88BDF2; /* Biru Muda */
            --danger-color: #384959; /* Biru Tua/Abu-abu Tua */
            --header-bg: #1e3a8a; /* Biru Tua untuk Header Pengumuman */
            --save-color: #4CAF50; /* Hijau untuk Simpan */
            --cancel-color: #F44336; /* Merah untuk Batal/Hapus */
            --card-border: #e0e7ff; /* Border kartu pengumuman */
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            margin: 0;
            background-color: #f9f9f9;
        }

        /* --- SIDEBAR STYLES (Sama dengan sebelumnya) --- */
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

        /* --- TOPBAR STYLES (Sama dengan sebelumnya) --- */
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
            border-radius: 16px;
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
            margin-top: 140px;
            padding: 30px 40px;
        }

        /* --- HEADER PENGUMUMAN --- */
        .announcement-header {
            background-color: var(--header-bg);
            color: white;
            padding: 15px 25px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2em;
            font-weight: 700;
        }

        .announcement-header .controls {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .announcement-header i {
            font-size: 1.1em;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .announcement-header i:hover {
            opacity: 0.8;
        }

        .add-announcement-btn {
            background-color: var(--primary-color);
            color: var(--danger-color);
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-announcement-btn:hover {
            background-color: #6a96c9;
            color: white;
        }

        /* --- CARD PENGUMUMAN --- */
        .announcement-list {
            padding: 0;
            margin-top: 0;
            list-style: none;
            background-color: #fff;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            padding-top: 1px; /* Untuk mengatasi margin collapse */
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .announcement-card {
            border: 1px solid var(--card-border);
            border-radius: 8px;
            margin: 20px 25px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            background-color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .announcement-details h4 {
            margin-top: 0;
            margin-bottom: 5px;
            color: var(--header-bg);
            font-size: 1.1em;
            font-weight: 700;
        }

        .announcement-details .period {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 10px;
        }

        .announcement-details .period i {
            margin-right: 5px;
            color: var(--primary-color);
        }

        .announcement-details p {
            font-size: 0.95em;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .announcement-details .attachment {
            display: block;
            font-size: 0.9em;
            color: var(--primary-color);
            text-decoration: none;
            margin-top: 5px;
        }

        .announcement-details .attachment:hover {
            text-decoration: underline;
        }

        .card-actions button {
            background: none;
            border: none;
            color: #999;
            padding: 5px;
            margin-left: 10px;
            cursor: pointer;
            font-size: 1em;
            transition: color 0.2s;
        }

        .card-actions {
            display: flex;
            gap: 5px;
        }

        .card-actions button:hover {
            color: var(--header-bg);
        }

        .card-actions button[title="Hapus"]:hover {
            color: var(--cancel-color);
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

        .modal-content .close-btn {
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .modal-content .close-btn:hover {
            color: #333;
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
            color: var(--header-bg);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            font-weight: 700;
            font-size: 1.5em;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .date-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .date-row .form-group {
            flex: 1;
        }

        /* Custom file input appearance (untuk mencocokkan style) */
        .form-group input[type="file"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
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
            <a href="{{ route('dashboard') }}">Dashboard</a>> Informasi
        </div>
  <div class="user-info">
            <span>{{ auth()->user()->nama_lengkap }}<br><small>{{ auth()->user()->username }} | {{ auth()->user()->role }}</small></span>
            <img src="{{ asset('images/image.png') }}" alt="User Avatar">
        </div>
    </div>

    <div class="content">

        
        
        <div class="announcement-header">
            Pengumuman
            <div class="controls">
                <button class="add-announcement-btn" id="openModalBtn"><i class="fa-solid fa-plus"></i> Tambah Pengumuman</button>
            
            </div>
        </div>
        
        <ul class="announcement-btn">
            
            @foreach ($pengumuman as $item)
            <li class="announcement-card">
                <div class="announcement-details">
                    <h4>{{$item->judul}}</h4>
                    <div class="period"><i class="fa-solid fa-clock"></i> {{ \Carbon\Carbon::parse($item->tgl_mulai)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($item->tgl_selesai)->translatedFormat('d M Y') }}</div>
                    <p>{{$item->isi}}</p>
                </div>
                <div class="card-actions">
                    <button title="Edit" onclick="openEditModal('{{ $item->id }}')"><i class="fa-solid fa-pencil-alt"></i></button>
                    <button title="Hapus" onclick="openDeleteModal('{{ $item->id }}')"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </li>

            @endforeach

        </ul>

    </div>

    {{-- <button id="openModalBtn">Tambah Pengumuman</button> --}}

{{-- <div id="modalOverlay" class="modal-overlay">
  <div class="modal-box">

    @if(session('success'))
    <div style="color:green; margin-bottom:10px;">
        {{ session('success') }}
    </div>
    @endif
    <h2>Tambah Pengumuman</h2>

    <form id="announcementForm" method="POST" action="{{ route('informasi.store') }}">
        @csrf

        <div class="form-group">
            <label>Judul</label>
            <input type="text" name="judul" required>
        </div>

        <div class="form-group">
            <label>Isi</label>
            <textarea name="isi" required></textarea>
        </div>

        <div class="form-group">
            <label>Lampiran (opsional)</label>
            <input type="file" name="attachment">
        </div>

        <div class="buttons">
            <button type="button" id="closeModalBtn" class="close-btn">Batal</button>
            <button type="submit" class="save-btn">Simpan</button>
        </div>
    </form>

  </div> --}}

    {{-- modal lama --}}

        <div id="announcementModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Tambah Pengumuman Baru</h3>
            <form id="announcementForm" method="POST" action="{{ route('informasi.store') }}" enctype="multipart/form-data">
                @csrf
                
                <input type="hidden" id="announcementId" name="id">

                <div class="form-group">
                    <label for="judul">Judul Pengumuman</label>
                    <input type="text" id="judul" name="judul" placeholder="Judul Pengumuman" required>
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="isi" placeholder="Deskripsi" required></textarea>
                </div>

                <div class="date-row">
                    <div class="form-group">
                        <label for="tgl_mulai">Tanggal Mulai</label>
                        <input type="date" id="tgl_mulai" name="tgl_mulai" required>
                    </div>
                    <div class="form-group">
                        <label for="tgl_selesai">Tanggal Selesai</label>
                        <input type="date" id="tgl_selesai" name="tgl_selesai" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="lampiran">Lampiran (Opsional)</label>
                    <input type="file" id="attachment" name="attachment">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-batal" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-simpan" id="submitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

            <div id="editPengumumanModal" class="modal">
                <div class="modal-content">
                    <h3>Edit Pengumuman</h3>
                    <form id="editPengumumanForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" id="edit_pengumuman_id" name="id_pengumuman">
                        
                        <div class="form-group">
                            <label for="edit_judul">Judul</label>
                            <input type="text" id="edit_judul" name="judul" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_isi">Isi</label>
                            <textarea id="edit_isi" name="isi" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_tgl_mulai">Tanggal Mulai</label>
                            <input type="date" id="edit_tgl_mulai" name="tgl_mulai" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_tgl_selesai">Tanggal Selesai</label>
                            <input type="date" id="edit_tgl_selesai" name="tgl_selesai" required>
                        </div>

                        <div class="form-actions">
                            <button type="button" onclick="closeEditModal()">Batal</button>
                            <button type="submit">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="deletePengumumanModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus pengumuman ini?</p>
            <div class="form-actions">
                <button type="button" onclick="closeDeleteModal()">Batal</button>
                <button type="button" id="confirmDeleteBtn">Hapus</button>
            </div>
        </div>
    </div>


    <script>
        // Ambil elemen modal
  const modalOverlay = document.getElementById("modalOverlay");
  const openModalBtn = document.getElementById("openModalBtn");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const announcementModal = document.getElementById('announcementModal');

        // Simpan ID pengumuman yang akan dihapus
        let idToDelete = null;

        // --- Data Simulasi Pengumuman (Ganti dengan data backend) ---
        const dataPengumumanSimulasi = @json($pengumuman);
        console.log(dataPengumumanSimulasi);
        // const dataPengumumanSimulasi = {
        //     '1': { judul: 'Sidang TA I', deskripsi: 'Pelaksanaan sidang akan dijadwalkan jam 08:00 sampai dengan jam 12:00', tgl_mulai: '2025-07-28', tgl_selesai: '2025-07-28', lampiran: '' },
        //     '2': { judul: 'Pendaftaran Sidang TA I', deskripsi: 'Silahkan daftar melalui link yang tertera pada digrup bimbingan dosen masing-masing', tgl_mulai: '2025-06-22', tgl_selesai: '2025-06-30', lampiran: 'Screenshot_2025-06-23_at_13.55.46.png' },
        //     '3': { judul: 'Panduan Pengerjaan Tugas Akhir', deskripsi: 'Silahkan baca panduan berikut', tgl_mulai: '2025-05-23', tgl_selesai: '2025-06-30', lampiran: 'Panduan_Tugas_Akhir.pdf' },
        // };
        // ---------------------------------------------------------------------

        // FUNGSI UNTUK MEMBUKA MODAL TAMBAH
          openModalBtn.addEventListener("click", () => {
            announcementModal.style.display = 'flex';
            console.log(modalOverlay.classList);
        });

        // FUNGSI UNTUK MEMBUKA MODAL EDIT
            function openEditModal(id) {
        const pengumuman = @json($pengumuman); // pastikan array
        const item = pengumuman.find(p => p.id == id);
        if (!item) return;

        document.getElementById('edit_pengumuman_id').value = id;
        document.getElementById('edit_judul').value = item.judul;
        document.getElementById('edit_isi').value = item.isi;
        document.getElementById('edit_tgl_mulai').value = new Date(item.tgl_mulai).toISOString().split('T')[0];
        document.getElementById('edit_tgl_selesai').value = new Date(item.tgl_selesai).toISOString().split('T')[0];

        const form = document.getElementById('editPengumumanForm');
        form.action = `/informasi/${id}`; // sesuai route patch
        document.getElementById('editPengumumanModal').style.display = 'flex';
    }

        function closeEditModal() {
            document.getElementById('editPengumumanModal').style.display = 'none';
}

        // FUNGSI UNTUK MENUTUP MODAL TAMBAH/EDIT
           function closeModal() {
        announcementModal.style.display = "none";
        }

        // FUNGSI UNTUK MEMBUKA MODAL HAPUS
        let deleteId = null;

        function openDeleteModal(id) {
            deleteId = id;
            document.getElementById('deletePengumumanModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deletePengumumanModal').style.display = 'none';
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) {
                fetch(`/informasi/${deleteId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(() => location.reload());
            }

    function closeDeleteModal() {
        deleteModal.style.display = "none";
        idToDelete = null;
    }
    });

        // FUNGSI UNTUK MENUTUP MODAL HAPUS
        // function closeDeleteModal() {
        //     deleteModal.style.display = "none";
        //     idToDelete = null;
        // }   

        // Logika konfirmasi hapus (simulasi)
        // confirmDeleteBtn.onclick = function() {
        //     if (idToDelete) {
        //         alert('Pengumuman ID ' + idToDelete + ' berhasil dihapus (Simulasi)');
        //         // Hapus data dari DOM/array (di sini, hanya simulasi)
        //     }
        //     closeDeleteModal();
        // }


        // Logika submit form (Tambah/Edit)
        
        // public function store(Request $request)
        // {
        //     $request->validate([
        //         'judul' => 'required|string',
        //         'isi' => 'required|string',
        //         'attachment' => 'nullable|file'
        //     ]);

        //     Pengumuman::create([
        //         'user_id' => auth()->id(),
        //         'judul' => $request->judul,
        //         'isi' => $request->isi,
        //         'attachment' => $request->attachment ? $request->file('attachment')->store('lampiran') : null,
        //     ]);

        //     return redirect()->back()->with('success', 'Pengumuman berhasil ditambahkan');
        // }

        //logika submit lama

        // announcementForm.onsubmit = function(e) {
        //     e.preventDefault();

        //     const id = announcementIdInput.value;
        //     const action = id ? "diupdate" : "ditambahkan";

        //     const formData = new FormData(announcementForm);
        //     formData.append("id", id);

        //     // Logika AJAX/Fetch ke Backend akan diletakkan di sini

        //     fetch("{{ route('informasi.store') }}", {
        //     method: "POST",
        //     headers: {
        //     "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        //     },
        //     body: formData
        // })
        //  .then(response => response.json())
        //  .then(result => {
        //     alert(`Pengumuman berhasil ${action}`);
        //     closeModal();
        //     location.reload()
        //  })
        //  .catch(error => console.error(error))
        //     alert(`Pengumuman berhasil ${action} (Simulasi)`);
        //     closeModal();
        // }


        // Logika menutup modal ketika klik di luar area modal
        window.onclick = function(event) {
            if (event.target == announcementModal) {
                closeModal();
            }
            if (event.target == deletePengumumanModal) {
                closeDeleteModal();
            }
        }
    </script>

</body>
</html>
