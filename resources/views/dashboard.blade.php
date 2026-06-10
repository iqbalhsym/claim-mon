@extends('layouts.noble_layout')

@section('title', 'Dashboard')

@section('css')
<style>
  .stat-card {
    border-radius: 12px;
    border: none;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.10) !important;
  }
  .stat-card .card-body {
    padding: 1.4rem 1.5rem;
  }
  .stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon svg { width: 24px; height: 24px; }

  .stat-icon-primary { background: rgba(15, 93, 166, 0.12); color: #0F5DA6; }
  .stat-icon-success { background: rgba(5,163,74,0.12);   color: #05a34a; }
  .stat-icon-warning { background: rgba(251,188,6,0.12);  color: #fbbc06; }
  .stat-icon-danger  { background: rgba(255,51,102,0.12); color: #ff3366; }

  .stat-number {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.1;
    font-family: var(--heading-font);
    color: var(--dark-color);
  }
  .stat-label {
    font-size: 0.78rem;
    color: var(--text-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  /* Section title */
  .section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .section-title svg { width: 16px; height: 16px; color: var(--primary-color); }

  .recent-table th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    font-weight: 600;
    border-top: none;
    padding: 10px 12px;
    border-bottom: 1px solid var(--border-color);
  }
  .recent-table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.82rem;
    color: var(--text-color);
  }
  .recent-table tr:last-child td { border-bottom: none; }

  /* Dark mode adjustments */
  [data-theme="dark"] .stat-number { color: var(--text-color); }
  [data-theme="dark"] .section-title { color: var(--text-color); }
</style>
@endsection

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4 fade-in-up">
  <div>
    <h4 class="mb-1 page-title">Dashboard Analitis</h4>
    <p class="text-muted mb-0">
      Monitoring Data Klaim &amp; Severity &mdash;
      <span class="fw-semibold">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
    </p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('claim-records.index') }}" class="btn btn-primary btn-sm">
      <i data-feather="file-text" style="width:14px;height:14px;" class="me-1"></i> Lihat Data Klaim
    </a>
    <a href="{{ route('claim-records.dpjp') }}" class="btn btn-outline-primary btn-sm">
      <i data-feather="activity" style="width:14px;height:14px;" class="me-1"></i> Laporan DPJP
    </a>
  </div>
</div>

{{-- Filter Tanggal --}}
<div class="card shadow-sm mb-4 fade-in-up" style="animation-delay: 25ms;">
  <div class="card-body py-2">
    <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="small fw-semibold text-muted text-nowrap"><i data-feather="calendar" class="text-primary me-1" style="width:16px;height:16px;"></i>Filter Tanggal Pulang:</span>
        <div class="d-flex align-items-center gap-1">
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" style="width: 145px; font-size: 0.8rem;">
          <span class="small text-muted">s/d</span>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" style="width: 145px; font-size: 0.8rem;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm py-1 px-3">Filter</button>
        @if($startDate || $endDate)
          <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm py-1 px-3">Reset</a>
        @endif
        <a href="{{ route('dashboard.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success btn-sm py-1 px-3 text-white ms-1">
          <i data-feather="download" style="width:14px;height:14px;" class="me-1"></i>Ekspor Data Diagram (Excel)
        </a>
      </div>
      @if($startDate || $endDate)
        <div class="small text-muted">
          Menampilkan data dari: <b>{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : 'Awal' }}</b> s/d <b>{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : 'Akhir' }}</b>
        </div>
      @endif
    </form>
  </div>
</div>

