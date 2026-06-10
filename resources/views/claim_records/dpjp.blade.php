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
    <h6 class="card-title mb-4">Statistik Kinerja Dokter per Bulan</h6>

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
              <td class="text-center font-weight-bold">{{ $row->patient_count }}</td>
              <td class="text-end">Rp {{ number_format($row->total_total_tarif, 0, ',', '.') }}</td>
              <td class="text-end">Rp {{ number_format($row->total_tarif_rs, 0, ',', '.') }}</td>
              <td class="text-end fw-semibold {{ $row->total_selisih >= 0 ? 'text-success' : 'text-danger' }}">
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
