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
  .stat-icon-info    { background: rgba(102,209,209,0.12);color: #66d1d1; }
  .stat-icon-purple  { background: rgba(139,92,246,0.12); color: #8b5cf6; }

  .stat-number {
    font-size: 2rem;
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
  .stat-sub {
    font-size: 0.8rem;
    margin-top: 2px;
  }

  /* Progress ring */
  .ring-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    flex-shrink: 0;
  }
  .ring-wrap svg { transform: rotate(-90deg); }
  .ring-wrap .ring-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 800;
    color: var(--dark-color);
  }

  /* Mini progress bar */
  .mini-progress {
    height: 6px;
    border-radius: 3px;
    background: var(--border-color);
    overflow: hidden;
    margin-top: 6px;
  }
  .mini-progress-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 1s ease;
  }

  /* Guarantor badge list */
  .guarantor-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
  }
  .guarantor-item:last-child { border-bottom: none; }
  .guarantor-bar {
    height: 4px;
    border-radius: 2px;
    background: var(--primary-color);
    margin-top: 4px;
    transition: width 1s ease;
  }

  /* Recent table */
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

  /* Month comparison */
  .month-badge {
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 20px;
    font-weight: 600;
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

  /* Dark mode adjustments */
  [data-theme="dark"] .stat-number { color: var(--text-color); }
  [data-theme="dark"] .ring-wrap .ring-text { color: var(--text-color); }
  [data-theme="dark"] .section-title { color: var(--text-color); }
</style>
@endsection

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4 fade-in-up">
  <div>
    <h4 class="mb-1 page-title">Dashboard</h4>
    <p class="text-muted mb-0">
      Monitoring Kelengkapan Rekam Medis &mdash;
      <span class="fw-semibold">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
    </p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('medical-records.index') }}" class="btn btn-outline-primary btn-sm">
      <i data-feather="list" style="width:14px;height:14px;" class="me-1"></i> Lihat Semua RM
    </a>
    <a href="{{ route('medical-records.create') }}" class="btn btn-primary btn-sm">
      <i data-feather="plus" style="width:14px;height:14px;" class="me-1"></i> Catatan Baru
    </a>
  </div>
</div>

{{-- ===== ROW 1: STAT CARDS ===== --}}
<div class="row g-3 mb-4">

  {{-- Total RM --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:50ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="stat-icon stat-icon-primary">
            <i data-feather="file-text"></i>
          </div>
          @php $trendClass = $bulanIni >= $bulanLalu ? 'text-success' : 'text-danger'; @endphp
          <span class="badge {{ $bulanIni >= $bulanLalu ? 'bg-success' : 'bg-danger' }} bg-opacity-10 {{ $trendClass }} month-badge">
            {{ $bulanIni >= $bulanLalu ? '↑' : '↓' }} Bulan ini
          </span>
        </div>
        <div class="stat-number">{{ number_format($totalRecord) }}</div>
        <div class="stat-label mt-1">Total Rekam Medis</div>
        <div class="stat-sub text-muted">{{ $bulanIni }} masuk bulan ini</div>
      </div>
    </div>
  </div>

  {{-- Lengkap --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:100ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="stat-icon stat-icon-success">
            <i data-feather="check-circle"></i>
          </div>
          <span class="badge bg-success bg-opacity-10 text-success month-badge">{{ $persenLengkap }}%</span>
        </div>
        <div class="stat-number">{{ number_format($lengkap) }}</div>
        <div class="stat-label mt-1">Berkas Lengkap</div>
        <div class="mini-progress">
          <div class="mini-progress-bar bg-success" style="width: {{ $persenLengkap }}%"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Tidak Lengkap --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:150ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="stat-icon stat-icon-warning">
            <i data-feather="alert-triangle"></i>
          </div>
          @php $persenTidak = $totalRecord > 0 ? round(($tidakLengkap/$totalRecord)*100) : 0; @endphp
          <span class="badge bg-warning bg-opacity-10 text-warning month-badge">{{ $persenTidak }}%</span>
        </div>
        <div class="stat-number">{{ number_format($tidakLengkap) }}</div>
        <div class="stat-label mt-1">Tidak Lengkap</div>
        <div class="mini-progress">
          <div class="mini-progress-bar bg-warning" style="width: {{ $persenTidak }}%"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Sudah Kembali --}}
  <div class="col-6 col-md-3 fade-in-up" style="animation-delay:200ms">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="stat-icon stat-icon-info">
            <i data-feather="corner-down-left"></i>
          </div>
          @php $persenKembali = $totalRecord > 0 ? round(($sudahKembali/$totalRecord)*100) : 0; @endphp
          <span class="badge bg-info bg-opacity-10 text-info month-badge">{{ $persenKembali }}%</span>
        </div>
        <div class="stat-number">{{ number_format($sudahKembali) }}</div>
        <div class="stat-label mt-1">RM Sudah Kembali</div>
        <div class="mini-progress">
          <div class="mini-progress-bar bg-info" style="width: {{ $persenKembali }}%"></div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- ===== ROW 2: CHART + MONITORING ===== --}}
