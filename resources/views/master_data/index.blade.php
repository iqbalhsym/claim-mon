@extends('layouts.noble_layout')

@section('title', 'Management Data Master')

@section('css')
<style>
.tab-type-btn {
    border: none;
    background: none;
    padding: 8px 18px;
    border-radius: 6px 6px 0 0;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
}
.tab-type-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    background: rgba(15, 93, 166, 0.06);
}
.inline-edit-form input {
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.85rem;
    width: 100%;
    background: var(--card-bg);
    color: var(--text-color);
}
.inline-edit-form input:focus {
    outline: 2px solid var(--primary-color);
}
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin mb-4 fade-in-up">
  <div>
    <h4 class="mb-1 page-title">Management Data Master</h4>
    <p class="text-muted mb-0">Kelola data referensi untuk dropdown input form Rekam Medis.</p>
  </div>
  <div class="d-flex align-items-center flex-wrap text-nowrap">
    <button type="button" class="btn btn-outline-success btn-icon-text mb-2 mb-md-0 me-2" data-bs-toggle="modal" data-bs-target="#importMasterModal">
      <i class="btn-icon-prepend" data-feather="upload"></i> Import Data
    </button>
    <a href="{{ route('master-data.export') }}" class="btn btn-outline-info btn-icon-text mb-2 mb-md-0">
      <i class="btn-icon-prepend" data-feather="download"></i> Export Data
    </a>
  </div>
</div>

{{-- Tab Navigation --}}
<div class="card fade-in-up delay-100">
  <div class="card-body p-0">
    <div class="d-flex border-bottom px-3 pt-3" id="master-tabs">
      @foreach($grouped as $type => $info)
        <button class="tab-type-btn {{ $loop->first ? 'active' : '' }}"
                onclick="switchTab('{{ $type }}')" id="tab-btn-{{ $type }}">
          {{ $info['label'] }}
          <span class="badge bg-secondary ms-1" style="font-size:0.7rem;">{{ $info['items']->count() }}</span>
        </button>
      @endforeach
    </div>

    {{-- Tab Panels --}}
    @foreach($grouped as $type => $info)
    <div id="tab-{{ $type }}" class="p-4 {{ $loop->first ? '' : 'd-none' }}">
      <div class="row">
        {{-- Form Tambah --}}
        <div class="col-md-4 mb-4">
          <div class="card border">
            <div class="card-body">
              <h6 class="fw-bold mb-3">
                <i data-feather="plus-circle" style="width:16px;height:16px;" class="me-1 text-primary"></i>
                Tambah {{ $info['label'] }}
              </h6>
              <form action="{{ route('master-data.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="mb-2">
                  <label class="form-label fw-semibold small">Nama</label>
                  <input type="text" class="form-control form-control-sm" name="name" placeholder="Masukkan nama..." required>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold small">Kode <span class="text-muted">(opsional)</span></label>
                  <input type="text" class="form-control form-control-sm" name="code" placeholder="Cth: ICU-01">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                  <i data-feather="save" style="width:14px;height:14px;" class="me-1"></i> Simpan
                </button>
              </form>
            </div>
          </div>
        </div>

        {{-- Tabel Data --}}
        <div class="col-md-8">
          @if($info['items']->isEmpty())
            <div class="text-center text-muted py-5">
              <i data-feather="inbox" style="width:36px;height:36px;opacity:0.4;" class="mb-2"></i>
              <p>Belum ada data {{ $info['label'] }}. Tambahkan melalui form di sebelah kiri.</p>
            </div>
          @else
          <div class="mb-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-transparent border-end-0">
                <i data-feather="search" style="width:14px;height:14px;" class="text-muted"></i>
              </span>
              <input type="text" class="form-control border-start-0 ps-0" 
                     id="search-{{ $type }}" 
                     placeholder="Cari nama atau kode..." 
                     onkeyup="filterMaster('{{ $type }}')">
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle" id="table-{{ $type }}">
              <thead>
                <tr>
                  <th style="width:40px">#</th>
                  <th>Nama</th>
                  <th>Kode</th>
                  <th style="width:120px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($info['items'] as $no => $item)
                <tr id="row-{{ $item->id }}">
                  <td class="text-muted small">{{ $no + 1 }}</td>
                  {{-- Display mode --}}
                  <td class="view-mode-{{ $item->id }}">{{ $item->name }}</td>
                  <td class="view-mode-{{ $item->id }}">
                    @if($item->code)<span class="badge badge-primary">{{ $item->code }}</span>@else<span class="text-muted">-</span>@endif
                  </td>
                  {{-- Edit mode (hidden) --}}
                  <td colspan="2" class="edit-mode-{{ $item->id }} d-none">
                    <form class="inline-edit-form d-flex gap-2 align-items-center"
                          action="{{ route('master-data.update', $item->id) }}" method="POST">
                      @csrf @method('PUT')
                      <input type="text" name="name" value="{{ $item->name }}" required style="flex:2">
                      <input type="text" name="code"  value="{{ $item->code }}" placeholder="Kode" style="flex:1">
                      <button type="submit" class="btn btn-success btn-sm">
                        <i data-feather="check" style="width:13px;height:13px;"></i>
                      </button>
                      <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit({{ $item->id }})">
                        <i data-feather="x" style="width:13px;height:13px;"></i>
                      </button>
                    </form>
                  </td>
                  {{-- Action buttons --}}
                  <td class="view-mode-{{ $item->id }}">
                    <div class="d-flex gap-1">
                      <button class="btn btn-outline-primary btn-sm" onclick="startEdit({{ $item->id }})" title="Edit">
                        <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                      </button>
                      <form action="{{ route('master-data.destroy', $item->id) }}" method="POST"
                            onsubmit="return confirm('Hapus data ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                          <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @endif
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>

<!-- Modal Import Master Data -->
<div class="modal fade" id="importMasterModal" tabindex="-1" aria-labelledby="importMasterModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importMasterModalLabel">Import Data Master (Excel/CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('master-data.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-bold">File Excel/CSV</label>
            <input type="file" class="form-control" name="file_excel" accept=".csv, .xlsx, .xls" required>
            <div class="form-text">Pastikan file memiliki header: <b>tipe_data</b>, <b>nama</b>, <b>kode</b>. Tipe data: ruangan, dokter, rawat_inap, formulir_lain.</div>
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
function switchTab(type) {
    // Hide all panels
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        if (!el.id.startsWith('tab-btn')) el.classList.add('d-none');
    });
    // Reset all tab buttons
    document.querySelectorAll('.tab-type-btn').forEach(btn => btn.classList.remove('active'));

    // Show selected
    document.getElementById('tab-' + type).classList.remove('d-none');
    document.getElementById('tab-btn-' + type).classList.add('active');
    feather.replace();
}

function startEdit(id) {
    document.querySelectorAll('.view-mode-' + id).forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.edit-mode-' + id).forEach(el => el.classList.remove('d-none'));
    feather.replace();
}

function cancelEdit(id) {
    document.querySelectorAll('.edit-mode-' + id).forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.view-mode-' + id).forEach(el => el.classList.remove('d-none'));
}

function filterMaster(type) {
    const input = document.getElementById('search-' + type);
    const filter = input.value.toLowerCase();
    const table = document.getElementById('table-' + type);
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const tdName = tr[i].getElementsByTagName('td')[1];
        const tdCode = tr[i].getElementsByTagName('td')[2];
        if (tdName || tdCode) {
            const txtName = tdName.textContent || tdName.innerText;
            const txtCode = tdCode.textContent || tdCode.innerText;
            if (txtName.toLowerCase().indexOf(filter) > -1 || txtCode.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>
@endsection
