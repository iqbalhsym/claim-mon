@extends('layouts.noble_layout')

@section('title', 'Geografi Pasien')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  #map {
    height: 540px;
    width: 100%;
    border-radius: 10px;
    background: #e8f0fe;
    z-index: 1;
  }

  .filter-bar {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }

  .filter-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
  }

  .filter-select {
    min-width: 200px;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    background: var(--bg-color);
    color: var(--text-color);
    font-size: 0.875rem;
    padding: 7px 12px;
    transition: border-color 0.2s;
  }

  .filter-select:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(15, 93, 166, 0.15);
  }

  .stat-mini-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
  }

  .stat-mini-icon {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .stat-mini-val {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-color);
    line-height: 1.1;
  }

  .stat-mini-label {
    font-size: 0.78rem;
    color: var(--text-muted);
    font-weight: 500;
    margin-top: 2px;
  }

  #info-panel {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 18px 20px;
    margin-top: 16px;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    animation: fadeSlideIn 0.3s ease;
  }

  @keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .info-panel-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .info-kota-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.83rem;
  }

  .info-kota-table th {
    text-align: left;
    color: var(--text-muted);
    font-weight: 600;
    border-bottom: 1px solid var(--border-color);
    padding: 6px 10px 6px 0;
  }

  .info-kota-table td {
    padding: 7px 10px 7px 0;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-color);
    vertical-align: middle;
  }

  .info-kota-table tr:last-child td { border-bottom: none; }

  .badge-count {
    background: rgba(15, 93, 166, 0.12);
    color: var(--primary-color);
    border-radius: 20px;
    padding: 3px 10px;
    font-weight: 700;
    font-size: 0.82rem;
  }

  .prov-table-wrap {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
  }

  .prov-table-head {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .prov-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.83rem;
  }

  .prov-table th {
    background: var(--bg-color);
    padding: 10px 16px;
    font-weight: 600;
    color: var(--text-muted);
    font-size: 0.75rem;
    text-transform: uppercase;
  }

  .prov-table td {
    padding: 10px 16px;
    border-top: 1px solid var(--border-color);
    color: var(--text-color);
    vertical-align: middle;
  }

  .prov-table tr:hover td {
    background: var(--sidebar-hover-bg);
    cursor: pointer;
  }

  .rank-badge {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bg-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
  }

  .rank-badge.top1 { background: #ffd700; color: #7d6000; }
  .rank-badge.top2 { background: #c0c0c0; color: #555; }
  .rank-badge.top3 { background: #cd7f32; color: #fff; }

  .prog-bar-wrap {
    height: 6px;
    background: var(--border-color);
    border-radius: 3px;
    overflow: hidden;
    margin-top: 4px;
  }

  .prog-bar-fill {
    height: 100%;
    border-radius: 3px;
    background: var(--primary-color);
    transition: width 0.6s ease;
  }

  .leaflet-popup-content-wrapper {
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    font-family: 'Roboto', sans-serif;
  }

  .custom-popup h6 {
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: #0b132b;
  }

  .custom-popup .pop-count {
    font-size: 1.4rem;
    font-weight: 800;
    color: #e53e3e;
  }

  .custom-popup .pop-label {
    font-size: 0.78rem;
    color: #666;
  }
</style>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin mb-4 fade-in-up">
  <div>
    <h4 class="mb-1 page-title">Geografi Pasien</h4>
    <p class="text-muted mb-0">Visualisasi persebaran pasien RSUI berdasarkan provinsi dan kabupaten/kota.</p>
  </div>
  <div class="d-flex align-items-center flex-wrap text-nowrap gap-2">
    <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importGeoModal">
      <i data-feather="upload" style="width:14px;height:14px;" class="me-1"></i> Import Data
    </button>
    <a href="{{ route('patient-geography.export') }}" class="btn btn-outline-info btn-sm">
      <i data-feather="download" style="width:14px;height:14px;" class="me-1"></i> Export Data
    </a>
  </div>
</div>

<div class="row mb-3 fade-in-up">
  <div class="col-6 col-md-2">
    <div class="stat-mini-card">
      <div class="stat-mini-icon icon-box-primary">
        <i data-feather="users" style="width:20px;height:20px;color:var(--primary-color)"></i>
      </div>
      <div>
        <div class="stat-mini-val">{{ number_format($totalPasien) }}</div>
        <div class="stat-mini-label">Total Pasien</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="stat-mini-card">
      <div class="stat-mini-icon" style="background:rgba(16, 185, 129, 0.12);">
        <i data-feather="check-circle" style="width:20px;height:20px;color:#10b981"></i>
      </div>
      <div>
        <div class="stat-mini-val" style="color:#10b981">{{ number_format($totalBpjs) }}</div>
        <div class="stat-mini-label">JKN</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="stat-mini-card">
      <div class="stat-mini-icon" style="background:rgba(59, 130, 246, 0.12);">
        <i data-feather="x-circle" style="width:20px;height:20px;color:#3b82f6"></i>
      </div>
      <div>
        <div class="stat-mini-val" style="color:#3b82f6">{{ number_format($totalNonBpjs) }}</div>
        <div class="stat-mini-label">Non JKN</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="stat-mini-card">
      <div class="stat-mini-icon icon-box-success">
        <i data-feather="map" style="width:20px;height:20px;color:var(--success-color)"></i>
      </div>
      <div>
        <div class="stat-mini-val">{{ $totalProvinsi }}</div>
        <div class="stat-mini-label">Prov Terjangkau</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="stat-mini-card">
      <div class="stat-mini-icon icon-box-warning">
        <i data-feather="award" style="width:20px;height:20px;color:var(--warning-color)"></i>
      </div>
      <div>
        <div class="stat-mini-val" style="font-size:1.1rem">{{ $byProvinsi->first()?->provinsi ?? '-' }}</div>
        <div class="stat-mini-label">Prov Terbanyak</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="stat-mini-card">
      <div class="stat-mini-icon icon-box-danger">
        <i data-feather="trending-up" style="width:20px;height:20px;color:var(--danger-color)"></i>
      </div>
      <div>
        <div class="stat-mini-val">{{ $byProvinsi->first()?->total ?? 0 }}</div>
        <div class="stat-mini-label">Max Pasien</div>
      </div>
    </div>
  </div>
</div>

<div class="row fade-in-up delay-100">
  <div class="col-lg-8 mb-4">

    <div class="filter-bar">
      <span class="filter-label"><i data-feather="filter" style="width:13px;height:13px;"></i> Filter:</span>
      <select id="filterProvinsi" class="form-select filter-select">
        <option value="">Semua Provinsi</option>
        @foreach($provinsiList as $prov)
          <option value="{{ $prov }}">{{ $prov }}</option>
        @endforeach
      </select>
      <select id="filterKota" class="form-select filter-select" disabled>
        <option value="">Pilih Provinsi Dulu</option>
      </select>
      <button class="btn btn-outline-secondary btn-sm" id="btnReset">
        <i data-feather="x" style="width:13px;height:13px;" class="me-1"></i> Reset
      </button>
    </div>

    <div class="card p-0" style="overflow:hidden; border-radius:10px;">
      <div id="map"></div>
    </div>

    <div id="info-panel">
      <div class="info-panel-title">
        <i data-feather="map-pin" style="width:14px;height:14px;"></i>
        <span id="info-panel-title-text">Detail</span>
      </div>
      <table class="info-kota-table" id="info-kota-table">
        <thead>
          <tr>
            <th>Kab / Kota</th>
            <th>Jumlah Pasien</th>
          </tr>
        </thead>
        <tbody id="info-kota-body"></tbody>
      </table>
    </div>
  </div>

  <div class="col-lg-4 mb-4" style="display:flex; flex-direction:column; gap:16px;">
    <!-- Pie Chart Card -->
    <div class="prov-table-wrap">
      <div class="prov-table-head">
        <i data-feather="pie-chart" style="width:16px;height:16px;color:var(--primary-color)"></i>
        Distribusi Pasien
      </div>
      <div style="padding: 16px; height: 280px; display:flex; justify-content:center; align-items:center;">
        <canvas id="provinsiPieChart"></canvas>
      </div>
    </div>

    <!-- Ranking Table Card -->
    <div class="prov-table-wrap" style="flex: 1;">
      <div class="prov-table-head">
        <i data-feather="bar-chart-2" style="width:16px;height:16px;color:var(--primary-color)"></i>
        Ranking Provinsi
      </div>
      <div style="max-height:384px; overflow-y:auto;">
        <table class="prov-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Provinsi</th>
              <th>Pasien</th>
            </tr>
          </thead>
          <tbody>
            @php $max = $byProvinsi->first()?->total ?? 1; @endphp
            @foreach($byProvinsi as $i => $prov)
            <tr class="prov-row" data-provinsi="{{ $prov->provinsi }}">
              <td>
                <span class="rank-badge {{ $i===0?'top1':($i===1?'top2':($i===2?'top3':'')) }}">
                  {{ $i + 1 }}
                </span>
              </td>
              <td>
                <div style="font-weight:600; font-size:0.83rem;">{{ $prov->provinsi }}</div>
                <div class="prog-bar-wrap">
                  <div class="prog-bar-fill" style="width:{{ round($prov->total/$max*100) }}%"></div>
                </div>
              </td>
              <td>
                <span class="badge-count">{{ $prov->total }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- ===== ROW 2: BPJS PIE + BAR CHART PER BULAN ===== --}}
<div class="row g-3 mb-4 fade-in-up" style="animation-delay:200ms">

      {{-- Pie JKN vs Non JKN --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="pie-chart" style="width:16px;height:16px;color:#10b981"></i>
        JKN vs Non JKN
      </div>
      <div style="padding:16px;">
        <div style="height:200px; display:flex; justify-content:center; align-items:center;">
          <canvas id="bpjsPieChart"></canvas>
        </div>
        <div class="d-flex justify-content-center gap-4 mt-3">
          <div class="text-center">
            <div class="fw-bold" style="color:#10b981; font-size:1.3rem;">{{ number_format($totalBpjs) }}</div>
            <div style="font-size:0.75rem; color:var(--text-muted);">
              <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#10b981;margin-right:4px;"></span>JKN
            </div>
          </div>
          <div class="text-center">
            <div class="fw-bold" style="color:#3b82f6; font-size:1.3rem;">{{ number_format($totalNonBpjs) }}</div>
            <div style="font-size:0.75rem; color:var(--text-muted);">
              <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#3b82f6;margin-right:4px;"></span>Non JKN
            </div>
          </div>
        </div>
        @php $pctBpjs = $totalPasien > 0 ? round($totalBpjs/$totalPasien*100) : 0; @endphp
        <div class="mt-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="small text-muted">Proporsi JKN</span>
            <span class="small fw-bold">{{ $pctBpjs }}%</span>
          </div>
          <div style="height:6px;border-radius:3px;background:var(--border-color);overflow:hidden;">
            <div style="height:100%;width:{{ $pctBpjs }}%;background:linear-gradient(90deg,#10b981,#3b82f6);border-radius:3px;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Bar Chart per Bulan --}}
  <div class="col-md-8">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <i data-feather="bar-chart" style="width:16px;height:16px;color:var(--primary-color)"></i>
          Data Pasien per Bulan
        </div>
        <select id="filterBulanRange" class="form-select form-select-sm" style="width:auto;">
          <option value="3">3 Bulan Terakhir</option>
          <option value="6">6 Bulan Terakhir</option>
          <option value="12" selected>12 Bulan Terakhir</option>
        </select>
      </div>
      <div style="padding:16px; height:280px;">
        <canvas id="perBulanChart"></canvas>
      </div>
    </div>
  </div>

</div>

{{-- ===== ROW 3: SEBARAN PASIEN, PENJAMINAN PASIEN, JUMLAH PASIEN PER WILAYAH ===== --}}
<div class="row g-3 mb-4 fade-in-up" style="animation-delay:250ms">
  {{-- Sebaran Pasien Per Wilayah --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="pie-chart" style="width:16px;height:16px;color:var(--primary-color)"></i>
        Sebaran Pasien Per Wilayah
      </div>
      <div style="padding:16px; height:280px; display:flex; justify-content:center; align-items:center;">
        <canvas id="sebaranWilayahChart"></canvas>
      </div>
    </div>
  </div>

  {{-- Penjaminan Pasien Per Wilayah --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="bar-chart-2" style="width:16px;height:16px;color:var(--success-color)"></i>
        Penjaminan Pasien Per Wilayah
      </div>
      <div style="padding:16px; height:280px;">
        <canvas id="penjaminanWilayahChart"></canvas>
      </div>
    </div>
  </div>

  {{-- Jumlah Pasien Per Wilayah --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="users" style="width:16px;height:16px;color:var(--info-color)"></i>
        Jumlah Pasien Per Wilayah
      </div>
      <div style="padding:16px; height:280px;">
        <canvas id="jumlahPasienWilayahChart"></canvas>
      </div>
    </div>
  </div>
</div>

{{-- ===== ROW 4: DETAIL WILAYAH JAWA BARAT ===== --}}
<div class="row g-3 mb-4 fade-in-up" style="animation-delay:300ms">
  {{-- Sebaran Pasien Wilayah Jawa Barat --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="pie-chart" style="width:16px;height:16px;color:var(--warning-color)"></i>
        Sebaran Pasien Wilayah Jawa Barat
      </div>
      <div style="padding:16px; height:280px; display:flex; justify-content:center; align-items:center;">
        <canvas id="sebaranJawaBaratChart"></canvas>
      </div>
    </div>
  </div>

  {{-- Penjaminan Pasien Wilayah Jawa Barat --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="bar-chart-2" style="width:16px;height:16px;color:var(--danger-color)"></i>
        Penjaminan Pasien Wilayah Jawa Barat
      </div>
      <div style="padding:16px; height:280px;">
        <canvas id="penjaminanJawaBaratChart"></canvas>
      </div>
    </div>
  </div>

  {{-- Pengunjung Per Bulan Wilayah Jawa Barat --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="trending-up" style="width:16px;height:16px;color:var(--primary-color)"></i>
        Pengunjung Per Bulan Wilayah Jawa Barat
      </div>
      <div style="padding:16px; height:280px;">
        <canvas id="pengunjungJawaBaratChart"></canvas>
      </div>
    </div>
  </div>
</div>

{{-- ===== ROW 5: DETAIL WILAYAH DKI JAKARTA ===== --}}
<div class="row g-3 mb-4 fade-in-up" style="animation-delay:350ms">
  {{-- Sebaran Pasien Wilayah DKI Jakarta --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="pie-chart" style="width:16px;height:16px;color:var(--warning-color)"></i>
        Sebaran Pasien Wilayah DKI Jakarta
      </div>
      <div style="padding:16px; height:280px; display:flex; justify-content:center; align-items:center;">
        <canvas id="sebaranDkiChart"></canvas>
      </div>
    </div>
  </div>

  {{-- Penjaminan Pasien Wilayah DKI Jakarta --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="bar-chart-2" style="width:16px;height:16px;color:var(--danger-color)"></i>
        Penjaminan Pasien Wilayah DKI Jakarta
      </div>
      <div style="padding:16px; height:280px;">
        <canvas id="penjaminanDkiChart"></canvas>
      </div>
    </div>
  </div>

  {{-- Pengunjung Per Bulan Wilayah DKI Jakarta --}}
  <div class="col-md-4">
    <div class="prov-table-wrap h-100">
      <div class="prov-table-head">
        <i data-feather="trending-up" style="width:16px;height:16px;color:var(--primary-color)"></i>
        Pengunjung Per Bulan Wilayah DKI Jakarta
      </div>
      <div style="padding:16px; height:280px;">
        <canvas id="pengunjungDkiChart"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="importGeoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Data Geografi Pasien</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        {{-- Tab pilihan import --}}
        <ul class="nav nav-tabs mb-3" id="importTabs">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMaster">
              <i data-feather="database" style="width:14px;height:14px;" class="me-1"></i>
              Import Master Pasien (Afya)
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabManual">
              <i data-feather="file-text" style="width:14px;height:14px;" class="me-1"></i>
              Import Manual (CSV/Excel)
            </button>
          </li>
        </ul>

        <div class="tab-content">
          {{-- Tab 1: Import Master Pasien --}}
          <div class="tab-pane fade show active" id="tabMaster">
            <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-3" style="font-size:0.83rem;">
              <i data-feather="alert-triangle" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
              <div>
                <b>Perhatian:</b> Import ini akan <b>menghapus semua data lama</b> dan menggantinya dengan data baru.<br>
                Gunakan file <b>master_pasien.xlsx</b> dari sistem Afya. Sheet yang dibaca: <b>"2025 - 03,2026"</b>.<br>
                Periode data: <b>Januari 2025 s/d Maret 2026</b>. Kolom: KAT Guarantor, KAT EDITED, Created Date.
              </div>
            </div>
            <form action="{{ route('patient-geography.import-master') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label class="form-label fw-bold">File master_pasien.xlsx</label>
                <input type="file" class="form-control" name="file_excel" accept=".xlsx,.xls" required>
                <div class="form-text">Hanya format .xlsx / .xls yang diterima.</div>
              </div>
              <button type="submit" class="btn btn-warning">
                <i data-feather="upload-cloud" style="width:14px;height:14px;" class="me-1"></i> Import & Ganti Data
              </button>
            </form>
          </div>

          {{-- Tab 2: Import Manual --}}
          <div class="tab-pane fade" id="tabManual">
            <div class="alert alert-info d-flex align-items-start gap-2 py-2 mb-3" style="font-size:0.83rem;">
              <i data-feather="info" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
              <div>
                Header kolom: <b>nama_pasien</b>, <b>no_rm</b>, <b>provinsi</b>, <b>kabupaten_kota</b>,
                <b>guarantor</b>, <b>kat_guarantor</b> (JKN/Non JKN), <b>tanggal_kunjungan</b> (YYYY-MM-DD).<br>
                Data yang nomor RM-nya sudah ada akan dilewati.
              </div>
            </div>
            <form action="{{ route('patient-geography.import') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label class="form-label fw-bold">File Excel / CSV</label>
                <input type="file" class="form-control" name="file_excel" accept=".csv,.xlsx,.xls" required>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i data-feather="upload-cloud" style="width:14px;height:14px;" class="me-1"></i> Mulai Import
                </button>
                <a href="{{ route('patient-geography.export') }}" class="btn btn-outline-secondary btn-sm">
                  <i data-feather="download" style="width:13px;height:13px;" class="me-1"></i> Download Template
                </a>
              </div>
            </form>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://d3js.org/d3.v4.min.js"></script>
<script src="https://unpkg.com/leaflet.minichart/dist/leaflet.minichart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const PROVINSI_COORDS = @json(\App\Http\Controllers\PatientGeographyController::$provinsiCoords);
const KOTA_COORDS     = @json(\App\Http\Controllers\PatientGeographyController::$kotaCoords);

// Map init
var map = L.map('map', { center: [-2.5, 117.5], zoom: 5, minZoom: 4, maxZoom: 16 });
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  maxZoom: 19,
}).addTo(map);

var markersLayer = L.layerGroup().addTo(map);

// Add Legend
var legend = L.control({ position: 'bottomright' });
legend.onAdd = function (map) {
  var div = L.DomUtil.create('div', 'info legend');
  div.style.backgroundColor = 'white';
  div.style.padding = '8px 12px';
  div.style.borderRadius = '5px';
  div.style.boxShadow = '0 1px 5px rgba(0,0,0,0.4)';
  div.style.fontSize = '12px';
  div.innerHTML += '<strong style="display:block;margin-bottom:6px;font-size:13px;">Keterangan</strong>';
  div.innerHTML += '<div style="margin-bottom:4px;"><i style="background: #10b981; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 6px; vertical-align: middle;"></i> <span style="vertical-align: middle;">JKN</span></div>';
  div.innerHTML += '<div><i style="background: #3b82f6; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 6px; vertical-align: middle;"></i> <span style="vertical-align: middle;">Non JKN</span></div>';
  return div;
};
legend.addTo(map);
var allProvinsiData = [];
var maxTotal = 1;

// Red gradient: merah muda (#fca5a5) -> merah gelap (#7f1d1d)
function getRedColor(count, max) {
  if (!count || count === 0) return '#fca5a5';
  var ratio = Math.min(count / (max || 1), 1);
  var r = Math.round(252 - ratio * (252 - 127));
  var g = Math.round(165 - ratio * 165);
  var b = Math.round(165 - ratio * 165);
  return 'rgb(' + r + ',' + g + ',' + b + ')';
}


// City markers - Pie Charts (JKN vs Non JKN)
function buildKotaMarkers(data) {
  markersLayer.clearLayers();
  var kotaMax = data.reduce(function(a, b) { return Math.max(a, b.total); }, 1);

  data.forEach(function(item) {
    if (!item.lat || !item.lng) return;
    
    // Scale pie chart radius dynamically
    var radius = 20 + (item.total / kotaMax) * 30;
    
    var bpjsCount = parseInt(item.bpjs || 0);
    var nonBpjsCount = parseInt(item.non_bpjs || 0);

    var marker = L.minichart([item.lat, item.lng], {
      data: [bpjsCount, nonBpjsCount],
      maxValues: kotaMax,
      type: "pie",
      colors: ["#10b981", "#3b82f6"], // Hijau (JKN), Biru (Non JKN)
      width: radius * 2
    });

    marker.bindPopup(
      '<div class="custom-popup" style="text-align:center;min-width:160px;">'
      + '<h6>' + item.kabupaten_kota + '</h6>'
      + '<div class="pop-count" style="color:#e53e3e;">' + item.total + '</div>'
      + '<div class="pop-label">total pasien</div>'
      + '<div style="margin-top:10px;text-align:left;font-size:0.85rem;border-top:1px solid #eee;padding-top:8px;">'
      + '  <div style="display:flex; justify-content:space-between; margin-bottom:4px;">'
      + '    <span style="color:#10b981;font-weight:600;"><i data-feather="check-circle" style="width:12px;height:12px"></i> JKN</span>'
      + '    <span style="font-weight:700;">' + bpjsCount + '</span>'
      + '  </div>'
      + '  <div style="display:flex; justify-content:space-between;">'
      + '    <span style="color:#3b82f6;font-weight:600;"><i data-feather="x-circle" style="width:12px;height:12px"></i> Non JKN</span>'
      + '    <span style="font-weight:700;">' + nonBpjsCount + '</span>'
      + '  </div>'
      + '</div>'
      + '</div>',
      { maxWidth: 220 }
    );
    
    // feather.replace doesn't run automatically in Leaflet popups
    marker.on('popupopen', function() {
      feather.replace();
    });

    marker.bindTooltip(
      '<div style="text-align:center;">' +
      '<b>' + item.kabupaten_kota + '</b><br>' +
      'Total: ' + item.total + ' Pasien<br>' +
      '<div style="margin-top:4px; font-size:11px;">' +
      '<span style="color:#10b981;font-weight:600;">JKN: ' + bpjsCount + '</span> &bull; ' +
      '<span style="color:#3b82f6;font-weight:600;">Non JKN: ' + nonBpjsCount + '</span>' +
      '</div></div>', 
      { sticky: true }
    );
    markersLayer.addLayer(marker);
  });
}


function loadAllProvinsi() {
  fetch('{{ route("patient-geography.api-data") }}')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      allProvinsiData = data;
      // No markers shown based on initial request
    });
}

function filterProvinsi(prov) {
  var kotaSel = document.getElementById('filterKota');
  kotaSel.innerHTML = '<option value="">Semua Kota</option>';
  kotaSel.disabled = true;

  if (!prov) {
    map.flyTo([-2.5, 117.5], 5, { animate: true, duration: 1.2 });
    markersLayer.clearLayers();
    hideInfoPanel();
    return;
  }

  var coords = PROVINSI_COORDS[prov];
  if (coords) map.flyTo([coords[0], coords[1]], coords[2], { animate: true, duration: 1.4 });

  fetch('{{ route("patient-geography.api-data") }}?provinsi=' + encodeURIComponent(prov))
    .then(function(r) { return r.json(); })
    .then(function(kotaData) {
      buildKotaMarkers(kotaData);
      showInfoKota(prov, kotaData);
      kotaData.forEach(function(k) {
        var opt = document.createElement('option');
        opt.value = k.kabupaten_kota;
        opt.textContent = k.kabupaten_kota;
        kotaSel.appendChild(opt);
      });
      kotaSel.disabled = false;
    });
}

function filterKota(kota) {
  if (!kota) return;
  var coords = KOTA_COORDS[kota];
  if (coords) map.flyTo([coords[0], coords[1]], coords[2] || 13, { animate: true, duration: 1.2 });
  
  markersLayer.eachLayer(function(layer) {
    if (layer.getTooltip) {
      var tip = layer.getTooltip();
      var content = tip ? tip.getContent() : '';
      var isMatch = content && content.indexOf(kota) > -1;
      
      if (layer.setOptions) {
        layer.setOptions({
          opacity: isMatch ? 1 : 0.1,
          fillOpacity: isMatch ? 1 : 0.1
        });
      }
    }
  });
}

function showInfoKota(provinsi, kotaData) {
  var panel   = document.getElementById('info-panel');
  var titleEl = document.getElementById('info-panel-title-text');
  var tbody   = document.getElementById('info-kota-body');
  var total   = kotaData.reduce(function(a, b) { return a + parseInt(b.total); }, 0);
  titleEl.textContent = provinsi + ' - ' + total + ' pasien total';
  tbody.innerHTML = '';
  kotaData.sort(function(a, b) { return b.total - a.total; }).forEach(function(k) {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td style="cursor:pointer;" onclick="selectKota(\'' + k.kabupaten_kota.replace(/'/g, "\\'") + '\')">'
                 + k.kabupaten_kota + '</td>'
                 + '<td><span class="badge-count">' + k.total + '</span></td>';
    tbody.appendChild(tr);
  });
  panel.style.display = 'block';
  feather.replace();
}

function hideInfoPanel() {
  document.getElementById('info-panel').style.display = 'none';
}

function selectKota(kota) {
  document.getElementById('filterKota').value = kota;
  filterKota(kota);
}

document.getElementById('filterProvinsi').addEventListener('change', function() {
  filterProvinsi(this.value);
});
document.getElementById('filterKota').addEventListener('change', function() {
  filterKota(this.value);
});
document.getElementById('btnReset').addEventListener('click', function() {
  document.getElementById('filterProvinsi').value = '';
  document.getElementById('filterKota').innerHTML = '<option value="">Pilih Provinsi Dulu</option>';
  document.getElementById('filterKota').disabled = true;
  map.flyTo([-2.5, 117.5], 5, { animate: true, duration: 1.2 });
  markersLayer.clearLayers();
  hideInfoPanel();
});

document.querySelectorAll('.prov-row').forEach(function(row) {
  row.addEventListener('click', function() {
    var prov = this.dataset.provinsi;
    document.getElementById('filterProvinsi').value = prov;
    filterProvinsi(prov);
  });
});

document.addEventListener('DOMContentLoaded', function() {
  feather.replace();
  loadAllProvinsi();
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  
  // Render Pie Chart — warna berbeda per provinsi
  var chartDataRaw = @json($byProvinsi);
  var chartLabels  = [];
  var chartValues  = [];
  var chartBgColors = [];

  // Palet warna yang beragam
  var palette = [
    '#0F5DA6','#05a34a','#fbbc06','#ff3366','#66d1d1',
    '#8b5cf6','#f97316','#06b6d4','#ec4899','#84cc16',
    '#14b8a6','#f59e0b','#3b82f6','#ef4444','#a855f7',
    '#10b981','#eab308','#0ea5e9','#d946ef','#22c55e'
  ];

  var maxItems  = 8;
  var sumOthers = 0;

  chartDataRaw.forEach(function(d, idx) {
    if (idx < maxItems) {
      chartLabels.push(d.provinsi);
      chartValues.push(d.total);
      chartBgColors.push(palette[idx % palette.length]);
    } else {
      sumOthers += d.total;
    }
  });

  if (sumOthers > 0) {
    chartLabels.push('Lainnya');
    chartValues.push(sumOthers);
    chartBgColors.push('#cbd5e1');
  }

  var ctxPie = document.getElementById('provinsiPieChart').getContext('2d');
  new Chart(ctxPie, {
    type: 'pie',
    data: {
      labels: chartLabels,
      datasets: [{
        data: chartValues,
        backgroundColor: chartBgColors,
        borderWidth: 2,
        borderColor: isDark ? '#15234b' : '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'right',
          labels: {
            boxWidth: 12,
            color: isDark ? '#e2e8f0' : '#0b132b',
            font: { size: 11, family: "'Roboto', sans-serif" }
          }
        },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              var total = ctx.dataset.data.reduce(function(a,b){return a+b;},0);
              var pct   = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
              return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
            }
          }
        }
      }
    }
  });

  // --- Pie Chart JKN vs Non JKN ---
  var ctxBpjs = document.getElementById('bpjsPieChart').getContext('2d');
  new Chart(ctxBpjs, {
    type: 'doughnut',
    data: {
      labels: ['JKN', 'Non JKN'],
      datasets: [{
        data: [{{ $totalBpjs }}, {{ $totalNonBpjs }}],
        backgroundColor: ['#10b981', '#3b82f6'],
        borderColor: isDark ? '#15234b' : '#ffffff',
        borderWidth: 3,
        hoverOffset: 6,
      }]
    },
    options: {
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              var total = ctx.dataset.data.reduce(function(a,b){return a+b;},0);
              var pct   = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
              return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
            }
          }
        }
      },
      animation: { animateRotate: true, duration: 900 }
    },
    plugins: [{
      id: 'bpjsCenter',
      afterDraw: function(chart) {
        var ctx2 = chart.ctx;
        var ca   = chart.chartArea;
        var cx   = ca.left + (ca.right - ca.left) / 2;
        var cy   = ca.top  + (ca.bottom - ca.top) / 2;
        var total = chart.data.datasets[0].data.reduce(function(a,b){return a+b;},0);
        var pct   = total > 0 ? Math.round(chart.data.datasets[0].data[0] / total * 100) : 0;
        ctx2.save();
        ctx2.font = 'bold 1.2rem Overpass, sans-serif';
        ctx2.fillStyle = isDark ? '#e2e8f0' : '#0b132b';
        ctx2.textAlign = 'center';
        ctx2.textBaseline = 'middle';
        ctx2.fillText(pct + '%', cx, cy - 7);
        ctx2.font = '0.65rem Roboto, sans-serif';
        ctx2.fillStyle = '#7987a1';
        ctx2.fillText('JKN', cx, cy + 12);
        ctx2.restore();
      }
    }]
  });

  // --- Bar/Line Chart per Bulan ---
  var perBulanData = @json($perBulan);
  var bulanLabels  = perBulanData.map(function(d){ return d.label; });
  var bulanBpjs    = perBulanData.map(function(d){ return d.bpjs; });
  var bulanNonBpjs = perBulanData.map(function(d){ return d.non_bpjs; });
  var bulanTotal   = perBulanData.map(function(d){ return d.total; });

  var ctxBar = document.getElementById('perBulanChart').getContext('2d');
  var perBulanChart = new Chart(ctxBar, {
    type: 'bar',
    data: {
      labels: bulanLabels,
      datasets: [
        {
          label: 'JKN',
          data: bulanBpjs,
          backgroundColor: 'rgba(16,185,129,0.75)',
          borderColor: '#10b981',
          borderWidth: 1,
          borderRadius: 4,
        },
        {
          label: 'Non JKN',
          data: bulanNonBpjs,
          backgroundColor: 'rgba(59,130,246,0.75)',
          borderColor: '#3b82f6',
          borderWidth: 1,
          borderRadius: 4,
        },
        {
          label: 'Total',
          data: bulanTotal,
          type: 'line',
          borderColor: '#0F5DA6',
          backgroundColor: 'rgba(15, 93, 166, 0.1)',
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: '#0F5DA6',
          fill: false,
          tension: 0.3,
          yAxisID: 'y',
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          labels: {
            color: isDark ? '#e2e8f0' : '#0b132b',
            font: { size: 11 }
          }
        }
      },
      scales: {
        x: {
          stacked: true,
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 10 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 10 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        }
      }
    }
  });

  // Filter bulan
  var allLabels   = bulanLabels.slice();
  var allBpjs     = bulanBpjs.slice();
  var allNonBpjs  = bulanNonBpjs.slice();
  var allTotal    = bulanTotal.slice();

  document.getElementById('filterBulanRange').addEventListener('change', function() {
    var n = parseInt(this.value);
    perBulanChart.data.labels              = allLabels.slice(-n);
    perBulanChart.data.datasets[0].data   = allBpjs.slice(-n);
    perBulanChart.data.datasets[1].data   = allNonBpjs.slice(-n);
    perBulanChart.update();
  });

  // ==========================================
  // 6 NEW CHARTS INITIALIZATION
  // ==========================================
  
  // 1. Sebaran Pasien Per Wilayah (Pie Chart)
  const sebaranWilayahData = @json($sebaranWilayah);
  const swLabels = sebaranWilayahData.map(d => d.wilayah);
  const swTotals = sebaranWilayahData.map(d => d.total);
  
  const ctxSw = document.getElementById('sebaranWilayahChart').getContext('2d');
  new Chart(ctxSw, {
    type: 'pie',
    data: {
      labels: swLabels,
      datasets: [{
        data: swTotals,
        backgroundColor: ['#0F5DA6', '#cbd5e1', '#05a34a', '#fbbc06'],
        borderWidth: 2,
        borderColor: isDark ? '#15234b' : '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: isDark ? '#e2e8f0' : '#0b132b',
            font: { size: 10 }
          }
        },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
              return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
            }
          }
        }
      }
    }
  });

  // 2. Penjaminan Pasien Per Wilayah (Grouped Horizontal Bar Chart)
  const swJkn = sebaranWilayahData.map(d => d.jkn);
  const swNonJkn = sebaranWilayahData.map(d => d.non_jkn);

  const ctxPw = document.getElementById('penjaminanWilayahChart').getContext('2d');
  new Chart(ctxPw, {
    type: 'bar',
    data: {
      labels: swLabels,
      datasets: [
        {
          label: 'JKN',
          data: swJkn,
          backgroundColor: '#10b981',
          borderRadius: 4
        },
        {
          label: 'Non JKN',
          data: swNonJkn,
          backgroundColor: '#3b82f6',
          borderRadius: 4
        }
      ]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: isDark ? '#e2e8f0' : '#0b132b', font: { size: 10 } }
        }
      },
      scales: {
        x: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 9 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        },
        y: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 10 } },
          grid: { display: false }
        }
      }
    }
  });

  // 3. Jumlah Pasien Per Wilayah (Horizontal Bar Chart)
  const topCitiesData = @json($topCities);
  const tcLabels = topCitiesData.map(d => d.kabupaten_kota);
  const tcTotals = topCitiesData.map(d => d.total);

  const ctxJpw = document.getElementById('jumlahPasienWilayahChart').getContext('2d');
  new Chart(ctxJpw, {
    type: 'bar',
    data: {
      labels: tcLabels,
      datasets: [{
        label: 'Jumlah Pasien',
        data: tcTotals,
        backgroundColor: '#0F5DA6',
        borderRadius: 4
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 9 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        },
        y: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 9 } },
          grid: { display: false }
        }
      }
    }
  });

  // 4. Sebaran Pasien Wilayah Jawa Barat (Pie Chart)
  const sebaranJbData = @json($sebaranJawaBarat);
  const sjbLabels = sebaranJbData.map(d => d.kota_group);
  const sjbTotals = sebaranJbData.map(d => d.total);

  const ctxSjb = document.getElementById('sebaranJawaBaratChart').getContext('2d');
  new Chart(ctxSjb, {
    type: 'pie',
    data: {
      labels: sjbLabels,
      datasets: [{
        data: sjbTotals,
        backgroundColor: ['#05a34a', '#fbbc06', '#cbd5e1', '#3b82f6'],
        borderWidth: 2,
        borderColor: isDark ? '#15234b' : '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: isDark ? '#e2e8f0' : '#0b132b',
            font: { size: 10 }
          }
        },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
              return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
            }
          }
        }
      }
    }
  });

  // 5. Penjaminan Pasien Wilayah Jawa Barat (Horizontal Bar Chart)
  const sjbJkn = sebaranJbData.map(d => d.jkn);
  const sjbNonJkn = sebaranJbData.map(d => d.non_jkn);

  const ctxPjb = document.getElementById('penjaminanJawaBaratChart').getContext('2d');
  new Chart(ctxPjb, {
    type: 'bar',
    data: {
      labels: sjbLabels,
      datasets: [
        {
          label: 'JKN',
          data: sjbJkn,
          backgroundColor: '#10b981',
          borderRadius: 4
        },
        {
          label: 'Non JKN',
          data: sjbNonJkn,
          backgroundColor: '#3b82f6',
          borderRadius: 4
        }
      ]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: isDark ? '#e2e8f0' : '#0b132b', font: { size: 10 } }
        }
      },
      scales: {
        x: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 9 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        },
        y: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 10 } },
          grid: { display: false }
        }
      }
    }
  });

  // 6. Pengunjung Per Bulan Wilayah Jawa Barat (Line Chart)
  const monthlyJbData = @json($monthlyJawaBarat);
  const mjbLabels = monthlyJbData.map(d => d.label);
  const mjbDepok = monthlyJbData.map(d => d.Depok);
  const mjbBogor = monthlyJbData.map(d => d.Bogor);
  const mjbBekasi = monthlyJbData.map(d => d.Bekasi);
  const mjbOthers = monthlyJbData.map(d => d['Jawa Barat Lainnya']);

  const ctxMjb = document.getElementById('pengunjungJawaBaratChart').getContext('2d');
  new Chart(ctxMjb, {
    type: 'line',
    data: {
      labels: mjbLabels,
      datasets: [
        {
          label: 'Depok',
          data: mjbDepok,
          borderColor: '#05a34a',
          backgroundColor: 'rgba(5,163,74,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Bogor',
          data: mjbBogor,
          borderColor: '#fbbc06',
          backgroundColor: 'rgba(251,188,6,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Bekasi',
          data: mjbBekasi,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59,130,246,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Jawa Barat Lainnya',
          data: mjbOthers,
          borderColor: '#64748b',
          backgroundColor: 'rgba(100,116,139,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: isDark ? '#e2e8f0' : '#0b132b', font: { size: 9 } }
        }
      },
      scales: {
        x: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 8 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        },
        y: {
          beginAtZero: true,
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 9 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        }
      }
    }
  });

  // ==========================================
  // DKI JAKARTA CHARTS INITIALIZATION
  // ==========================================
  
  // 1. Sebaran Pasien Wilayah DKI Jakarta (Pie Chart)
  const sebaranDkiData = @json($sebaranDki);
  const sdLabels = sebaranDkiData.map(d => d.kota_group);
  const sdTotals = sebaranDkiData.map(d => d.total);
  
  const ctxSd = document.getElementById('sebaranDkiChart').getContext('2d');
  new Chart(ctxSd, {
    type: 'pie',
    data: {
      labels: sdLabels,
      datasets: [{
        data: sdTotals,
        backgroundColor: ['#05a34a', '#3b82f6', '#cbd5e1', '#fbbc06', '#0F5DA6', '#ff3366', '#a855f7'],
        borderWidth: 2,
        borderColor: isDark ? '#15234b' : '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: isDark ? '#e2e8f0' : '#0b132b',
            font: { size: 10 }
          }
        },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
              return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
            }
          }
        }
      }
    }
  });

  // 2. Penjaminan Pasien Wilayah DKI Jakarta (Grouped Horizontal Bar Chart)
  const sdJkn = sebaranDkiData.map(d => d.jkn);
  const sdNonJkn = sebaranDkiData.map(d => d.non_jkn);

  const ctxPd = document.getElementById('penjaminanDkiChart').getContext('2d');
  new Chart(ctxPd, {
    type: 'bar',
    data: {
      labels: sdLabels,
      datasets: [
        {
          label: 'JKN',
          data: sdJkn,
          backgroundColor: '#10b981',
          borderRadius: 4
        },
        {
          label: 'Non JKN',
          data: sdNonJkn,
          backgroundColor: '#3b82f6',
          borderRadius: 4
        }
      ]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: isDark ? '#e2e8f0' : '#0b132b', font: { size: 10 } }
        }
      },
      scales: {
        x: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 9 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        },
        y: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 10 } },
          grid: { display: false }
        }
      }
    }
  });

  // 3. Pengunjung Per Bulan Wilayah DKI Jakarta (Line Chart)
  const monthlyDkiData = @json($monthlyDki);
  const mdLabels = monthlyDkiData.map(d => d.label);
  const mdSelatan = monthlyDkiData.map(d => d['Jakarta Selatan']);
  const mdTimur = monthlyDkiData.map(d => d['Jakarta Timur']);
  const mdPusat = monthlyDkiData.map(d => d['Jakarta Pusat']);
  const mdBarat = monthlyDkiData.map(d => d['Jakarta Barat']);
  const mdUtara = monthlyDkiData.map(d => d['Jakarta Utara']);
  const mdSeribu = monthlyDkiData.map(d => d['Kepulauan Seribu']);

  const ctxMdd = document.getElementById('pengunjungDkiChart').getContext('2d');
  new Chart(ctxMdd, {
    type: 'line',
    data: {
      labels: mdLabels,
      datasets: [
        {
          label: 'Jakarta Selatan',
          data: mdSelatan,
          borderColor: '#05a34a',
          backgroundColor: 'rgba(5,163,74,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Jakarta Timur',
          data: mdTimur,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59,130,246,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Jakarta Pusat',
          data: mdPusat,
          borderColor: '#fbbc06',
          backgroundColor: 'rgba(251,188,6,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Jakarta Barat',
          data: mdBarat,
          borderColor: '#64748b',
          backgroundColor: 'rgba(100,116,139,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Jakarta Utara',
          data: mdUtara,
          borderColor: '#ff3366',
          backgroundColor: 'rgba(255,51,102,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        },
        {
          label: 'Kepulauan Seribu',
          data: mdSeribu,
          borderColor: '#a855f7',
          backgroundColor: 'rgba(168,85,247,0.05)',
          borderWidth: 2,
          pointRadius: 3,
          fill: false,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: isDark ? '#e2e8f0' : '#0b132b', font: { size: 9 } }
        }
      },
      scales: {
        x: {
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 8 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        },
        y: {
          beginAtZero: true,
          ticks: { color: isDark ? '#8899bb' : '#7987a1', font: { size: 9 } },
          grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
        }
      }
    }
  });
});
</script>
@endsection
