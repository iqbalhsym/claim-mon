@extends('layouts.noble_layout')

@section('title', 'Detail KSM ' . $ksm . ' - ' . ($jenisRawat === 'ranap' ? 'Ranap' : 'Rajal'))

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
  #ksmDetailDoctorsTable th {
    cursor: pointer;
    user-select: none;
  }
</style>
@endsection

@section('content')
@php
  // Group stats by DPJP and Month for KSM pivot
  $ksmPivot = [];
  $uniqueKsmMonths = [];
  foreach ($stats as $row) {
      $mKey = $row->month_key;
      $docName = $row->dpjp ?: 'Tanpa Nama Dokter';
      if (!in_array($mKey, $uniqueKsmMonths)) {
          $uniqueKsmMonths[] = $mKey;
      }
      $ksmPivot[$docName][$mKey] = [
          'patients' => (int)$row->patient_count,
          'total_tarif' => (float)$row->total_total_tarif,
          'balance' => (float)$row->total_selisih
      ];
  }
  sort($uniqueKsmMonths);
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
  <div>
    <h4 class="mb-1 page-title">Laporan KSM / Spesialis: {{ $ksm }} ({{ $jenisRawat === 'ranap' ? 'Ranap' : 'Rajal' }})</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.' . $jenisRawat) }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route($jenisRawat === 'ranap' ? 'claim-records.dpjp.ranap' : 'claim-records.dpjp.rajal', ['month' => $selectedMonth, 'tab' => 'ksm']) }}">Laporan DPJP</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $ksm }}</li>
      </ol>
    </nav>
  </div>
  <div>
    <a href="{{ route($jenisRawat === 'ranap' ? 'claim-records.dpjp.ranap' : 'claim-records.dpjp.rajal', ['month' => $selectedMonth, 'tab' => 'ksm']) }}" class="btn btn-outline-secondary btn-sm py-1.5 px-3">
      <i data-feather="arrow-left" style="width:14px;height:14px;" class="me-1"></i> Kembali ke Laporan per KSM (Spesialis)
    </a>
  </div>
</div>

{{-- Filter Bulan-Tahun --}}
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body py-2">
    <form action="{{ route('claim-records.dpjp.ksm', ['jenis_rawat' => $jenisRawat, 'ksm' => $ksm]) }}" method="GET" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
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
          <a href="{{ route('claim-records.dpjp.ksm', ['jenis_rawat' => $jenisRawat, 'ksm' => $ksm]) }}" class="btn btn-outline-secondary btn-sm py-1 px-3">Reset</a>
        @endif
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
      <div class="col-12 col-md-4">
        <div class="card-stat-header mb-0">
          <div class="stat-label-mini">Total Pasien</div>
          <div class="stat-value-mini">{{ number_format($totalPatients) }}</div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card-stat-header mb-0">
          <div class="stat-label-mini">Total Tarif INACBG</div>
          <div class="stat-value-mini">Rp {{ number_format($totalTarif, 0, ',', '.') }}</div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card-stat-header mb-0">
          <div class="stat-label-mini">Total Balance Positif/Negatif</div>
          <div class="stat-value-mini {{ $totalBalance >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($totalBalance, 0, ',', '.') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Charts --}}
<div class="row mb-4">
  <div class="col-md-5 mb-3">
    <div class="card border shadow-sm h-100 mb-0">
      <div class="card-body">
        <h6 class="card-title mb-3" style="font-size:0.88rem;">
          <i data-feather="bar-chart-2" class="text-primary me-2" style="width:16px;height:16px;"></i>Top 5 Diagram Jumlah Pasien
        </h6>
        <div style="position: relative; height: 260px;">
          <canvas id="top5PatientsChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-7 mb-3">
    <div class="card border shadow-sm h-100 mb-0">
      <div class="card-body">
        <h6 class="card-title mb-3" style="font-size:0.88rem;">
          <i data-feather="dollar-sign" class="text-primary me-2" style="width:16px;height:16px;"></i>Top 5 Perbandingan Tarif INACBG &amp; Balance (Rupiah)
        </h6>
        <div style="position: relative; height: 260px;">
          <canvas id="top5FinanceChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Tabel Detail Dokter --}}
