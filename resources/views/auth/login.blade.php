<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Sihitung RSUI</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <style>
        :root {
            --primary-color: #0F5DA6;
            --accent-orange: #f7941d;
            --body-font: 'IBM Plex Sans', sans-serif;
            --heading-font: 'IBM Plex Sans', sans-serif;
            --bg-color: #0b132b;
            --card-bg: rgba(21, 35, 75, 0.65);
            --text-color: #ffffff;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: var(--body-font);
            background: radial-gradient(circle at 10% 20%, rgba(15, 93, 166, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(247, 148, 29, 0.1) 0%, transparent 40%),
                        var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Floating Animated Blobs */
        .bg-blob {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(15, 93, 166, 0.22) 0%, rgba(15, 93, 166, 0) 70%);
            filter: blur(70px);
            z-index: -1;
            animation: float 25s infinite alternate ease-in-out;
        }
        .bg-blob-1 {
            top: 5%;
            left: 5%;
        }
        .bg-blob-2 {
            bottom: 5%;
            right: 5%;
            animation-delay: -7s;
            background: radial-gradient(circle, rgba(247, 148, 29, 0.12) 0%, rgba(247, 148, 29, 0) 70%);
        }
        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, -60px) scale(1.1); }
            100% { transform: translate(-40px, 50px) scale(0.9); }
        }

        .auth-wrapper {
            width: 100%;
            padding: 2rem 0;
            z-index: 1;
        }

        .auth-card {
            background: var(--card-bg) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color) !important;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.4) !important;
            border-radius: 0px !important;
            overflow: hidden;
        }

        .auth-left-wrapper {
            background-image: linear-gradient(135deg, rgba(15, 93, 166, 0.92), rgba(11, 19, 43, 0.96)), url('{{ asset("images/bg-gedung-rsui.jpeg") }}');
            background-size: cover;
            background-position: center;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: #ffffff;
            border-right: 1px solid var(--border-color);
        }

        .brand-overlay-content {
            animation: fadeInUp 0.8s ease-out;
        }

        .noble-logo {
            font-family: var(--heading-font);
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 1.5rem;
            display: inline-flex;
            align-items: center;
        }
        
        .noble-logo span {
            color: var(--accent-orange);
        }

        .noble-logo .logo-brand {
            color: #ffffff;
        }

        .form-control {
            border-radius: 0px !important;
            padding: 12px 16px !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.15) !important;
            font-size: 0.875rem;
            background-color: rgba(15, 25, 55, 0.5) !important;
            color: #ffffff !important;
            transition: all 0.3s ease !important;
            height: auto !important;
        }

        .form-control:focus {
            background-color: rgba(15, 25, 55, 0.7) !important;
            border-color: rgba(15, 93, 166, 0.5) !important;
            border-bottom-color: var(--accent-orange) !important;
            box-shadow: 0 0 15px rgba(247, 148, 29, 0.15) !important;
            outline: none !important;
            color: #ffffff !important;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.38) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            padding: 12px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            letter-spacing: 0.5px !important;
            border-radius: 0px !important;
            color: #ffffff !important;
            transition: all 0.2s ease !important;
        }
        
        .btn-primary:hover {
            background-color: #0c4e8a !important;
            border-color: #0c4e8a !important;
            transform: translateY(-1px);
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 6px;
        }

        /* CAPTCHA card */
        .captcha-container {
            background-color: rgba(15, 25, 55, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 6px;
            height: 50px;
        }

        .btn-refresh {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transition: all 0.2s ease;
            height: 50px;
            width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-refresh:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--accent-orange);
        }

        .alert {
            border-radius: 0px !important;
            font-size: 13px;
        }

        .alert-success {
            background-color: rgba(25, 128, 56, 0.18) !important;
            border-color: rgba(25, 128, 56, 0.3) !important;
            color: #a7f0ba !important;
        }

        .alert-danger {
            background-color: rgba(218, 30, 40, 0.18) !important;
            border-color: rgba(218, 30, 40, 0.3) !important;
            color: #ffb3b8 !important;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
    </style>
</head>

<body>
    <!-- Background blobs -->
    <div class="bg-blob bg-blob-1"></div>
    <div class="bg-blob bg-blob-2"></div>

    <div class="auth-wrapper">
        <div class="container d-flex justify-content-center align-items-center">
            <div class="col-md-9 col-lg-9 col-xl-10 mx-auto">
                <div class="card auth-card">
                    <div class="row w-100 mx-0">
                        <!-- Left Side Image Overlay -->
                        <div class="col-md-5 pe-md-0 d-none d-md-flex px-0">
                            <div class="auth-left-wrapper text-center">
                                <div class="brand-overlay-content">
                                    <img src="{{ asset('images/logorsui.png') }}" alt="logo" style="width: 80px; height: auto;" class="mb-4">
                                    <h3 class="fw-bold text-white mb-2" style="font-family: var(--heading-font); letter-spacing: -0.5px;">Sihitung RSUI</h3>
                                    <p class="text-white text-opacity-75 small mb-4" style="max-width: 250px;">Monitoring Data Klaim &amp; Laporan Kinerja DPJP Rumah Sakit Universitas Indonesia</p>
                                    <div class="badge bg-light bg-opacity-10 text-white border border-white border-opacity-10 px-3 py-2" style="border-radius: 999px !important;">
                                        <i data-feather="shield" class="me-1" style="width:14px;height:14px;"></i> Secure Portal
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side Form -->
                        <div class="col-md-7 ps-md-0">
                            <div class="auth-form-wrapper p-4 p-md-5">
                                <div class="noble-logo d-flex align-items-center">
                                    <img src="{{ asset('images/logorsui.png') }}" alt="logo" style="width: 44px; height: auto;" class="me-3 d-md-none">
                                    <span class="logo-brand">Sihitung<span>RSUI</span></span>
                                </div>
                                <h5 class="text-white text-opacity-75 fw-normal mb-4" style="font-size: 0.95rem;">Selamat Datang! Masuk ke akun Anda.</h5>
                                
                                @if (session('status'))
                                    <div class="alert alert-success d-flex align-items-center" role="alert">
                                        <i data-feather="check-circle" class="me-2" style="width: 16px; height: 16px;"></i> 
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i data-feather="alert-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                                        {{ $errors->first() }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('login') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" id="username" placeholder="Username / Email" value="{{ old('username') }}" required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" id="password" autocomplete="current-password" placeholder="Password" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">CAPTCHA SECURITY</label>
                                        <div class="d-flex align-items-center mb-2 gap-2">
                                            <div class="captcha-container d-flex align-items-center justify-content-center">
                                                <img id="captcha-img" src="{{ $captcha_image }}" alt="Captcha" style="height: 100%; filter: invert(1) contrast(150%);">
                                            </div>
                                            <button type="button" class="btn btn-refresh" onclick="refreshCaptcha()" title="Refresh Captcha">
                                                <i data-feather="refresh-cw" style="width: 16px; height: 16px;"></i>
                                            </button>
                                        </div>
                                        <input type="number" class="form-control" name="captcha" placeholder="Hasil penjumlahan" required>
                                    </div>

                                    <div class="form-check mb-4">
                                        <input type="checkbox" class="form-check-input" id="authCheck" style="background-color: rgba(15, 25, 55, 0.5); border-color: rgba(255, 255, 255, 0.2);">
                                        <label class="form-check-label text-white text-opacity-75 small" for="authCheck">
                                            Ingat Sesi
                                        </label>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary w-100 text-white shadow-sm">MASUK WEB</button>
                                    </div>
                                </form>
                                <div class="mt-5 text-center text-white text-opacity-50" style="font-size: 11px;">
                                    <p class="mb-1">&copy; Copyright {{ date('Y') }} | Rumah Sakit Universitas Indonesia. All Rights Reserved.</p>
                                    <p class="mb-0">Development SIMRS and TI</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        feather.replace();
        function refreshCaptcha() {
            fetch('{{ route('captcha.refresh') }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('captcha-img').src = data.captcha_image;
                })
                .catch(error => {
                    console.error('Error refreshing captcha:', error);
                });
        }
    </script>
</body>
</html>