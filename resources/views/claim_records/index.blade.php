@extends('layouts.noble_layout')

@section('title', 'Data Klaim')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
  <div>
    <h4 class="mb-1 page-title">Data Klaim</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Data Klaim</li>
      </ol>
    </nav>
  </div>
</div>

{{-- Minimalist Import & Truncate Section --}}
<div class="card shadow-sm mb-3 fade-in-up" style="animation-delay: 50ms;">
  <div class="card-body py-2">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      
      <!-- Import Form -->
      <form action="{{ route('claim-records.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap mb-0">
        @csrf
        <span class="small fw-semibold text-muted text-nowrap">
          <i data-feather="upload-cloud" class="text-primary me-1" style="width:16px;height:16px;"></i>Impor Excel:
        </span>
        <input class="form-control form-control-sm py-1" type="file" id="file_excel" name="file_excel" accept=".xlsx,.xls,.csv" required style="width: 220px; font-size: 0.78rem;">
        <button type="submit" class="btn btn-primary btn-sm py-1 px-3">
          <i data-feather="upload" style="width:13px;height:13px;" class="me-1"></i>Mulai Impor
        </button>
      </form>

      <!-- Delete Data Form -->
      <form action="{{ route('claim-records.truncate') }}" method="POST" onsubmit="return confirmDelete()" class="d-flex align-items-center gap-2 flex-wrap mb-0">
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
              @if($search || $severity)
                <small class="text-muted">(Terfilter dari {{ number_format($totalRecords) }})</small>
              @endif
            </h6>
          </div>
          
          <form action="{{ route('claim-records.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-0">
            <!-- Severity Filter -->
            <select name="severity" class="form-select form-select-sm" style="width: 140px; font-size: 0.8rem;">
              <option value="">Semua Severity</option>
              <option value="I" {{ $severity === 'I' ? 'selected' : '' }}>Severity I (Ringan)</option>
              <option value="II" {{ $severity === 'II' ? 'selected' : '' }}>Severity II (Sedang)</option>
              <option value="III" {{ $severity === 'III' ? 'selected' : '' }}>Severity III (Berat)</option>
            </select>

            <!-- Search input -->
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari Pasien / No RM / Dokter..." value="{{ $search }}" style="width: 210px; font-size: 0.8rem;">
            
            <button type="submit" class="btn btn-primary btn-sm py-1 px-3">Filter</button>
            
            @if($search || $severity)
              <a href="{{ route('claim-records.index') }}" class="btn btn-outline-secondary btn-sm py-1 px-2">Reset</a>
            @endif

            <!-- Export button -->
            <a href="{{ route('claim-records.export', ['search' => $search, 'severity' => $severity]) }}" class="btn btn-outline-success btn-sm py-1 px-3">
              <i data-feather="download" style="width:13px;height:13px;" class="me-1"></i>Ekspor Excel
            </a>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th>No. RM</th>
                <th>Nama Pasien</th>
                <th class="text-center">Tgl Pulang</th>
                <th class="text-center">INACBG</th>
                <th class="text-center">Severity</th>
                <th class="text-end">Tarif RS</th>
                <th class="text-end">Total Tarif+INACBG</th>
                <th class="text-end">Balance Positif/Negatif</th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $rec)
                <tr>
                  <td class="text-mono small">{{ $rec->no_rm }}</td>
                  <td>
                    <div class="fw-semibold">{{ $rec->nama_pasien }}</div>
                    <small class="text-muted text-truncate d-inline-block" style="max-width: 180px;" title="{{ $rec->dpjp }}">
                      DPJP: {{ $rec->dpjp ?: '-' }}
                    </small>
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
                  <td class="text-end small">Rp {{ number_format($rec->tarif_rs + $rec->total_tarif, 0, ',', '.') }}</td>
                  <td class="text-end fw-semibold small {{ $rec->total_tarif >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($rec->total_tarif, 0, ',', '.') }}
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
          {{ $records->appends(['search' => $search, 'severity' => $severity])->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
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
</script>
@endsection