<div class="card shadow-sm border-0">
  <div class="card-body">
    <h6 class="card-title mb-3">Tabel Rincian Dokter</h6>
    
    <div class="table-responsive">
      <table id="ksmDetailDoctorsTable" class="table table-striped table-hover table-sm mb-0">
        <thead>
          <tr>
            <th>Nama Dokter (DPJP)</th>
            <th class="text-center">Jumlah Pasien</th>
            <th class="text-end">Tarif INACBG</th>
            <th class="text-end">Tarif RS</th>
            <th class="text-end">Balance Positif/Negatif</th>
          </tr>
        </thead>
        <tbody>
          @foreach($stats as $row)
            <tr>
              <td><b>{{ $row->dpjp ?: 'Tanpa Nama Dokter' }}</b></td>
              <td class="text-center font-weight-bold" data-order="{{ $row->patient_count }}">{{ $row->patient_count }}</td>
              <td class="text-end" data-order="{{ $row->total_total_tarif }}">Rp {{ number_format($row->total_total_tarif, 0, ',', '.') }}</td>
              <td class="text-end" data-order="{{ $row->total_tarif_rs }}">Rp {{ number_format($row->total_tarif_rs, 0, ',', '.') }}</td>
              <td class="text-end fw-semibold {{ $row->total_selisih >= 0 ? 'text-success' : 'text-danger' }}" data-order="{{ $row->total_selisih }}">
                Rp {{ number_format($row->total_selisih, 0, ',', '.') }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
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
  $('#ksmDetailDoctorsTable').DataTable({
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
    },
    "pageLength": 25,
    "order": [[1, "desc"]], // Order by Patients count desc by default
    "columnDefs": [
      { "targets": [1, 2, 3, 4], "orderable": true }
    ]
  });

  // Chart setup
  const pivotData = @json($ksmPivot);
  const uniqueMonths = @json($uniqueKsmMonths);

  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';
  const labelColor = isDark ? '#8899bb' : '#7987a1';

  // Find top 5 doctors by Patient Count across all months
  const doctorsList = Object.keys(pivotData);
  const docTotals = doctorsList.map(doc => {
    let total = 0;
    uniqueMonths.forEach(m => {
      total += (pivotData[doc][m] ? parseInt(pivotData[doc][m].patients) : 0);
    });
    return { name: doc, total: total };
  });
  docTotals.sort((a, b) => b.total - a.total);
  const top5Docs = docTotals.slice(0, 5).map(d => d.name);

  // Colors matching the user's design
  const colors = ['#05a34a', '#fbbc06', '#ff3366', '#0f5da6', '#8a2be2'];

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

  // 1. Top 5 Patients Chart
  if (top5Docs.length > 0) {
    const monthLabels = uniqueMonths.map(mKey => {
      try {
        const parts = mKey.split('-');
        const date = new Date(parts[0], parts[1] - 1, 1);
        return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
      } catch (e) {
        return mKey;
      }
    });

    const datasets = top5Docs.map((doc, idx) => {
      const dataPoints = uniqueMonths.map(m => {
        return pivotData[doc][m] ? parseInt(pivotData[doc][m].patients) : 0;
      });
      return {
        label: doc,
        data: dataPoints,
        backgroundColor: colors[idx % colors.length],
        borderRadius: 4
      };
    });

    const ctxPatients = document.getElementById('top5PatientsChart').getContext('2d');
    new Chart(ctxPatients, {
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
            labels: { color: labelColor, font: { size: 9 } }
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

  // 2. Top 5 Finance Chart (INACBG vs Balance)
  const financeDocTotals = doctorsList.map(doc => {
    let total = 0;
    uniqueMonths.forEach(m => {
      total += (pivotData[doc][m] ? parseFloat(pivotData[doc][m].total_tarif) : 0);
    });
    return { name: doc, total: total };
  });
  financeDocTotals.sort((a, b) => b.total - a.total);
  const top5FinanceDocs = financeDocTotals.slice(0, 5).map(d => d.name);

  // Financial datalabels plugin
  const financeDatalabelsPlugin = {
    id: 'financeDatalabels',
    afterDatasetsDraw(chart) {
      const { ctx } = chart;
      ctx.save();
      chart.data.datasets.forEach((dataset, datasetIndex) => {
        const meta = chart.getDatasetMeta(datasetIndex);
        if (meta.hidden) return;
        
        meta.data.forEach((bar, index) => {
          const val = dataset.data[index];
          if (val === null || val === undefined || val === 0) return;
          
          const absVal = Math.abs(val);
          let label = '';
          if (absVal >= 1e9) {
            label = (val / 1e9).toFixed(1) + ' M';
          } else if (absVal >= 1e6) {
            label = (val / 1e6).toFixed(1) + ' jt';
          } else if (absVal >= 1e3) {
            label = (val / 1e3).toFixed(0) + ' rb';
          } else {
            label = val.toString();
          }
          
          ctx.fillStyle = isDark ? '#ffffff' : '#2e3a59';
          ctx.font = 'bold 8px sans-serif';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'bottom';
          ctx.fillText(label, bar.x, bar.y - 4);
        });
      });
      ctx.restore();
    }
  };

  if (top5FinanceDocs.length > 0) {
    const financeLabels = top5FinanceDocs;
    const financeTarifData = top5FinanceDocs.map(doc => {
      let sum = 0;
      uniqueMonths.forEach(m => {
        sum += (pivotData[doc][m] ? parseFloat(pivotData[doc][m].total_tarif) : 0);
      });
      return sum;
    });
    const financeBalanceData = top5FinanceDocs.map(doc => {
      let sum = 0;
      uniqueMonths.forEach(m => {
        sum += (pivotData[doc][m] ? parseFloat(pivotData[doc][m].balance) : 0);
      });
      return sum;
    });

    const ctxFinance = document.getElementById('top5FinanceChart').getContext('2d');
    new Chart(ctxFinance, {
      type: 'bar',
      plugins: [financeDatalabelsPlugin],
      data: {
        labels: financeLabels,
        datasets: [
          {
            label: 'Tarif INACBG',
            data: financeTarifData,
            backgroundColor: '#4e5bf2',
            borderRadius: 4
          },
          {
            label: 'Balance',
            data: financeBalanceData,
            backgroundColor: financeBalanceData.map(val => val >= 0 ? '#05a34a' : '#ff3366'),
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
            labels: { color: labelColor, font: { size: 10 } }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: labelColor, font: { size: 9 } }
          },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: labelColor,
              callback: function(value) {
                const absVal = Math.abs(value);
                let label = '';
                if (absVal >= 1e9) {
                  label = (value / 1e9) + ' M';
                } else if (absVal >= 1e6) {
                  label = (value / 1e6) + ' jt';
                } else if (absVal >= 1e3) {
                  label = (value / 1e3) + ' rb';
                } else {
                  label = value;
                }
                return 'Rp ' + label;
              }
            }
          }
        }
      }
    });
  }
});
</script>
@endsection
