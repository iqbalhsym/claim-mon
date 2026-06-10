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
          <div class="stat-label-mini">Total Tarif</div>
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
          <div class="stat-label-mini">Total Selisih</div>
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
    <h6 class="card-title mb-1">Statistik Kinerja Dokter per Bulan</h6>
    <p class="text-muted small mb-3"><i data-feather="info" class="text-info me-1" style="width:14px;height:14px;"></i> <b>Tip:</b> Klik header kolom (seperti <b>Jumlah Pasien</b>, <b>Total Tarif</b>, atau <b>Selisih</b>) untuk mengurutkan data dari yang <b>terbesar ke terendah</b> (atau sebaliknya).</p>

    <div class="table-responsive">
      <table id="dpjpTable" class="table table-striped table-hover table-sm mb-0">
        <thead>
          <tr>
            <th>Bulan</th>
            <th>Nama Dokter (DPJP)</th>
            <th class="text-center">Jumlah Pasien</th>
            <th class="text-end">Total Tarif</th>
            <th class="text-end">Tarif RS</th>
            <th class="text-end">Selisih</th>
          </tr>
        </thead>
        <tbody>
          @foreach($stats as $row)
            @php
              // Format month_key e.g. "2026-01" to "Januari 2026"
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
});
</script>
@endsection
