<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title') - Sihitung RSUI</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Overpass:wght@300;400;600;700;800&family=Roboto:wght@300;400;500;700&display=swap"
    rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Feather Icons -->
  <script src="https://unpkg.com/feather-icons"></script>

  <style>
    :root {
      --primary-color: #0F5DA6;
      --secondary-color: #7987a1;
      --success-color: #05a34a;
      --info-color: #66d1d1;
      --warning-color: #fbbc06;
      --danger-color: #ff3366;
      --light-color: #e9ecef;
      --dark-color: #0b132b;
      --bg-color: #f9fafb;
      --sidebar-bg: #ffffff;
      --sidebar-color: #000000;
      --sidebar-hover-bg: #f4f5f7;
      --sidebar-hover-color: #0F5DA6;
      --header-bg: #ffffff;
      --card-bg: #ffffff;
      --text-color: #000000;
      --text-muted: #7987a1;
      --border-color: #e8ebf1;
      --body-font: 'Roboto', sans-serif;
      --heading-font: 'Overpass', sans-serif;
      --transition: all 0.3s ease;
    }

    [data-theme="dark"] {
      --bg-color: #0c1427;
      --sidebar-bg: #15234b;
      --sidebar-color: #e2e8f0;
      --sidebar-hover-bg: #1e2e5c;
      --sidebar-hover-color: #ffffff;
      --header-bg: #15234b;
      --card-bg: #15234b;
      --text-color: #e2e8f0;
      --dark-color: #ffffff;
      --border-color: #2b3c6e;
    }

    body {
      font-family: var(--body-font);
      background-color: var(--bg-color);
      color: var(--text-color);
      overflow-x: hidden;
      font-size: 0.875rem;
      transition: background-color 0.3s, color 0.3s;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      font-family: var(--heading-font);
      font-weight: 700;
      color: var(--dark-color);
    }

    a {
      text-decoration: none;
      color: var(--primary-color);
    }

    /* Layout Structure */
    .main-wrapper {
      display: flex;
      width: 100%;
      height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      background: var(--sidebar-bg);
      border-right: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      z-index: 999;
      transition: var(--transition);
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
    }

    .sidebar-header {
      height: 60px;
      display: flex;
      align-items: center;
      padding: 0 25px;
      border-bottom: 1px solid var(--border-color);
    }

    .sidebar-header .logo {
      font-family: var(--heading-font);
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--dark-color);
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
    }

    .sidebar-header .logo span {
      color: var(--primary-color);
    }

    .sidebar-header .logo .logo-text {
      color: var(--dark-color);
    }

    .sidebar-header .logo .logo-text span {
      color: var(--primary-color);
    }

    .sidebar-body {
      padding: 15px 0;
      overflow-y: auto;
      flex-grow: 1;
    }

    .nav-category {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-muted);
      padding: 15px 25px 5px;
      margin-bottom: 5px;
    }

    .nav-item {
      padding: 0 15px;
    }

    .nav-link {
      display: flex;
      align-items: center;
      padding: 10px 15px;
      color: var(--sidebar-color);
      font-weight: 500;
      border-radius: 6px;
      transition: var(--transition);
      margin-bottom: 2px;
    }

    .nav-link.active,
    .nav-link:hover {
      background: var(--sidebar-hover-bg);
      color: var(--sidebar-hover-color);
    }

    .nav-link.active .link-icon,
    .nav-link:hover .link-icon {
      color: var(--sidebar-hover-color);
    }

    .link-icon {
      width: 18px;
      height: 18px;
      margin-right: 15px;
      color: var(--secondary-color);
      transition: var(--transition);
    }

    .sidebar.open {
      margin-left: 0;
    }

    /* Folded Sidebar */
    body.sidebar-folded .sidebar {
      width: 70px;
    }

    body.sidebar-folded .sidebar .logo span,
    body.sidebar-folded .sidebar .logo .logo-text,
    body.sidebar-folded .sidebar .link-title,
    body.sidebar-folded .sidebar .nav-category {
      display: none;
    }

    body.sidebar-folded .sidebar-header {
      padding: 0;
      justify-content: center;
    }

    body.sidebar-folded .sidebar-header .logo {
      font-size: 1.2rem;
      justify-content: center;
      width: 100%;
    }

    body.sidebar-folded .sidebar-header .logo img {
      margin-right: 0 !important;
    }

    body.sidebar-folded .nav-item {
      padding: 0 5px;
    }

    body.sidebar-folded .nav-link {
      justify-content: center;
      padding: 10px 0;
    }

    body.sidebar-folded .link-icon {
      margin-right: 0;
    }

    body.sidebar-folded .page-wrapper {
      width: calc(100% - 70px);
      margin-left: 70px;
    }

    /* Page Wrapper */
    .page-wrapper {
      min-height: 100vh;
      width: calc(100% - 260px);
      margin-left: 260px;
      display: flex;
      flex-direction: column;
      transition: var(--transition);
    }

    /* Navbar */
    .navbar {
      height: 60px;
      background: var(--header-bg);
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 25px;
      position: sticky;
      top: 0;
      z-index: 998;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    .navbar-toggler-btn {
      background: transparent;
      border: none;
      color: var(--dark-color);
      cursor: pointer;
    }

    .navbar-toggler-btn svg {
      width: 20px;
      height: 20px;
    }

    .profile-dropdown a {
      color: var(--dark-color);
      font-weight: 500;
    }

    .profile-dropdown img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
    }

    /* Page Content */
    .page-content {
      padding: 25px;
      flex-grow: 1;
    }

    .page-title {
      font-size: 1.35rem;
      margin-bottom: 2px;
    }

    .page-breadcrumb {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 25px;
    }

    /* Cards */
    .card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
      margin-bottom: 25px;
      border: none;
    }

    .card-body {
      padding: 1.5rem;
    }

    .card-title {
      font-family: var(--heading-font);
      font-size: 1.05rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: var(--dark-color);
    }

    /* Buttons */
    .btn {
      font-weight: 500;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      font-size: 0.875rem;
      transition: var(--transition);
    }

    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-primary:hover {
      background-color: #4e5bf2;
      border-color: #4e5bf2;
      box-shadow: 0 4px 10px rgba(101, 113, 255, 0.3);
    }

    /* Stats Widget */
    .icon-box {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .icon-box-primary {
      background: rgba(101, 113, 255, 0.1);
      color: var(--primary-color);
    }

    .icon-box-success {
      background: rgba(5, 163, 74, 0.1);
      color: var(--success-color);
    }

    .icon-box-warning {
      background: rgba(251, 188, 6, 0.1);
      color: var(--warning-color);
    }

    .icon-box-danger {
      background: rgba(255, 51, 102, 0.1);
      color: var(--danger-color);
    }

    .stat-value {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 5px;
    }

    /* Tables */
    .table th {
      font-size: 0.75rem;
      text-transform: uppercase;
      font-weight: 600;
      color: var(--text-muted);
      border-top: none;
      border-bottom: 1px solid var(--border-color);
      padding: 12px 15px;
    }

    .table td {
      padding: 12px 15px;
      vertical-align: middle;
      border-bottom: 1px solid var(--border-color);
      color: var(--text-color);
    }

    /* Badges */
    .badge {
      padding: 5px 8px;
      font-weight: 500;
      border-radius: 4px;
      font-size: 0.75rem;
    }

    .badge-success {
      background: rgba(5, 163, 74, 0.1);
      color: var(--success-color);
    }

    .badge-danger {
      background: rgba(255, 51, 102, 0.1);
      color: var(--danger-color);
    }

    .badge-warning {
      background: rgba(251, 188, 6, 0.1);
      color: var(--warning-color);
    }

    .badge-primary {
      background: rgba(101, 113, 255, 0.1);
      color: var(--primary-color);
    }

    /* Footer */
    .footer {
      border-top: 1px solid var(--border-color);
      padding: 20px 25px;
      font-size: 0.8rem;
      color: var(--text-muted);
      background: var(--card-bg);
      display: flex;
      justify-content: space-between;
      align-items: center;
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

    .delay-100 {
      animation-delay: 100ms;
    }

    .delay-200 {
      animation-delay: 200ms;
    }

    .delay-300 {
      animation-delay: 300ms;
    }

    @media (max-width: 991px) {
      .sidebar {
        margin-left: -260px;
      }

      .page-wrapper {
        width: 100%;
        margin-left: 0;
      }

      .sidebar.open {
        margin-left: 0;
      }
    }

    /* =============================================
       DARK MODE OVERRIDES
       ============================================= */
    [data-theme="dark"] body {
      background-color: var(--bg-color);
      color: var(--text-color);
    }

    /* Tables */
    [data-theme="dark"] .table {
      color: var(--text-color);
      border-color: var(--border-color);
    }
    [data-theme="dark"] .table th {
      color: var(--text-muted);
      border-color: var(--border-color);
      background-color: var(--card-bg);
    }
    [data-theme="dark"] .table td {
      color: var(--text-color);
      border-color: var(--border-color);
    }
    [data-theme="dark"] .table-hover > tbody > tr:hover > * {
      background-color: rgba(255, 255, 255, 0.04);
      color: var(--text-color);
    }
    [data-theme="dark"] .table > :not(caption) > * > * {
      background-color: transparent;
      color: var(--text-color);
    }

    /* Cards */
    [data-theme="dark"] .card {
      background-color: var(--card-bg);
      border-color: var(--border-color);
    }
    [data-theme="dark"] .card-title,
    [data-theme="dark"] .card-header {
      color: var(--text-color);
      background-color: var(--card-bg);
      border-color: var(--border-color);
    }

    /* Modals */
    [data-theme="dark"] .modal-content {
      background-color: var(--card-bg);
      border-color: var(--border-color);
      color: var(--text-color);
    }
    [data-theme="dark"] .modal-header {
      background-color: var(--card-bg);
      border-color: var(--border-color);
      color: var(--text-color);
    }
    [data-theme="dark"] .modal-footer {
      background-color: var(--card-bg);
      border-color: var(--border-color);
    }
    [data-theme="dark"] .modal-title {
      color: var(--text-color);
    }
    [data-theme="dark"] .btn-close {
      filter: invert(1) grayscale(100%) brightness(200%);
    }

    /* Forms */
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
      background-color: #1e2e5c;
      border-color: var(--border-color);
      color: var(--text-color);
    }
    [data-theme="dark"] .form-control:focus,
    [data-theme="dark"] .form-select:focus {
      background-color: #1e2e5c;
      border-color: var(--primary-color);
      color: var(--text-color);
      box-shadow: 0 0 0 0.2rem rgba(101, 113, 255, 0.25);
    }
    [data-theme="dark"] .form-control::placeholder {
      color: var(--text-muted);
    }
    [data-theme="dark"] .form-label {
      color: var(--text-color);
    }
    [data-theme="dark"] .form-text {
      color: var(--text-muted);
    }
    [data-theme="dark"] .input-group-text {
      background-color: #1e2e5c;
      border-color: var(--border-color);
      color: var(--text-muted);
    }

    /* Dropdowns */
    [data-theme="dark"] .dropdown-menu {
      background-color: var(--card-bg);
      border-color: var(--border-color);
    }
    [data-theme="dark"] .dropdown-item {
      color: var(--text-color);
    }
    [data-theme="dark"] .dropdown-item:hover,
    [data-theme="dark"] .dropdown-item:focus {
      background-color: var(--sidebar-hover-bg);
      color: var(--text-color);
    }
    [data-theme="dark"] .dropdown-divider {
      border-color: var(--border-color);
    }
    [data-theme="dark"] .dropdown-header {
      color: var(--text-muted);
    }

    /* Buttons */
    [data-theme="dark"] .btn-outline-secondary {
      color: var(--text-muted);
      border-color: var(--border-color);
    }
    [data-theme="dark"] .btn-outline-secondary:hover {
      background-color: var(--sidebar-hover-bg);
      color: var(--text-color);
    }
    [data-theme="dark"] .btn-secondary {
      background-color: #2b3c6e;
      border-color: #2b3c6e;
      color: var(--text-color);
    }
    [data-theme="dark"] .btn-link {
      color: var(--text-color);
    }
    [data-theme="dark"] .btn-link:hover {
      color: var(--primary-color);
    }

    /* Badges */
    [data-theme="dark"] .badge.bg-light {
      background-color: #1e2e5c !important;
      color: var(--text-color) !important;
      border-color: var(--border-color) !important;
    }
    [data-theme="dark"] .badge.text-primary {
      color: #8b96ff !important;
    }
    [data-theme="dark"] .badge.text-info {
      color: #66d1d1 !important;
    }

    /* Alerts */
    [data-theme="dark"] .alert-success {
      background-color: rgba(5, 163, 74, 0.15);
      border-color: rgba(5, 163, 74, 0.3);
      color: #4ade80;
    }
    [data-theme="dark"] .alert-danger {
      background-color: rgba(255, 51, 102, 0.15);
      border-color: rgba(255, 51, 102, 0.3);
      color: #ff6b8a;
    }
    [data-theme="dark"] .alert-warning {
      background-color: rgba(251, 188, 6, 0.15);
      border-color: rgba(251, 188, 6, 0.3);
      color: #fcd34d;
    }

    /* Text utilities */
    [data-theme="dark"] .text-dark {
      color: var(--text-color) !important;
    }
    [data-theme="dark"] .text-muted {
      color: #8899bb !important;
    }
    [data-theme="dark"] .fw-bold {
      color: var(--text-color);
    }

    /* Profile dropdown name */
    [data-theme="dark"] .name.font-weight-bold {
      color: var(--text-color) !important;
    }

    /* Progress bar track */
    [data-theme="dark"] .progress {
      background-color: #2b3c6e;
    }

    /* Headings */
    [data-theme="dark"] h1,
    [data-theme="dark"] h2,
    [data-theme="dark"] h3,
    [data-theme="dark"] h4,
    [data-theme="dark"] h5,
    [data-theme="dark"] h6,
    [data-theme="dark"] .page-title {
      color: var(--text-color);
    }

    /* Navbar profile link */
    [data-theme="dark"] .profile-dropdown a {
      color: var(--text-color);
    }

    /* Navbar toggler */
    [data-theme="dark"] .navbar-toggler-btn {
      color: var(--text-color);
    }

    /* Footer */
    [data-theme="dark"] .footer {
      background-color: var(--card-bg);
      border-color: var(--border-color);
    }

    /* Scrollbar */
    [data-theme="dark"] ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    [data-theme="dark"] ::-webkit-scrollbar-track {
      background: var(--bg-color);
    }
    [data-theme="dark"] ::-webkit-scrollbar-thumb {
      background: #2b3c6e;
      border-radius: 3px;
    }
    [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-color);
    }

    /* Tab buttons (master data) */
    [data-theme="dark"] .tab-type-btn {
      color: var(--text-muted);
    }
    [data-theme="dark"] .tab-type-btn.active {
      color: var(--primary-color);
      background: rgba(15, 93, 166, 0.1);
    }
    [data-theme="dark"] .badge.bg-secondary {
      background-color: #2b3c6e !important;
      color: var(--text-muted) !important;
    }

    /* Border utility */
    [data-theme="dark"] .border,
    [data-theme="dark"] .border-bottom,
    [data-theme="dark"] .border-top {
      border-color: var(--border-color) !important;
    }

    /* Inline edit form input */
    [data-theme="dark"] .inline-edit-form input {
      background-color: #1e2e5c;
      border-color: var(--border-color);
      color: var(--text-color);
    }

    /* Page subtitle */
    [data-theme="dark"] p.text-muted {
      color: #8899bb !important;
    }
  </style>
  <link rel="stylesheet" href="{{ asset('css/rsui-theme.css') }}?v={{ time() }}">
  @yield('css')