<div class="row g-3 mb-4">

  {{-- Donut Chart Kelengkapan --}}
  <div class="col-md-4 fade-in-up" style="animation-delay:250ms">
    <div class="card h-100">
      <div class="card-body">
        <div class="section-title">
          <i data-feather="pie-chart"></i> Kelengkapan Berkas
        </div>
        <div class="d-flex align-items-center justify-content-center py-2">
          <canvas id="donutChart" width="200" height="200"></canvas>
        </div>
        <div class="d-flex justify-content-center gap-4 mt-3">
          <div class="text-center">
            <div class="fw-bold" style="color:#05a34a">{{ $lengkap }}</div>
            <div class="stat-label">Lengkap</div>
          </div>
          <div class="text-center">
            <div class="fw-bold" style="color:#fbbc06">{{ $tidakLengkap }}</div>
            <div class="stat-label">Tidak Lengkap</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Status Monitoring --}}
  <div class="col-md-4 fade-in-up" style="animation-delay:300ms">
    <div class="card h-100">
      <div class="card-body">
        <div class="section-title">
          <i data-feather="activity"></i> Status Monitoring
        </div>

        {{-- Kembali ke RM --}}
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-semibold">Kembali ke RM</span>
            <span class="small text-muted">{{ $sudahKembali }} / {{ $totalRecord }}</span>
          </div>
          @php $pKembali = $totalRecord > 0 ? round(($sudahKembali/$totalRecord)*100) : 0; @endphp
          <div class="mini-progress">
            <div class="mini-progress-bar bg-primary" style="width:{{ $pKembali }}%"></div>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span class="small text-success">✓ Sudah: {{ $sudahKembali }}</span>
            <span class="small text-danger">✗ Belum: {{ $belumKembali }}</span>
          </div>
        </div>

        {{-- Analisa --}}
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-semibold">Analisa</span>
            <span class="small text-muted">{{ $sudahAnalisa }} / {{ $totalRecord }}</span>
          </div>
          @php $pAnalisa = $totalRecord > 0 ? round(($sudahAnalisa/$totalRecord)*100) : 0; @endphp
          <div class="mini-progress">
            <div class="mini-progress-bar bg-purple" style="width:{{ $pAnalisa }}%; background:#8b5cf6;"></div>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span class="small text-success">✓ Sudah: {{ $sudahAnalisa }}</span>
            <span class="small text-danger">✗ Belum: {{ $belumAnalisa }}</span>
          </div>
        </div>

        {{-- Laporan Pembedahan --}}
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-semibold">Laporan Pembedahan</span>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-success bg-opacity-10 text-success">
              ✓ Lengkap: {{ $pembedahanLengkap }}
            </span>
            <span class="badge bg-warning bg-opacity-10 text-warning">
              ⚠ Tdk Lengkap: {{ $pembedahanTidakLengkap }}
            </span>
            <span class="badge bg-secondary bg-opacity-10 text-muted">
              — Kosong: {{ $pembedahanKosong }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Breakdown Guarantor --}}
  <div class="col-md-4 fade-in-up" style="animation-delay:350ms">
    <div class="card h-100">
      <div class="card-body">
        <div class="section-title">
          <i data-feather="users"></i> Top Guarantor
        </div>
        @forelse($byGuarantor as $item)
          @php
            $pct = $totalRecord > 0 ? round(($item->total / $totalRecord) * 100) : 0;
            $colors = ['#0F5DA6','#05a34a','#fbbc06','#ff3366','#66d1d1'];
            $color  = $colors[$loop->index % count($colors)];
          @endphp
          <div class="guarantor-item">
            <div class="flex-grow-1 me-3">
              <div class="d-flex justify-content-between">
                <span class="small fw-semibold">{{ $item->guarantor ?: 'Tidak Diisi' }}</span>
                <span class="small text-muted">{{ $item->total }} ({{ $pct }}%)</span>
              </div>
              <div class="mini-progress mt-1">
                <div class="mini-progress-bar" style="width:{{ $pct }}%; background:{{ $color }};"></div>
              </div>
            </div>
          </div>
        @empty
          <p class="text-muted small">Belum ada data.</p>
        @endforelse

        <div class="mt-3 pt-2 border-top">
          <div class="d-flex justify-content-between">
            <span class="small text-muted">Bulan lalu</span>
            <span class="small fw-bold">{{ $bulanLalu }} RM</span>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span class="small text-muted">Bulan ini</span>
            <span class="small fw-bold {{ $bulanIni >= $bulanLalu ? 'text-success' : 'text-danger' }}">
              {{ $bulanIni }} RM
              @if($bulanLalu > 0)
                ({{ $bulanIni >= $bulanLalu ? '+' : '' }}{{ round((($bulanIni - $bulanLalu) / $bulanLalu) * 100) }}%)
              @endif
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- ===== ROW 3: RECENT RECORDS ===== --}}
<div class="row g-3 fade-in-up" style="animation-delay:400ms">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="section-title mb-0">
            <i data-feather="clock"></i> Rekam Medis Terbaru
          </div>
          <a href="{{ route('medical-records.index') }}" class="btn btn-outline-primary btn-sm">
            Lihat Semua <i data-feather="arrow-right" style="width:13px;height:13px;" class="ms-1"></i>
          </a>
        </div>
        <div class="table-responsive">
          <table class="table recent-table mb-0">
            <thead>
              <tr>
                <th>Billing No</th>
                <th>Nama Pasien</th>
                <th>Guarantor</th>
                <th>Ruangan</th>
                <th>Tgl Pulang</th>
                <th>Status Berkas</th>
                <th>RM / Analisa</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentRecords as $rec)
              <tr class="{{ $rec->is_rm_lengkap ? 'table-row-complete' : 'table-row-incomplete' }}">
                <td class="text-muted text-mono">{{ $rec->billing_no ?: '-' }}</td>
                <td>
                  <div class="fw-semibold">{{ $rec->nama_pasien ?: '-' }}</div>
                  <div class="text-muted" style="font-size:0.75rem">No RM: <span class="text-mono">{{ $rec->no_rm }}</span></div>
                </td>
                <td>{{ $rec->guarantor ?: '-' }}</td>
                <td>{{ $rec->ruangan ?: $rec->ruangan_afya ?: '-' }}</td>
                <td>{{ $rec->tanggal_pulang ? \Carbon\Carbon::parse($rec->tanggal_pulang)->format('d/m/Y') : '-' }}</td>
                <td>
                  @if($rec->is_rm_lengkap)
                    <span class="badge badge-success">LENGKAP</span>
                  @else
                    <span class="badge badge-warning">TDK LENGKAP</span>
                  @endif
                </td>
                <td>
                  <span class="text-{{ $rec->status_kembali_rm ? 'success' : 'danger' }}" title="Kembali ke RM">●</span>
                  <span class="text-{{ $rec->status_analisa ? 'success' : 'danger' }}" title="Analisa">●</span>
                </td>
                <td>
                  <a href="{{ route('medical-records.show', $rec->id) }}" class="btn btn-outline-info btn-sm py-0 px-2">
                    <i data-feather="eye" style="width:13px;height:13px;"></i>
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Belum ada data rekam medis.</td>
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
  feather.replace();

  // Donut Chart
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const textColor = isDark ? '#e2e8f0' : '#0b132b';

  const ctx = document.getElementById('donutChart').getContext('2d');
  const donut = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Lengkap', 'Tidak Lengkap'],
      datasets: [{
        data: [{{ $lengkap }}, {{ $tidakLengkap }}],
        backgroundColor: ['#05a34a', '#fbbc06'],
        borderColor: isDark ? '#15234b' : '#ffffff',
        borderWidth: 3,
        hoverOffset: 6,
      }]
    },
    options: {
      cutout: '72%',
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
              return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
            }
          }
        }
      },
      animation: { animateRotate: true, duration: 1000 }
    },
    plugins: [{
      id: 'centerText',
      afterDraw(chart) {
        const { ctx, chartArea: { width, height, left, top } } = chart;
        ctx.save();
        const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
        const pct   = total > 0 ? Math.round((chart.data.datasets[0].data[0] / total) * 100) : 0;
        ctx.font = 'bold 1.4rem Overpass, sans-serif';
        ctx.fillStyle = isDark ? '#e2e8f0' : '#0b132b';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(pct + '%', left + width / 2, top + height / 2 - 8);
        ctx.font = '0.7rem Roboto, sans-serif';
        ctx.fillStyle = '#7987a1';
        ctx.fillText('Lengkap', left + width / 2, top + height / 2 + 14);
        ctx.restore();
      }
    }]
  });

  // Re-render chart on theme toggle
  document.getElementById('theme-toggler').addEventListener('click', function() {
    setTimeout(() => {
      const dark = document.documentElement.getAttribute('data-theme') === 'dark';
      donut.data.datasets[0].borderColor = dark ? '#15234b' : '#ffffff';
      donut.update();
    }, 50);
  });
});
</script>
@endsection
