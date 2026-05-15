@extends('layouts.noble_layout')

@section('title', 'Data Rekam Medis')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin mb-4">
  <div>
    <h4 class="mb-1 page-title">Daftar Kelengkapan RM</h4>
    <p class="text-muted mb-0">Tinjau dan kelola data rekam medis pasien sesuai Format 2026.</p>
  </div>
  <div class="d-flex align-items-center flex-wrap text-nowrap">
    <div class="search-box me-2 mb-2 mb-md-0 d-none d-md-block">
      <div class="input-group">
        <span class="input-group-text bg-transparent border-end-0">
          <i data-feather="search" style="width: 14px; height: 14px;" class="text-muted"></i>
        </span>
        <input type="text" id="globalSearch" class="form-control border-start-0 ps-0" style="width: 200px;" placeholder="Cari data...">
      </div>
    </div>
    <button type="button" class="btn btn-outline-success btn-icon-text mb-2 mb-md-0 me-2" data-bs-toggle="modal" data-bs-target="#importModal">
      <i class="btn-icon-prepend" data-feather="upload"></i> Import Data
    </button>
    <a href="{{ route('medical-records.export') }}" class="btn btn-outline-info btn-icon-text mb-2 mb-md-0 me-2">
      <i class="btn-icon-prepend" data-feather="download"></i> Export CSV/XLSX
    </a>
    <form action="{{ route('medical-records.truncate') }}" method="POST" class="d-inline me-2" onsubmit="return confirm('APAKAH ANDA YAKIN? Tindakan ini akan MENGHAPUS SELURUH DATA rekam medis secara permanen!')">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-outline-danger btn-icon-text mb-2 mb-md-0">
        <i class="btn-icon-prepend" data-feather="trash-2"></i> Hapus Semua Data
      </button>
    </form>
    <a href="{{ route('medical-records.create') }}" class="btn btn-primary btn-icon-text mb-2 mb-md-0">
      <i class="btn-icon-prepend" data-feather="plus"></i>
      Catatan Baru
    </a>
  </div>
</div>

<div class="search-box mb-3 d-md-none">
  <div class="input-group">
    <span class="input-group-text bg-transparent border-end-0">
      <i data-feather="search" style="width: 14px; height: 14px;" class="text-muted"></i>
    </span>
    <input type="text" id="globalSearchMobile" class="form-control border-start-0 ps-0" placeholder="Cari data rekam medis...">
  </div>
