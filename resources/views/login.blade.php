<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | InTA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #88BDF2, #384959);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            display: flex;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0,0,0,0.15);
            width: 950px;
            height: 520px;
            overflow: hidden;
        }

        .login-box {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-box {
            flex: 1;
            background: linear-gradient(180deg, #88BDF2, #384959);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-box img {
            width: 300px;
        }

        h2 {
            margin-bottom: 25px;
            color: #222;
            font-size: 30px;
            text-align: center;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        button {
            margin-top: 25px;
            width: 100%;
            padding: 14px;
            background: #384959;
            border: none;
            color: #fff;
            border-radius: 8px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #2c3c48;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="login-box">
            <h2>Login</h2>
            @if(session('error'))
            <div style="color:red; margin-bottom:10px;">
                {{ session('error') }}
            </div>
             @endif
            <form id="loginForm" method="POST" action="{{ route('auth') }}">
                @csrf
                <label for="nim">NIM</label>
                <input type="text" id="nim" name="username" placeholder="Masukkan NIM" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan Password" required>

                <button type="submit">Log In</button>
            </form>
        </div>
        <div class="logo-box">
             <img src="{{ asset('images/logo_inta_remove.png') }}" alt="Logo InTA">
        </div>
    </div>

    <script>
        // Saat login ditekan → arahkan ke dashboard
        // document.getElementById('loginForm').addEventListener('submit', function(e) {
        //     e.preventDefault();
        //     const nim = document.getElementById('nim').value.trim();
        //     const password = document.getElementById('password').value.trim();

        //     if (nim && password) {
        //         // Simulasi login berhasil → pindah ke dashboard
        //         window.location.href = "{{ route('dashboard') }}";
        //     } else {
        //         alert('Silakan isi NIM dan Password terlebih dahulu!');
        //     }
        // });
    </script>

</body>
</html>