</head>

<body>
  <div class="main-wrapper">

    <!-- Sidebar -->
    <nav class="sidebar">
      <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="logo">
          <img src="{{ asset('images/logorsui.png') }}" alt="logo" style="width: 32px; height: auto;" class="me-2">
          <span class="logo-text">Sihitung<span>RSUI</span></span>
        </a>
        <div class="sidebar-toggler not-active d-md-none ms-auto">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
      <div class="sidebar-body">
        <ul class="nav flex-column">
          <li class="nav-category">Main</li>
          <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
              <i class="link-icon" data-feather="box"></i>
              <span class="link-title">Dashboard</span>
            </a>
          </li>

          <li class="nav-category">Aplikasi</li>
          <li class="nav-item">
            <a href="{{ route('claim-records.index') }}"
              class="nav-link {{ request()->routeIs('claim-records.index') ? 'active' : '' }}">
              <i class="link-icon" data-feather="file-text"></i>
              <span class="link-title">Data Klaim</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="{{ route('claim-records.dpjp') }}"
              class="nav-link {{ request()->routeIs('claim-records.dpjp') ? 'active' : '' }}">
              <i class="link-icon" data-feather="activity"></i>
              <span class="link-title">Laporan DPJP</span>
            </a>
          </li>


          @if(auth()->check() && auth()->user()->role == 'administrator')
            <li class="nav-category">Pengaturan</li>
            <li class="nav-item">
              <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="link-icon" data-feather="users"></i>
                <span class="link-title">Manajemen Akun</span>
              </a>
            </li>
          @endif
        </ul>
      </div>
    </nav>
    <!-- End Sidebar -->

    <!-- Page Wrapper -->
    <div class="page-wrapper">

      <!-- Navbar -->
      <nav class="navbar">
        <button class="navbar-toggler-btn" id="sidebarToggler">
          <i data-feather="menu"></i>
        </button>

        <div class="navbar-content">
          <ul class="nav align-items-center">

            <li class="nav-item">
              <a class="nav-link" href="#" id="theme-toggler">
                <i data-feather="moon" id="theme-icon"></i>
              </a>
            </li>

            <li class="nav-item dropdown profile-dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img
                  src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=0F5DA6&color=fff"
                  alt="profile">
                <span class="ms-2 d-none d-md-inline-block">{{ auth()->user()->name ?? 'Administrator' }}</span>
              </a>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <div class="dropdown-header d-flex flex-column align-items-center mb-2">
                  <div class="figure mb-2">
                    <img
                      src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=0F5DA6&color=fff"
                      alt="" class="rounded-circle" width="60" height="60">
                  </div>
                  <div class="info text-center">
                    <p class="name font-weight-bold mb-0 text-dark">{{ auth()->user()->name ?? 'Administrator' }}</p>
                    <p class="email text-muted mb-0">{{ auth()->user()->role ?? 'Admin' }}</p>
                  </div>
                </div>
                <div class="dropdown-divider"></div>
                <ul class="list-unstyled p-1 mb-0">
                  <li class="dropdown-item py-2">
                    <form action="{{ route('logout') }}" method="POST">
                      @csrf
                      <button type="submit"
                        class="btn btn-link text-body p-0 m-0 w-100 text-start text-decoration-none">
                        <i data-feather="log-out" class="me-2 icon-md" style="width: 16px; height: 16px;"></i>
                        <span>Log Out</span>
                      </button>
                    </form>
                  </li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </nav>
      <!-- End Navbar -->

      <!-- Page Content -->
      <div class="page-content">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i data-feather="check-circle" class="me-2" style="width: 16px; height: 16px;"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i data-feather="alert-circle" class="me-2" style="width: 16px; height: 16px;"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @yield('content')
      </div>
      <!-- End Page Content -->

      <!-- Footer -->
      <footer class="footer">
        <p class="text-muted mb-0">&copy; Copyright {{ date('Y') }} | <b>Rumah Sakit Universitas Indonesia</b>. All
          Rights Reserved.</p>
        <p class="text-muted mb-0">Development SIMRS and TI</p>
      </footer>
      <!-- End Footer -->

    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    feather.replace();

    // Sidebar Minimize Toggler
    const sidebarToggler = document.getElementById('sidebarToggler');
    sidebarToggler.addEventListener('click', function () {
      if (window.innerWidth < 992) {
        document.querySelector('.sidebar').classList.toggle('open');
      } else {
        document.body.classList.toggle('sidebar-folded');
      }
    });

    // Dark Mode Toggler
    const themeToggler = document.getElementById('theme-toggler');
    const themeIcon = document.getElementById('theme-icon');

    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
      themeIcon.setAttribute('data-feather', 'sun');
    }
    feather.replace();

    themeToggler.addEventListener('click', (e) => {
      e.preventDefault();
      let theme = document.documentElement.getAttribute('data-theme');
      if (theme === 'dark') {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
        themeToggler.innerHTML = '<i data-feather="moon"></i>';
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        themeToggler.innerHTML = '<i data-feather="sun"></i>';
      }
      feather.replace();
    });
  </script>
  @yield('js')
</body>

</html>