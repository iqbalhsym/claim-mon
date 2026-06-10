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

      <!-- Truncate Form -->
      <form action="{{ route('claim-records.truncate') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua data klaim? Tindakan ini tidak dapat dibatalkan.')" class="mb-0">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-3">
          <i data-feather="trash-2" style="width:13px;height:13px;" class="me-1"></i>Kosongkan Database
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
          <h6 class="card-title mb-0">Total: {{ number_format($totalRecords) }} Klaim Terdaftar</h6>
          
          <form action="{{ route('claim-records.index') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari Pasien / No RM / Dokter..." value="{{ $search }}" style="width: 200px;">
            <button type="submit" class="btn btn-outline-primary btn-sm">Cari</button>
            @if($search)
              <a href="{{ route('claim-records.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            @endif
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
                <th class="text-end">Total Tarif</th>
                <th class="text-end">Selisih</th>
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
          {{ $records->appends(['search' => $search])->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
