@extends('layouts.noble_layout')

@section('title', 'Data Klaim ' . ($jenisRawat === 'ranap' ? 'Ranap' : 'Rajal'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
  <div>
    <h4 class="mb-1 page-title">Data Klaim {{ $jenisRawat === 'ranap' ? 'Ranap' : 'Rajal' }}</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.' . $jenisRawat) }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Data Klaim {{ $jenisRawat === 'ranap' ? 'Ranap' : 'Rajal' }}</li>
      </ol>
    </nav>
  </div>
</div>

{{-- Minimalist Import & Truncate Section --}}
<div class="card shadow-sm mb-3 fade-in-up" style="animation-delay: 50ms;">
  <div class="card-body py-2">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      
      <!-- Import Form -->
      <form id="import-form" action="{{ route('claim-records.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap mb-0">
        @csrf
        <input type="hidden" name="jenis_rawat" value="{{ $jenisRawat }}">
        <span class="small fw-semibold text-muted text-nowrap">
          <i data-feather="upload-cloud" class="text-primary me-1" style="width:16px;height:16px;"></i>Impor Excel:
        </span>
        <input class="form-control form-control-sm py-1" type="file" id="file_excel" name="file_excel" accept=".xlsx,.xls,.csv" required style="width: 220px; font-size: 0.78rem;">
        <button type="submit" class="btn btn-primary btn-sm py-1 px-3">
          <i data-feather="upload" style="width:13px;height:13px;" class="me-1"></i>Mulai Impor
        </button>
      </form>

      <!-- Delete Data Form -->
      <form action="{{ route('claim-records.truncate', ['jenis_rawat' => $jenisRawat]) }}" method="POST" onsubmit="return confirmDelete()" class="d-flex align-items-center gap-2 flex-wrap mb-0">
        @csrf
        @method('DELETE')
        <span class="small fw-semibold text-muted text-nowrap">
          <i data-feather="trash-2" class="text-danger me-1" style="width:16px;height:16px;"></i>Hapus Data:
        </span>
        <select id="delete_month" name="delete_month" class="form-select form-select-sm" style="width: 200px; font-size: 0.78rem;">
          <option value="all">-- Semua Data (Truncate) --</option>
          @foreach($availableMonths as $mKey)
            @php
              try {
                $carbon = \Carbon\Carbon::createFromFormat('Y-m', $mKey);
                $label = $carbon->translatedFormat('F Y');
              } catch (\Exception $e) {
                $label = $mKey;
              }
            @endphp
            <option value="{{ $mKey }}">{{ $label }}</option>
          @endforeach
        </select>
        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-3">
          Hapus
        </button>
      </form>

    </div>
  </div>
</div>

<div class="row g-3">
  {{-- List Table Card --}}
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-2">
          <div>
            <h6 class="card-title mb-0">
              Total: {{ number_format($totalFiltered) }} Klaim Terdaftar
              @if($search || $severity || request()->query('month'))
                <small class="text-muted">(Terfilter dari {{ number_format($totalRecords) }})</small>
              @endif
            </h6>
          </div>
          
          <form action="{{ route($jenisRawat === 'ranap' ? 'claim-records.ranap' : 'claim-records.rajal') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-0">
            <!-- Month Filter -->
            <select name="month" class="form-select form-select-sm" style="width: 140px; font-size: 0.8rem;">
              <option value="">Semua Bulan</option>
              @foreach($availableMonths as $mKey)
                @php
                  try {
                    $carbon = \Carbon\Carbon::createFromFormat('Y-m', $mKey);
                    $label = $carbon->translatedFormat('F Y');
                  } catch (\Exception $e) {
                    $label = $mKey;
                  }
                @endphp
                <option value="{{ $mKey }}" {{ request()->query('month') === $mKey ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>

            <!-- Severity Filter -->
            <select name="severity" class="form-select form-select-sm" style="width: 140px; font-size: 0.8rem;">
              @if($jenisRawat === 'rajal')
                <option value="">Semua Severity</option>
                <option value="0" {{ $severity === '0' ? 'selected' : '' }}>Severity 0 (Rajal)</option>
              @else
                <option value="">Semua Severity</option>
                <option value="I" {{ $severity === 'I' ? 'selected' : '' }}>Severity I (Ringan)</option>
                <option value="II" {{ $severity === 'II' ? 'selected' : '' }}>Severity II (Sedang)</option>
                <option value="III" {{ $severity === 'III' ? 'selected' : '' }}>Severity III (Berat)</option>
              @endif
            </select>

            <!-- Search input -->
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari Pasien / No RM / Dokter..." value="{{ $search }}" style="width: 210px; font-size: 0.8rem;">
            
            <button type="submit" class="btn btn-primary btn-sm py-1 px-3">Filter</button>
            
            @if($search || $severity || request()->query('month'))
              <a href="{{ route($jenisRawat === 'ranap' ? 'claim-records.ranap' : 'claim-records.rajal') }}" class="btn btn-outline-secondary btn-sm py-1 px-2">Reset</a>
            @endif

            <!-- Export button -->
            <a href="{{ route('claim-records.export', ['jenis_rawat' => $jenisRawat, 'search' => $search, 'severity' => $severity, 'month' => request()->query('month')]) }}" class="btn btn-outline-success btn-sm py-1 px-3">
              <i data-feather="download" style="width:13px;height:13px;" class="me-1"></i>Ekspor Excel
            </a>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle">
            <thead>
              <tr>
                <th class="align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'no_rm', 'sort_dir' => $sortBy === 'no_rm' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-between gap-1">
                    <span>No. RM</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'no_rm')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
                <th class="align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama_pasien', 'sort_dir' => $sortBy === 'nama_pasien' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-between gap-1">
                    <span>Nama Pasien</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'nama_pasien')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
                <th class="text-center align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'discharge_date', 'sort_dir' => $sortBy === 'discharge_date' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-center gap-1 mx-auto" style="width: fit-content;">
                    <span>Tgl Pulang</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'discharge_date')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
                <th class="text-center align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'inacbg', 'sort_dir' => $sortBy === 'inacbg' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-center gap-1 mx-auto" style="width: fit-content;">
                    <span>INACBG</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'inacbg')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
                <th class="text-center align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'severity', 'sort_dir' => $sortBy === 'severity' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-center gap-1 mx-auto" style="width: fit-content;">
                    <span>Severity</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'severity')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
                <th class="text-end align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tarif_rs', 'sort_dir' => $sortBy === 'tarif_rs' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-end gap-1 ms-auto" style="width: fit-content;">
                    <span>Tarif RS</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'tarif_rs')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
                <th class="text-end align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'total_tarif', 'sort_dir' => $sortBy === 'total_tarif' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-end gap-1 ms-auto" style="width: fit-content;">
                    <span>Total Tarif INACBG</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'total_tarif')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
                <th class="text-end align-middle">
                  <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'selisih', 'sort_dir' => $sortBy === 'selisih' && $sortDir === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-end gap-1 ms-auto" style="width: fit-content;">
                    <span>Balance Positif/Negatif</span>
                    <span class="d-inline-flex">
                      @if($sortBy === 'selisih')
                        @if($sortDir === 'asc')
                          <i data-feather="chevron-up" style="width: 14px; height: 14px;"></i>
                        @else
                          <i data-feather="chevron-down" style="width: 14px; height: 14px;"></i>
                        @endif
                      @else
                        <i data-feather="chevrons-up-down" class="text-muted text-opacity-50" style="width: 14px; height: 14px;"></i>
                      @endif
                    </span>
                  </a>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $rec)
                <tr>
                  <td class="text-mono small">{{ $rec->no_rm }}</td>
                  <td>
                    <a href="javascript:void(0);" class="patient-detail-link fw-semibold text-decoration-none text-primary" data-id="{{ $rec->id }}">
                      {{ $rec->nama_pasien }}
                    </a>
                    <div>
                      <small class="text-muted text-truncate d-inline-block" style="max-width: 180px;" title="{{ $rec->dpjp }}">
                        DPJP: {{ $rec->dpjp ?: '-' }}
                      </small>
                    </div>
                  </td>
                  <td class="text-center small">{{ $rec->discharge_date ? $rec->discharge_date->format('d/m/Y') : '-' }}</td>
                  <td class="text-mono small">{{ $rec->inacbg }}</td>
                  <td class="text-center">
                    @if($rec->severity === 'I')
                      <span class="badge bg-success bg-opacity-10 text-success">I</span>
                    @elseif($rec->severity === 'II')
                      <span class="badge bg-warning bg-opacity-10 text-warning">II</span>
                    @elseif($rec->severity === 'III')
                      <span class="badge bg-danger bg-opacity-10 text-danger">III</span>
                    @else
                      <span class="badge bg-secondary text-muted">{{ $rec->severity }}</span>
                    @endif
                  </td>
                  <td class="text-end small">Rp {{ number_format($rec->tarif_rs, 0, ',', '.') }}</td>
                  <td class="text-end small">Rp {{ number_format($rec->total_tarif, 0, ',', '.') }}</td>
                  <td class="text-end fw-semibold small {{ $rec->selisih >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($rec->selisih, 0, ',', '.') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">Belum ada data klaim.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3 d-flex justify-content-end">
          {{ $records->appends(['search' => $search, 'severity' => $severity, 'sort_by' => $sortBy, 'sort_dir' => $sortDir])->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Fullscreen Loading Overlay -->
<div id="import-loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(11, 19, 43, 0.82); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
  <div class="spinner-border text-primary mb-3" role="status" style="width: 3.5rem; height: 3.5rem; border-width: 0.3em;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <h5 class="text-white fw-bold mb-1" style="letter-spacing: 0.5px;">Membaca &amp; Mengimpor Data Klaim</h5>
  <p class="text-white text-opacity-75 small mb-0">Sedang memproses file Excel, mohon jangan menutup halaman ini...</p>
</div>

<!-- Patient Detail Modal -->
<div class="modal fade" id="patientDetailModal" tabindex="-1" aria-labelledby="patientDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white border-0 py-3">
        <h5 class="modal-title fw-bold" id="patientDetailModalLabel">
          <i data-feather="user" class="me-2" style="width: 20px; height: 20px;"></i>
          Rincian Klaim Pasien
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Loading spinner -->
        <div id="patient-modal-loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="text-muted mt-2 small">Mengambil rincian data...</p>
        </div>
        
        <!-- Details Content -->
        <div id="patient-modal-content" class="d-none">
          <div class="row g-4">
            <!-- Left Side: Patient & Medis Info -->
            <div class="col-md-6 border-end border-light">
              <h6 class="fw-bold text-primary mb-3"><i data-feather="file-text" class="me-1" style="width:16px;height:16px;"></i>Informasi Medis &amp; Pasien</h6>
              <table class="table table-borderless table-sm small mb-0">
                <tr>
                  <th class="ps-0 text-muted" style="width: 40%">No. Rekam Medis</th>
                  <td class="text-mono fw-bold" id="detail-no-rm"></td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">Nama Pasien</th>
                  <td class="fw-bold" id="detail-nama-pasien"></td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">Tanggal Masuk</th>
                  <td id="detail-admission-date"></td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">Tanggal Pulang</th>
                  <td id="detail-discharge-date"></td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">Dokter DPJP</th>
                  <td class="fw-semibold" id="detail-dpjp"></td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">KSM / Spesialis</th>
                  <td class="fw-semibold" id="detail-ksm"></td>
                </tr>
              </table>
            </div>
            
            <!-- Right Side: Finance Info -->
            <div class="col-md-6">
              <h6 class="fw-bold text-primary mb-3"><i data-feather="dollar-sign" class="me-1" style="width:16px;height:16px;"></i>Rincian Keuangan &amp; Tarif</h6>
              <table class="table table-borderless table-sm small mb-0">
                <tr>
                  <th class="ps-0 text-muted" style="width: 40%">Kode INACBG</th>
                  <td class="text-mono fw-bold" id="detail-inacbg"></td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">Severity Level</th>
                  <td>
                    <span id="detail-severity-badge"></span>
                  </td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">Tarif RS</th>
                  <td class="fw-bold text-dark" id="detail-tarif-rs"></td>
                </tr>
                <tr>
                  <th class="ps-0 text-muted">Tarif INACBG</th>
                  <td class="fw-bold text-primary" id="detail-total-tarif"></td>
                </tr>
                <tr class="border-top">
                  <th class="ps-0 text-muted pt-2">Selisih / Balance</th>
                  <td class="fw-bold pt-2 fs-6" id="detail-selisih"></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Full Excel Raw Data Section -->
          <div class="border-top border-light mt-4 pt-3" id="raw-data-section">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
              <h6 class="fw-bold text-primary mb-0 d-flex align-items-center">
                <i data-feather="database" class="me-2" style="width:16px;height:16px;"></i>
                Data Impor Excel Lengkap
              </h6>
              <div class="d-flex align-items-center gap-2">
                <span class="small text-muted text-nowrap">Filter:</span>
                <input type="text" id="raw-data-search" class="form-control form-control-sm" placeholder="Cari kolom atau nilai..." style="max-width: 220px; font-size: 0.78rem;">
              </div>
            </div>
            
            <div class="table-responsive bg-light rounded" style="max-height: 300px; overflow-y: auto; border: 1px solid #e9ecef;">
              <table class="table table-sm table-hover table-striped mb-0 small">
                <thead class="table-light sticky-top" style="z-index: 10;">
                  <tr>
                    <th style="width: 40%; background: #f8f9fa;">Nama Kolom Excel</th>
                    <th style="background: #f8f9fa;">Nilai</th>
                  </tr>
                </thead>
                <tbody id="raw-data-table-body">
                  <!-- Will be rendered via JS -->
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer border-0 py-2">
        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<!-- jQuery & DataTables JS from CDN -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
function confirmDelete() {
    const select = document.getElementById('delete_month');
    const selectedText = select.options[select.selectedIndex].text;
    const val = select.value;
    if (val === 'all') {
        return confirm('Apakah Anda yakin ingin menghapus SEMUA data klaim? Tindakan ini tidak dapat dibatalkan.');
    } else {
        return confirm('Apakah Anda yakin ingin menghapus data klaim untuk bulan ' + selectedText + '? Tindakan ini tidak dapat dibatalkan.');
    }
}

document.getElementById('import-form').addEventListener('submit', function(e) {
    const overlay = document.getElementById('import-loading-overlay');
    overlay.classList.remove('d-none');
    overlay.style.display = 'flex';
    
    // Disable submit button and show loading text
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';
});

function renderRawDataRows(rawData, query = '') {
    const tbody = $('#raw-data-table-body');
    tbody.empty();
    
    const lowerQuery = query.toLowerCase().trim();
    let count = 0;

    Object.entries(rawData).forEach(([key, val]) => {
        const valStr = val === null || val === undefined ? '' : String(val);
        
        // Filter based on query if present
        if (lowerQuery !== '') {
            if (!key.toLowerCase().includes(lowerQuery) && !valStr.toLowerCase().includes(lowerQuery)) {
                return; // skip
            }
        }

        const formattedVal = valStr === '' ? '<em class="text-muted">-</em>' : valStr;

        tbody.append(`
            <tr class="raw-data-row">
                <td class="fw-semibold text-muted text-mono" style="font-size: 0.75rem;">${key}</td>
                <td class="text-dark fw-medium text-wrap" style="word-break: break-all; font-size: 0.75rem;">${formattedVal}</td>
            </tr>
        `);
        count++;
    });

    if (count === 0) {
        tbody.append(`
            <tr>
                <td colspan="2" class="text-center text-muted py-3">Tidak ada kolom yang cocok dengan pencarian.</td>
            </tr>
        `);
    }
}

$(document).ready(function() {
    // Client-side raw data search filter
    $('#raw-data-search').on('input', function() {
        if (window.currentRawData) {
            renderRawDataRows(window.currentRawData, $(this).val());
        }
    });

    $('.patient-detail-link').on('click', function() {
        const id = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('patientDetailModal'));
        
        // Show loading spinner, hide content
        $('#patient-modal-loading').removeClass('d-none');
        $('#patient-modal-content').addClass('d-none');
        modal.show();
        
        // Fetch patient detail via AJAX
        $.ajax({
            url: `{{ url('claim-records') }}/${id}`,
            method: 'GET',
            success: function(data) {
                // Populate elements
                $('#detail-no-rm').text(data.no_rm);
                $('#detail-nama-pasien').text(data.nama_pasien);
                $('#detail-admission-date').text(data.admission_date);
                $('#detail-discharge-date').text(data.discharge_date);
                $('#detail-dpjp').text(data.dpjp);
                $('#detail-ksm').text(data.ksm);
                $('#detail-inacbg').text(data.inacbg);
                
                // Severity level badge
                let badgeClass = 'bg-secondary text-muted';
                if (data.severity === 'I') {
                    badgeClass = 'bg-success bg-opacity-10 text-success';
                } else if (data.severity === 'II') {
                    badgeClass = 'bg-warning bg-opacity-10 text-warning';
                } else if (data.severity === 'III') {
                    badgeClass = 'bg-danger bg-opacity-10 text-danger';
                }
                $('#detail-severity-badge').html(`<span class="badge ${badgeClass}">${data.severity}</span>`);
                
                $('#detail-tarif-rs').text(data.tarif_rs_formatted);
                $('#detail-total-tarif').text(data.total_tarif_formatted);
                
                // Balance color coding
                const selisihEl = $('#detail-selisih');
                selisihEl.text(data.selisih_formatted);
                if (data.selisih >= 0) {
                    selisihEl.removeClass('text-danger').addClass('text-success');
                } else {
                    selisihEl.removeClass('text-success').addClass('text-danger');
                }

                // Handle raw data section
                $('#raw-data-search').val('');
                const tbody = $('#raw-data-table-body');
                tbody.empty();

                if (data.raw_data && Object.keys(data.raw_data).length > 0) {
                    $('#raw-data-section').removeClass('d-none');
                    window.currentRawData = data.raw_data;
                    renderRawDataRows(data.raw_data);
                } else {
                    $('#raw-data-section').addClass('d-none');
                    window.currentRawData = null;
                }
                
                // Hide loader, show content
                $('#patient-modal-loading').addClass('d-none');
                $('#patient-modal-content').removeClass('d-none');
                
                // Re-initialize feather icons for newly added modal icons
                if (window.feather) {
                    feather.replace();
                }
            },
            error: function(xhr, status, error) {
                alert('Gagal mengambil data rincian pasien.');
                modal.hide();
            }
        });
    });
});
</script>
@endsection