{{-- ===== ROW 1: STAT CARDS ===== --}}
<div class="row g-3 mb-4">
  {{-- Total Pasien --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:50ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="stat-icon stat-icon-primary">
            <i data-feather="users"></i>
          </div>
        </div>
        <div class="stat-number">{{ number_format($totalRecord) }}</div>
        <div class="stat-label mt-1">Total Pasien</div>
      </div>
    </div>
  </div>

  {{-- Total Tarif --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:100ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="stat-icon stat-icon-success">
            <i data-feather="trending-up"></i>
          </div>
        </div>
        <div class="stat-number" style="font-size: 1.3rem;">Rp {{ number_format($totalTotalTarif, 0, ',', '.') }}</div>
        <div class="stat-label mt-1">Total Tarif</div>
      </div>
    </div>
  </div>

  {{-- Total Tarif RS --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:150ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="stat-icon stat-icon-warning">
            <i data-feather="home"></i>
          </div>
        </div>
        <div class="stat-number" style="font-size: 1.3rem;">Rp {{ number_format($totalTarifRs, 0, ',', '.') }}</div>
        <div class="stat-label mt-1">Total Tarif RS</div>
      </div>
    </div>
  </div>

  {{-- Selisih --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:200ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          @if($totalSelisih >= 0)
            <div class="stat-icon stat-icon-success">
              <i data-feather="plus-circle"></i>
            </div>
          @else
            <div class="stat-icon stat-icon-danger">
              <i data-feather="minus-circle"></i>
            </div>
          @endif
        </div>
        <div class="stat-number {{ $totalSelisih >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.3rem;">
          Rp {{ number_format($totalSelisih, 0, ',', '.') }}
        </div>
        <div class="stat-label mt-1">Total Selisih</div>
      </div>
    </div>
  </div>
</div>

{{-- ===== ROW 2: GRAPHICS (SEVERITY) ===== --}}
<div class="row g-3 mb-4">
  {{-- Chart 1: Jumlah Kasus Severity --}}
  <div class="col-md-6 fade-in-up" style="animation-delay:250ms">
    <div class="card h-100">
      <div class="card-body">
        <div class="section-title">
          <i data-feather="bar-chart-2"></i> Jumlah Kasus Severity per Bulan
        </div>
        <div style="position: relative; height: 320px;">
          <canvas id="severityCountChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Chart 2: Persentase Kasus Severity --}}
  <div class="col-md-6 fade-in-up" style="animation-delay:300ms">
    <div class="card h-100">
      <div class="card-body">
        <div class="section-title">
          <i data-feather="percent"></i> Persentase Kasus Severity per Bulan
        </div>
        <div style="position: relative; height: 320px;">
          <canvas id="severityPercentChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ===== ROW 3: TOP DOCTORS & RECENT CLAIMS ===== --}}
<div class="row g-3 mb-4">
  {{-- Top Doctors --}}
  <div class="col-md-5 fade-in-up" style="animation-delay:350ms">
    <div class="card h-100">
      <div class="card-body">
        <div class="section-title">
          <i data-feather="user-check"></i> Top 5 DPJP (Dokter Utama)
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Nama Dokter</th>
                <th class="text-center">Pasien</th>
                <th class="text-end">Selisih</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topDoctors as $doc)
                <tr>
                  <td class="text-truncate" style="max-width: 180px;" title="{{ $doc->dpjp ?: 'Tanpa Nama' }}">
                    <b>{{ $doc->dpjp ?: 'Tanpa Nama' }}</b>
                  </td>
                  <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary">{{ $doc->patient_count }}</span></td>
                  <td class="text-end fw-semibold {{ $doc->total_selisih >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($doc->total_selisih, 0, ',', '.') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-3">Tidak ada data dokter.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Recent Claims --}}
  <div class="col-md-7 fade-in-up" style="animation-delay:400ms">
    <div class="card h-100">
      <div class="card-body">
        <div class="section-title">
          <i data-feather="clock"></i> Klaim Pasien Terbaru
        </div>
        <div class="table-responsive">
          <table class="table recent-table mb-0">
            <thead>
              <tr>
                <th>Nama Pasien</th>
                <th>INACBG</th>
                <th>Severity</th>
                <th class="text-end">Selisih</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentRecords as $rec)
                <tr>
                  <td>
                    <div class="fw-semibold text-truncate" style="max-width: 160px;">{{ $rec->nama_pasien }}</div>
                    <small class="text-muted text-mono">RM: {{ $rec->no_rm }}</small>
                  </td>
                  <td class="text-mono small">{{ $rec->inacbg }}</td>
                  <td class="text-center">
                    @if($rec->severity == 'I')
                      <span class="badge bg-success bg-opacity-10 text-success">I (Ringan)</span>
                    @elseif($rec->severity == 'II')
                      <span class="badge bg-warning bg-opacity-10 text-warning">II (Sedang)</span>
                    @elseif($rec->severity == 'III')
                      <span class="badge bg-danger bg-opacity-10 text-danger">III (Berat)</span>
                    @else
                      <span class="badge bg-secondary text-muted">{{ $rec->severity }}</span>
                    @endif
                  </td>
                  <td class="text-end fw-semibold {{ $rec->selisih >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($rec->selisih, 0, ',', '.') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-3">Tidak ada data klaim.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';
  const labelColor = isDark ? '#8899bb' : '#7987a1';

  const months = @json($months);
  const counts = @json($severityCounts);
  const percents = @json($severityPercentages);

  // 1. Chart Jumlah Kasus
  const ctxCount = document.getElementById('severityCountChart').getContext('2d');
  new Chart(ctxCount, {
    type: 'bar',
    data: {
      labels: months,
      datasets: [
        {
          label: 'Severity I (Ringan)',
          data: counts['I'],
          backgroundColor: '#05a34a',
          borderRadius: 4
        },
        {
          label: 'Severity II (Sedang)',
          data: counts['II'],
          backgroundColor: '#fbbc06',
          borderRadius: 4
        },
        {
          label: 'Severity III (Berat)',
          data: counts['III'],
          backgroundColor: '#ff3366',
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: { color: labelColor, font: { family: 'Roboto' } }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: labelColor }
        },
        y: {
          grid: { color: gridColor },
          ticks: { color: labelColor }
        }
      }
    }
  });

  // 2. Chart Persentase Kasus
  const ctxPercent = document.getElementById('severityPercentChart').getContext('2d');
  new Chart(ctxPercent, {
    type: 'bar',
    data: {
      labels: months,
      datasets: [
        {
          label: 'Severity I (%)',
          data: percents['I'],
          backgroundColor: '#05a34a',
          borderRadius: 4
        },
        {
          label: 'Severity II (%)',
          data: percents['II'],
          backgroundColor: '#fbbc06',
          borderRadius: 4
        },
        {
          label: 'Severity III (%)',
          data: percents['III'],
          backgroundColor: '#ff3366',
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: { color: labelColor, font: { family: 'Roboto' } }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return ` ${context.dataset.label}: ${context.raw}%`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: labelColor }
        },
        y: {
          grid: { color: gridColor },
          ticks: {
            color: labelColor,
            callback: function(value) { return value + '%'; }
          },
          max: 100
        }
      }
    }
  });
});
</script>
@endsection
