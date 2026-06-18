@extends('layouts.noble_layout')

@section('title', 'Laporan DPJP')

@section('css')
<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
  .card-stat-header {
    background-color: var(--sidebar-hover-bg);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
  }
  .stat-label-mini {
    font-size: 0.72rem;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 600;
  }
  .stat-value-mini {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--dark-color);
  }
  /* Customizing DataTables for our dark/light theme */
  .dataTables_wrapper .dataTables_length, 
  .dataTables_wrapper .dataTables_filter, 
  .dataTables_wrapper .dataTables_info, 
  .dataTables_wrapper .dataTables_paginate {
    color: var(--text-color) !important;
    font-size: 0.82rem;
    margin-top: 10px;
    margin-bottom: 10px;
  }
  .dataTables_wrapper .form-control-sm,
  .dataTables_wrapper .form-select-sm {
    border-color: var(--border-color);
    background-color: var(--card-bg);
    color: var(--text-color);
  }
  .page-item.active .page-link {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
  [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
    background-color: rgba(255, 255, 255, 0.02) !important;
    color: var(--text-color) !important;
  }
  [data-theme="dark"] .page-link {
    background-color: #1e2e5c !important;
    border-color: var(--border-color) !important;
    color: var(--text-color) !important;
  }
  #dpjpTable th {
    cursor: pointer;
    user-select: none;
  }

  /* Custom Tab Styles & Overrides to fix visibility conflict */
  .nav-tabs {
    border-bottom: 2px solid var(--border-color) !important;
  }
  .nav-tabs .nav-item {
    margin-bottom: -2px;
  }
  .nav-tabs .nav-link {
    color: var(--text-muted) !important;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    background: transparent !important;
    padding: 10px 20px !important;
    font-weight: 500;
    font-size: 13px !important;
    border-radius: 0px !important;
    margin-bottom: 0px !important;
    display: inline-flex !important;
    align-items: center;
  }
  .nav-tabs .nav-link:hover {
    color: var(--primary-color) !important;
    border-bottom-color: var(--border-color) !important;
    background: transparent !important;
  }
  .nav-tabs .nav-link.active {
    color: var(--primary-color) !important;
    border-bottom-color: var(--primary-color) !important;
    background: transparent !important;
    font-weight: 600;
  }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
  <div>
    <h4 class="mb-1 page-title">Laporan DPJP (Dokter Utama)</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Laporan DPJP</li>
      </ol>
    </nav>
  </div>
</div>