</div>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Tabel Rekam Medis</h6>

        {{-- Filter Bar --}}
        <div class="row g-2 mb-3 align-items-end" id="filterBar">
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Status Berkas</label>
            <select id="filterStatus" class="form-select form-select-sm">
              <option value="">Semua Status</option>
              <option value="LENGKAP">Lengkap</option>
              <option value="TIDAK LENGKAP">Tidak Lengkap</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Guarantor</label>
            <select id="filterGuarantor" class="form-select form-select-sm">
              <option value="">Semua Guarantor</option>
              @foreach($records->pluck('guarantor')->filter()->unique()->sort() as $g)
                <option value="{{ $g }}">{{ $g }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Status RM</label>
            <select id="filterRM" class="form-select form-select-sm">
              <option value="">Semua</option>
              <option value="kembali">RM Sudah Kembali</option>
              <option value="belum">RM Belum Kembali</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Status Analisa</label>
            <select id="filterAnalisa" class="form-select form-select-sm">
              <option value="">Semua</option>
              <option value="sudah">Sudah Analisa</option>
              <option value="belum">Belum Analisa</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-between align-items-center mt-1">
            <span class="small text-muted" id="filterCount"></span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilter">
              <i data-feather="x" style="width:13px;height:13px;"></i> Reset Filter
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover" id="medicalTable">
            <thead>
              <tr>
                <th>Billing No</th>
                <th>Nama Pasien</th>
                <th>Guarantor</th>
                <th>Ruangan</th>
                <th>Tgl Masuk</th>
                <th>Tgl Pulang</th>
                <th>Status Berkas</th>
                <th>Rincian Berkas</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $rec)
              <tr data-status="{{ $rec->is_rm_lengkap ? 'LENGKAP' : 'TIDAK LENGKAP' }}"
                  data-guarantor="{{ $rec->guarantor }}"
                  data-rm="{{ $rec->status_kembali_rm ? 'kembali' : 'belum' }}"
                  data-analisa="{{ $rec->status_analisa ? 'sudah' : 'belum' }}">
                <td class="text-muted small">{{ $rec->billing_no }}</td>
                <td>
                    <div class="fw-bold">{{ $rec->nama_pasien }}</div>
                    <div class="text-muted small">No RM: {{ $rec->no_rm }}</div>
                </td>
                <td>{{ $rec->guarantor }}</td>
                <td>
                    <div class="small">{{ $rec->ruangan }}</div>
                    <div class="text-muted x-small">Afya: {{ $rec->ruangan_afya }}</div>
                </td>
                <td>{{ $rec->tanggal_masuk ? \Carbon\Carbon::parse($rec->tanggal_masuk)->format('d-m-Y') : '-' }}</td>
                <td>{{ $rec->tanggal_pulang ? \Carbon\Carbon::parse($rec->tanggal_pulang)->format('d-m-Y') : '-' }}</td>
                <td>
                  <div class="mb-1">
                      @if($rec->is_rm_lengkap)
                        <span class="badge badge-success">LENGKAP</span>
                      @else
                        <span class="badge badge-warning">TIDAK LENGKAP</span>
                      @endif
                  </div>
                  <div class="small">
                      <span class="text-{{ $rec->status_kembali_rm ? 'success' : 'danger' }}">●</span> RM
                      <span class="text-{{ $rec->status_analisa ? 'success' : 'danger' }}">●</span> Analisa
                  </div>
                {{-- Rincian Berkas: Progress summary dari grouped forms --}}
                <td style="min-width:180px;">
                  @php
                    $riGroups = \App\Models\MedicalRecord::normalizeGroupedFormulir($rec->formulir_rawat_inap);
                    $kdGroups = \App\Models\MedicalRecord::normalizeGroupedFormulir($rec->kelengkapan_dokter);
                    $flItems  = \App\Models\MedicalRecord::normalizeFormulirItems($rec->formulir_lain);

                    $allItems = collect($riGroups)->pluck('items')->flatten(1)
                                ->merge(collect($kdGroups)->pluck('items')->flatten(1));

                    $totalForms     = $allItems->count()
                                    + count(array_filter($flItems, fn($i) => !empty($i['nama'])));
                    $completedForms = $allItems->where('is_lengkap', true)->count()
                                    + collect($flItems)->where('status', 'sudah_selesai')->count();
                    $diajukanForms  = $allItems->where('is_kembali', true)->count();

                    $percent = $totalForms > 0 ? round(($completedForms / $totalForms) * 100) : 0;
                  @endphp

                  @if($totalForms > 0)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold">{{ $completedForms }}/{{ $totalForms }} Lengkap</span>
                        <span class="small text-muted">{{ $percent }}%</span>
                    </div>
                    <div class="progress progress-sm mb-1" style="height: 5px;">
                        <div class="progress-bar bg-{{ $percent == 100 ? 'success' : 'primary' }}" role="progressbar" style="width: {{ $percent }}%"></div>
                    </div>
                    @if($diajukanForms > 0)
                      <div class="small text-muted mb-1">
                        <i data-feather="send" style="width:10px;height:10px;"></i> Diajukan: {{ $diajukanForms }}/{{ $allItems->count() }}
                      </div>
                    @endif
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($riGroups as $g)
                          @if(!empty($g['group_name']))
                            @php $gLengkap = collect($g['items'])->every(fn($i) => $i['is_lengkap']); @endphp
                            <span class="badge border" style="font-size:0.6rem;padding:2px 4px;background:{{ $gLengkap ? 'rgba(5,163,74,0.1)' : 'rgba(101,113,255,0.08)' }};color:{{ $gLengkap ? '#05a34a' : '#6571ff' }};">
                              {{ $gLengkap ? '✓' : '' }} {{ Str::limit($g['group_name'], 12) }}
                            </span>
                          @endif
                        @endforeach
                        @foreach($kdGroups as $g)
                          @if(!empty($g['group_name']))
                            @php $gLengkap = collect($g['items'])->every(fn($i) => $i['is_lengkap']); @endphp
                            <span class="badge border" style="font-size:0.6rem;padding:2px 4px;background:{{ $gLengkap ? 'rgba(5,163,74,0.1)' : 'rgba(102,209,209,0.1)' }};color:{{ $gLengkap ? '#05a34a' : '#66d1d1' }};">
                              {{ $gLengkap ? '✓' : '' }} {{ Str::limit($g['group_name'], 12) }}
                            </span>
                          @endif
                        @endforeach
                    </div>
                  @else
                    <span class="text-muted small">-</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="{{ route('medical-records.show', $rec->id) }}" class="btn btn-outline-info btn-sm" title="Lihat Detail">
                      <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                    </a>
                    <a href="{{ route('medical-records.edit', $rec->id) }}" class="btn btn-outline-primary btn-sm" title="Edit">
                      <i data-feather="edit-2" style="width: 14px; height: 14px;"></i>
                    </a>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                  <td colspan="9" class="text-center text-muted py-5">
                      <i data-feather="inbox" class="mb-3" style="width: 40px; height: 40px; opacity: 0.5;"></i>
                      <p>Belum ada data Rekam Medis ditemukan.</p>
                  </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">Import Data Rekam Medis (Excel/CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('medical-records.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-bold">File Excel/CSV</label>
            <input type="file" class="form-control" name="file_excel" accept=".csv, .xlsx, .xls" required>
            <div class="form-text">Gunakan header kolom yang sesuai format export.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
             <i data-feather="upload-cloud" class="me-2" style="width:16px;height:16px;"></i> Mulai Import
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput       = document.getElementById('globalSearch');
    const searchInputMobile = document.getElementById('globalSearchMobile');
    const filterStatus      = document.getElementById('filterStatus');
    const filterGuarantor   = document.getElementById('filterGuarantor');
    const filterRM          = document.getElementById('filterRM');
    const filterAnalisa     = document.getElementById('filterAnalisa');
    const filterCount       = document.getElementById('filterCount');
    const btnReset          = document.getElementById('btnResetFilter');
    const table             = document.getElementById('medicalTable');
    const tbody             = table.getElementsByTagName('tbody')[0];

    function applyFilters() {
        const search    = (searchInput.value || searchInputMobile.value).toLowerCase();
        const status    = filterStatus.value;
        const guarantor = filterGuarantor.value;
        const rm        = filterRM.value;
        const analisa   = filterAnalisa.value;

        const rows = tbody.getElementsByTagName('tr');
        let visible = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            // Skip empty state row
            if (row.cells.length < 2) { row.style.display = ''; continue; }

            const rowStatus   = row.dataset.status   || '';
            const rowGuarantor= row.dataset.guarantor || '';
            const rowRM       = row.dataset.rm        || '';
            const rowAnalisa  = row.dataset.analisa   || '';
            const rowText     = row.textContent.toLowerCase();

            const matchSearch    = !search    || rowText.includes(search);
            const matchStatus    = !status    || rowStatus === status;
            const matchGuarantor = !guarantor || rowGuarantor === guarantor;
            const matchRM        = !rm        || rowRM === rm;
            const matchAnalisa   = !analisa   || rowAnalisa === analisa;

            const show = matchSearch && matchStatus && matchGuarantor && matchRM && matchAnalisa;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        }

        // Update count label
        const total = Array.from(rows).filter(r => r.cells.length >= 2).length;
        if (status || guarantor || rm || analisa || search) {
            filterCount.textContent = `Menampilkan ${visible} dari ${total} data`;
        } else {
            filterCount.textContent = '';
        }
    }

    // Bind all filter inputs
    [searchInput, searchInputMobile].forEach(el => el.addEventListener('keyup', applyFilters));
    [filterStatus, filterGuarantor, filterRM, filterAnalisa].forEach(el => el.addEventListener('change', applyFilters));

    // Reset button
    btnReset.addEventListener('click', function() {
        searchInput.value        = '';
        searchInputMobile.value  = '';
        filterStatus.value       = '';
        filterGuarantor.value    = '';
        filterRM.value           = '';
        filterAnalisa.value      = '';
        applyFilters();
        feather.replace();
    });

    feather.replace();
});
</script>
@endsection
