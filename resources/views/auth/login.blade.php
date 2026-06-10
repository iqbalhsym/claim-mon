<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Claim RSUI</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/rsui-theme.css') }}?v={{ time() }}">
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <style>
        :root {
            --primary-color: #0F5DA6;
            --body-font: 'IBM Plex Sans', sans-serif;
            --heading-font: 'IBM Plex Sans', sans-serif;
        }

        body {
            font-family: var(--body-font);
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .auth-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .auth-left-wrapper {
            background-image: url('{{ asset("images/bg-gedung-rsui.jpeg") }}');
            background-size: cover;
            background-position: center;
            width: 100%;
        }

        .auth-card {
            box-shadow: 0 8px 24px rgba(15, 93, 166, 0.08);
            border: 1px solid var(--border-color);
            border-radius: 0px;
            overflow: hidden;
            background-color: var(--card-bg);
        }

        .noble-logo {
            font-family: var(--heading-font);
            font-size: 2rem;
            font-weight: 800;
            color: #0b132b;
            letter-spacing: -0.5px;
            margin-bottom: 2rem;
        }
        
        .noble-logo span {
            color: var(--primary-color);
        }

        .form-control {
            border-radius: 0px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-bottom: 2px solid var(--border-color);
            font-size: 0.875rem;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .form-control:focus {
            border-bottom-color: var(--primary-color);
            box-shadow: 0 2px 0 0 var(--primary-color);
            background-color: var(--card-bg);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 0px;
            color: #ffffff;
        }
        
        .btn-primary:hover {
            background-color: #0a4a87;
            border-color: #0a4a87;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">
        <div class="container d-flex justify-content-center align-items-center">
            <div class="col-md-9 col-lg-8 col-xl-10 mx-auto">
                <div class="card auth-card">
                    <div class="row w-100 mx-0">
                        <!-- Left Side Image -->
                        <div class="col-md-5 pe-md-0 d-none d-md-flex px-0">
                            <div class="auth-left-wrapper"></div>
                        </div>
                        
                        <!-- Right Side Form -->
                        <div class="col-md-7 ps-md-0">
                            <div class="auth-form-wrapper p-4 p-md-5">
                                <a href="#" class="noble-logo d-flex align-items-center text-decoration-none">
                                    <img src="{{ asset('images/logorsui.png') }}" alt="logo" style="width: 48px; height: auto;" class="me-3">
                                    <span>Claim RSUI</span>
                                </a>
                                <h5 class="text-muted fw-normal mb-4">Selamat Datang! Masuk ke akun Anda.</h5>
                                
                                @if (session('status'))
                                    <div class="alert alert-success d-flex align-items-center" role="alert">
                                        <i data-feather="check-circle" class="me-2 text-success"></i> 
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i data-feather="alert-circle" class="me-2 text-danger"></i>
                                        {{ $errors->first() }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('login') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" id="username" placeholder="Username / Email" value="{{ old('username') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" id="password" autocomplete="current-password" placeholder="Password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">CAPTCHA SECURITY</label>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="border rounded p-1 bg-light d-flex align-items-center" style="height: 50px;">
                                                <img id="captcha-img" src="{{ $captcha_image }}" alt="Captcha" style="height: 100%;">
                                            </div>
                                            <button type="button" class="btn btn-link text-primary ms-2" onclick="refreshCaptcha()">
                                                <i data-feather="refresh-cw"></i>
                                            </button>
                                        </div>
                                        <input type="number" class="form-control" name="captcha" placeholder="Hasil penjumlahan" required>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="authCheck">
                                        <label class="form-check-label" for="authCheck">
                                            Ingat Sesi
                                        </label>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary w-100 text-white shadow-sm">MASUK WEB</button>
                                    </div>
                                </form>
                                <div class="mt-5 text-center text-muted">
                                    <p class="small mb-1">&copy; Copyright {{ date('Y') }} | Rumah Sakit Universitas Indonesia. All Rights Reserved.</p>
                                    <p class="small mb-0">Development SIMRS and TI</p>
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