{{-- Filter Bulan-Tahun --}}
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body py-2">
    <form action="{{ route('claim-records.dpjp') }}" method="GET" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="small fw-semibold text-muted text-nowrap"><i data-feather="calendar" class="text-primary me-1" style="width:16px;height:16px;"></i>Filter Bulan Pulang:</span>
        <select name="month" class="form-select form-select-sm" style="width: 200px; font-size: 0.8rem;">
          <option value="">-- Semua Bulan --</option>
          @foreach($availableMonths as $mKey)
            @php
              try {
                $carbon = \Carbon\Carbon::createFromFormat('Y-m', $mKey);
                $label = $carbon->translatedFormat('F Y');
              } catch (\Exception $e) {
                $label = $mKey;
              }
            @endphp
            <option value="{{ $mKey }}" {{ $selectedMonth == $mKey ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm py-1 px-3">Filter</button>
        @if($selectedMonth)
          <a href="{{ route('claim-records.dpjp') }}" class="btn btn-outline-secondary btn-sm py-1 px-3">Reset</a>
        @endif
        <a href="{{ route('claim-records.dpjp.export', ['month' => $selectedMonth]) }}" class="btn btn-success btn-sm py-1 px-3 text-white ms-1">
          <i data-feather="download" style="width:14px;height:14px;" class="me-1"></i>Ekspor Excel
        </a>
      </div>
      @if($selectedMonth)
        <div class="small text-muted">
          Menampilkan data bulan: <b>
            @php
              try {
                $carbon = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth);
                echo $carbon->translatedFormat('F Y');
              } catch (\Exception $e) {
                echo $selectedMonth;
              }
            @endphp
          </b>
        </div>
      @endif
    </form>
  </div>
</div>

{{-- Ringkasan Akumulasi --}}
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-6 col-md-3">
        <div class="card-stat-header mb-0">
          <div class="stat-label-mini">Total Pasien</div>
          <div class="stat-value-mini">{{ number_format($grandTotalPatients) }}</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card-stat-header mb-0">
          <div class="stat-label-mini">Total Tarif INACBG</div>
          <div class="stat-value-mini">Rp {{ number_format($grandTotalTarif, 0, ',', '.') }}</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card-stat-header mb-0">
          <div class="stat-label-mini">Total Tarif RS</div>
          <div class="stat-value-mini">Rp {{ number_format($grandTotalRs, 0, ',', '.') }}</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card-stat-header mb-0">
          <div class="stat-label-mini">Total Balance Positif/Negatif</div>
          <div class="stat-value-mini {{ $grandTotalSelisih >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($grandTotalSelisih, 0, ',', '.') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Tabel Data Laporan --}}
<div class="card shadow-sm border-0">
  <div class="card-body">
    <h6 class="card-title mb-3">Statistik Kinerja Dokter per Bulan</h6>

    @php
      // Group stats by DPJP and Month for side-by-side pivot comparison
      $dpjpPivot = [];
      $uniqueMonths = [];
      foreach ($stats as $row) {
          $mKey = $row->month_key;
          $docName = $row->dpjp ?: 'Tanpa Nama Dokter';
          if (!in_array($mKey, $uniqueMonths)) {
              $uniqueMonths[] = $mKey;
          }
          $dpjpPivot[$docName][$mKey] = [
              'patients' => $row->patient_count,
              'total_tarif' => $row->total_total_tarif + $row->total_tarif_rs,
              'balance' => $row->total_total_tarif
          ];
      }
      sort($uniqueMonths);
    @endphp

    <!-- Tabs Nav -->
    <ul class="nav nav-tabs" id="dpjpReportTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail-pane" type="button" role="tab" aria-controls="detail-pane" aria-selected="true">
          <i data-feather="list" style="width:14px;height:14px;" class="me-1"></i> Detail Laporan Bulanan
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="comparison-tab" data-bs-toggle="tab" data-bs-target="#comparison-pane" type="button" role="tab" aria-controls="comparison-pane" aria-selected="false">
          <i data-feather="columns" style="width:14px;height:14px;" class="me-1"></i> Sandingan Perbandingan Bulanan
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="ksm-tab" data-bs-toggle="tab" data-bs-target="#ksm-pane" type="button" role="tab" aria-controls="ksm-pane" aria-selected="false">
          <i data-feather="package" style="width:14px;height:14px;" class="me-1"></i> Laporan per KSM (Spesialis)
        </button>
      </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content mt-3" id="dpjpReportTabContent">
      <!-- Tab 1: Detailed List -->
      <div class="tab-pane fade show active" id="detail-pane" role="tabpanel" aria-labelledby="detail-tab">
        <p class="text-muted small mb-3">
          <i data-feather="info" class="text-info me-1" style="width:14px;height:14px;"></i> 
          <b>Tip:</b> Klik header kolom (seperti <b>Jumlah Pasien</b>, <b>Total Tarif+INACBG</b>, atau <b>Balance Positif/Negatif</b>) untuk mengurutkan data dari yang <b>terbesar ke terendah</b> (atau sebaliknya).
        </p>

        <div class="table-responsive">
          <table id="dpjpTable" class="table table-striped table-hover table-sm mb-0">
            <thead>
              <tr>
                <th>Bulan</th>
                <th>Nama Dokter (DPJP)</th>
                <th class="text-center">Jumlah Pasien</th>
                <th class="text-end">Total Tarif+INACBG</th>
                <th class="text-end">Tarif RS</th>
                <th class="text-end">Balance Positif/Negatif</th>
              </tr>
            </thead>
            <tbody>
              @foreach($stats as $row)
                @php
                  try {
                    $carbon = \Carbon\Carbon::createFromFormat('Y-m', $row->month_key);
                    $monthName = $carbon->translatedFormat('F Y');
                  } catch (\Exception $e) {
                    $monthName = $row->month_key;
                  }
                @endphp
                <tr>
                  <td><span class="badge bg-light text-dark font-weight-bold">{{ $monthName }}</span></td>
                  <td><b>{{ $row->dpjp ?: 'Tanpa Nama Dokter' }}</b></td>
                  <td class="text-center font-weight-bold" data-order="{{ $row->patient_count }}">{{ $row->patient_count }}</td>
                  <td class="text-end" data-order="{{ $row->total_total_tarif + $row->total_tarif_rs }}">Rp {{ number_format($row->total_total_tarif + $row->total_tarif_rs, 0, ',', '.') }}</td>
                  <td class="text-end" data-order="{{ $row->total_tarif_rs }}">Rp {{ number_format($row->total_tarif_rs, 0, ',', '.') }}</td>
                  <td class="text-end fw-semibold {{ $row->total_total_tarif >= 0 ? 'text-success' : 'text-danger' }}" data-order="{{ $row->total_total_tarif }}">
                    Rp {{ number_format($row->total_total_tarif, 0, ',', '.') }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 2: Side-by-Side Comparison -->
      <div class="tab-pane fade" id="comparison-pane" role="tabpanel" aria-labelledby="comparison-tab">
        @if(count($uniqueMonths) > 0)
          <!-- Comparison Chart -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="card border shadow-none">
                <div class="card-body">
                  <h6 class="card-title mb-3" style="font-size:0.88rem;">
                    <i data-feather="bar-chart-2" class="text-primary me-2" style="width:16px;height:16px;"></i>Grafik Perbandingan Pasien Top 5 Dokter
                  </h6>
                  <div style="position: relative; height: 260px;">
                    <canvas id="dpjpComparisonChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pivot Table -->
          <div class="table-responsive">
            <table id="dpjpComparisonTable" class="table table-striped table-hover table-sm mb-0">
              <thead>
                <tr>
                  <th class="align-middle">Nama Dokter (DPJP)</th>
                  @foreach($uniqueMonths as $mKey)
                    @php
                      try {
                        $carbon = \Carbon\Carbon::createFromFormat('Y-m', $mKey);
                        $monthLabel = $carbon->translatedFormat('F Y');
                      } catch (\Exception $e) {
                        $monthLabel = $mKey;
                      }
                    @endphp
                    <th class="text-center bg-light border-start small">{{ $monthLabel }}<br><span class="fw-normal text-muted">Pasien</span></th>
                    <th class="text-end bg-light small">{{ $monthLabel }}<br><span class="fw-normal text-muted">Tarif+INACBG</span></th>
                    <th class="text-end bg-light small">{{ $monthLabel }}<br><span class="fw-normal text-muted">Balance</span></th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($dpjpPivot as $docName => $monthData)
                  <tr>
                    <td><b>{{ $docName }}</b></td>
                    @foreach($uniqueMonths as $mKey)
                      @php
                        $data = $monthData[$mKey] ?? null;
                      @endphp
                      <td class="text-center border-start" data-order="{{ $data['patients'] ?? 0 }}">
                        {{ $data ? number_format($data['patients']) : '-' }}
                      </td>
                      <td class="text-end" data-order="{{ $data['total_tarif'] ?? 0 }}">
                        {{ $data ? 'Rp ' . number_format($data['total_tarif'], 0, ',', '.') : '-' }}
                      </td>
                      <td class="text-end fw-semibold {{ ($data['balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}" data-order="{{ $data['balance'] ?? 0 }}">
                        {{ $data ? 'Rp ' . number_format($data['balance'], 0, ',', '.') : '-' }}
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center py-4 text-muted">Belum ada data untuk perbandingan.</div>
        @endif
      </div>
      
      <!-- Tab 3: KSM List -->
      <div class="tab-pane fade" id="ksm-pane" role="tabpanel" aria-labelledby="ksm-tab">
        <p class="text-muted small mb-3">
          <i data-feather="info" class="text-info me-1" style="width:14px;height:14px;"></i> 
          <b>Tip:</b> Klik header kolom (seperti <b>Jumlah Pasien</b>, <b>Total Tarif+INACBG</b>, atau <b>Balance Positif/Negatif</b>) untuk mengurutkan data per KSM dari yang <b>terbesar ke terendah</b> (atau sebaliknya).
        </p>

        <div class="table-responsive">
          <table id="ksmTable" class="table table-striped table-hover table-sm mb-0">
            <thead>
              <tr>
                <th>Bulan</th>
                <th>KSM / Spesialis</th>
                <th class="text-center">Jumlah Pasien</th>
                <th class="text-end">Total Tarif+INACBG</th>
                <th class="text-end">Tarif RS</th>
                <th class="text-end">Balance Positif/Negatif</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ksmStats as $row)
                @php
                  try {
                    $carbon = \Carbon\Carbon::createFromFormat('Y-m', $row->month_key);
                    $monthName = $carbon->translatedFormat('F Y');
                  } catch (\Exception $e) {
                    $monthName = $row->month_key;
                  }
                @endphp
                <tr>
                  <td><span class="badge bg-light text-dark font-weight-bold">{{ $monthName }}</span></td>
                  <td><b>{{ $row->ksm ?: 'Tidak Terdaftar/Lain-lain' }}</b></td>
                  <td class="text-center font-weight-bold" data-order="{{ $row->patient_count }}">{{ $row->patient_count }}</td>
                  <td class="text-end" data-order="{{ $row->total_total_tarif + $row->total_tarif_rs }}">Rp {{ number_format($row->total_total_tarif + $row->total_tarif_rs, 0, ',', '.') }}</td>
                  <td class="text-end" data-order="{{ $row->total_tarif_rs }}">Rp {{ number_format($row->total_tarif_rs, 0, ',', '.') }}</td>
                  <td class="text-end fw-semibold {{ $row->total_total_tarif >= 0 ? 'text-success' : 'text-danger' }}" data-order="{{ $row->total_total_tarif }}">
                    Rp {{ number_format($row->total_total_tarif, 0, ',', '.') }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@section('js')
<!-- jQuery & DataTables JS from CDN -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
  $('#dpjpTable').DataTable({
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
    },
    "pageLength": 25,
    "order": [[0, "desc"], [2, "desc"]], // Order by Month desc, then Patients count desc
    "columnDefs": [
      { "targets": [2, 3, 4, 5], "orderable": true }
    ]
  });

  $('#dpjpComparisonTable').DataTable({
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
    },
    "pageLength": 25,
    "order": [[0, "asc"]]
  });

  $('#ksmTable').DataTable({
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
    },
    "pageLength": 25,
    "order": [[0, "desc"], [2, "desc"]], // Order by Month desc, then Patients count desc
    "columnDefs": [
      { "targets": [2, 3, 4, 5], "orderable": true }
    ]
  });

  // Chart JS comparison setup
  const pivotData = @json($dpjpPivot);
  const uniqueMonths = @json($uniqueMonths);

  if (uniqueMonths.length > 0) {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';
    const labelColor = isDark ? '#8899bb' : '#7987a1';

    // Parse month labels for the chart
    const monthLabels = uniqueMonths.map(mKey => {
      try {
        const parts = mKey.split('-');
        const date = new Date(parts[0], parts[1] - 1, 1);
        return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
      } catch (e) {
        return mKey;
      }
    });

    const doctorsList = Object.keys(pivotData);
    const docTotals = doctorsList.map(doc => {
      let total = 0;
      uniqueMonths.forEach(m => {
        total += (pivotData[doc][m] ? pivotData[doc][m].patients : 0);
      });
      return { name: doc, total: total };
    });
    docTotals.sort((a, b) => b.total - a.total);
    const top5Docs = docTotals.slice(0, 5).map(d => d.name);

    const colors = ['#05a34a', '#fbbc06', '#ff3366', '#0f5da6', '#8a2be2'];
    const datasets = top5Docs.map((doc, idx) => {
      const dataPoints = uniqueMonths.map(m => {
        return pivotData[doc][m] ? pivotData[doc][m].patients : 0;
      });
      return {
        label: doc,
        data: dataPoints,
        backgroundColor: colors[idx % colors.length],
        borderRadius: 4
      };
    });

    // Custom inline plugin to draw datalabels above bars
    const chartDatalabelsPlugin = {
      id: 'chartDatalabels',
      afterDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        chart.data.datasets.forEach((dataset, datasetIndex) => {
          const meta = chart.getDatasetMeta(datasetIndex);
          if (meta.hidden) return;
          
          meta.data.forEach((bar, index) => {
            const val = dataset.data[index];
            if (val === null || val === undefined || val === 0) return;
            
            ctx.fillStyle = isDark ? '#ffffff' : '#2e3a59';
            ctx.font = 'bold 9px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.fillText(val, bar.x, bar.y - 4);
          });
        });
        ctx.restore();
      }
    };

    const ctx = document.getElementById('dpjpComparisonChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      plugins: [chartDatalabelsPlugin],
      data: {
        labels: monthLabels,
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: labelColor }
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
  }
});
</script>

