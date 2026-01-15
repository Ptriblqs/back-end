<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 350px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #1e293b;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        button {
            width: 100%;
            background-color: #2563eb;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        .error {
            color: #dc2626;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .success {
            color: #16a34a;
            font-size: 13px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>

        <div id="message"></div>

        <form id="loginForm">
            <label for="role">Role</label>
            <select name="role" id="role" required>
                <option value="">-- Pilih Role --</option>
                <option value="admin">Admin</option>
                <option value="dosen">Dosen</option>
                <option value="mahasiswa">Mahasiswa</option>
            </select>

            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Masuk</button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const messageBox = document.getElementById('message');
            messageBox.innerHTML = '';

            const data = {
                role: document.getElementById('role').value,
                username: document.getElementById('username').value,
                password: document.getElementById('password').value
            };

            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (!response.ok) {
                    messageBox.innerHTML = `<div class="error">${result.message || 'Login gagal.'}</div>`;
                } else {
                    messageBox.innerHTML = `<div class="success">Login berhasil! Selamat datang, ${result.user?.username || ''}</div>`;
                    // Simpan token ke localStorage (opsional)
                    if (result.token) {
                        localStorage.setItem('token', result.token);
                    }
                    // Redirect ke halaman dashboard
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1200);
                }

            } catch (error) {
                messageBox.innerHTML = `<div class="error">Terjadi kesalahan koneksi.</div>`;
                console.error(error);
            }
        });
    </script>
</body>
</html>
