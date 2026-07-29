@extends('layouts.noble_layout')

@section('title', 'Laporan Rekapan Perpenjamin ' . ($jenisRawat === 'ranap' ? 'Ranap' : 'Rajal'))

@section('css')
<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
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
  #guarantorTable th {
    cursor: pointer;
    user-select: none;
  }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
  <div>
    <h4 class="mb-1 page-title">Laporan Rekapan Perpenjamin - {{ $jenisRawat === 'ranap' ? 'Ranap' : 'Rajal' }}</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.' . $jenisRawat) }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Laporan Rekapan Perpenjamin</li>
      </ol>
    </nav>
  </div>
</div>

{{-- Minimalist Import & Truncate Section --}}
<div class="card shadow-sm mb-3 fade-in-up" style="animation-delay: 50ms;">
  <div class="card-body py-2">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      
      <!-- Import Form -->
      <form id="import-form" action="{{ route('billing-records.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap mb-0">
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
      <form action="{{ route('billing-records.truncate', ['jenis_rawat' => $jenisRawat]) }}" method="POST" onsubmit="return confirmDelete()" class="d-flex align-items-center gap-2 flex-wrap mb-0">
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

{{-- Filter Bulan-Tahun --}}
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body py-2">
    <form action="{{ route($jenisRawat === 'ranap' ? 'claim-records.guarantor.ranap' : 'claim-records.guarantor.rajal') }}" method="GET" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
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
          <a href="{{ route($jenisRawat === 'ranap' ? 'claim-records.guarantor.ranap' : 'claim-records.guarantor.rajal') }}" class="btn btn-outline-secondary btn-sm py-1 px-3">Reset</a>
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

{{-- Tabel Data Laporan --}}
<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="table-responsive">
      <table id="guarantorTable" class="table table-striped table-hover table-sm mb-0">
        <thead>
          <tr>
            <th class="text-center">No</th>
            <th>Penjamin</th>
            <th class="text-center">Jumlah Kunjungan</th>
            <th class="text-end">Ajuan Klaim</th>
            <th class="text-end">Dibayar Pasien</th>
            <th class="text-end">Discount RS</th>
            <th class="text-end">Net Billing</th>
            <th class="text-end">Jasa RS</th>
            <th class="text-end">Jasa Pelayanan</th>
          </tr>
        </thead>
        <tbody>
          @php
            $no = 1;
            $grandKunjungan = 0;
            $grandAjuan = 0;
            $grandDibayar = 0;
            $grandDiscount = 0;
            $grandNet = 0;
            $grandJasaRS = 0;
            $grandJasaPelayanan = 0;
          @endphp
          @foreach($stats as $row)
            @php
              $grandKunjungan += $row->kunjungan;
              $grandAjuan += $row->ajuan_klaim;
              $grandDibayar += $row->dibayar_pasien;
              $grandDiscount += $row->discount_rs;
              $grandNet += $row->net_billing;
              $grandJasaRS += $row->jasa_rs;
              $grandJasaPelayanan += $row->jasa_pelayanan;
            @endphp
            <tr>
              <td class="text-center">{{ $no++ }}</td>
              <td><b>{{ $row->guarantor ?: 'Tidak Terdaftar / Umum' }}</b></td>
              <td class="text-center font-weight-bold" data-order="{{ $row->kunjungan }}">{{ number_format($row->kunjungan) }}</td>
              <td class="text-end" data-order="{{ $row->ajuan_klaim }}">Rp {{ number_format($row->ajuan_klaim, 0, ',', '.') }}</td>
              <td class="text-end" data-order="{{ $row->dibayar_pasien }}">Rp {{ number_format($row->dibayar_pasien, 0, ',', '.') }}</td>
              <td class="text-end" data-order="{{ $row->discount_rs }}">Rp {{ number_format($row->discount_rs, 0, ',', '.') }}</td>
              <td class="text-end" data-order="{{ $row->net_billing }}">Rp {{ number_format($row->net_billing, 0, ',', '.') }}</td>
              <td class="text-end" data-order="{{ $row->jasa_rs }}">Rp {{ number_format($row->jasa_rs, 0, ',', '.') }}</td>
              <td class="text-end" data-order="{{ $row->jasa_pelayanan }}">Rp {{ number_format($row->jasa_pelayanan, 0, ',', '.') }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="bg-light fw-bold">
          <tr>
            <td colspan="2" class="text-center">GRAND TOTAL</td>
            <td class="text-center">{{ number_format($grandKunjungan) }}</td>
            <td class="text-end">Rp {{ number_format($grandAjuan, 0, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format($grandDibayar, 0, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format($grandDiscount, 0, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format($grandNet, 0, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format($grandJasaRS, 0, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format($grandJasaPelayanan, 0, ',', '.') }}</td>
          </tr>
        </tfoot>
      </table>
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

@endsection

@section('js')
<!-- jQuery & DataTables JS from CDN -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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

$(document).ready(function() {
  var t = $('#guarantorTable').DataTable({
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
    },
    "pageLength": 25,
    "order": [[2, "desc"]],
    "columnDefs": [
      { "searchable": false, "orderable": false, "targets": 0 },
      { "targets": [2, 3, 4, 5, 6, 7, 8], "orderable": true }
    ]
  });

  t.on('order.dt search.dt', function () {
    let i = 1;
    t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
        this.data(i++);
    });
  }).draw();
});
</script>
@endsection
