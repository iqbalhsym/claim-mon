@extends('layouts.noble_layout')

@section('title', 'Edit Data Rekam Medis')

@section('css')
<style>
.autocomplete-wrapper { position: relative; }
.autocomplete-list {
    position: absolute; top: 100%; left: 0; right: 0;
    background: var(--card-bg); border: 1px solid var(--border-color);
    border-radius: 0 0 6px 6px; max-height: 180px; overflow-y: auto;
    z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: none;
}
.autocomplete-list li {
    padding: 8px 12px; cursor: pointer; font-size: 0.875rem;
    transition: background 0.15s; list-style: none; color: var(--text-color);
}
.autocomplete-list li:hover, .autocomplete-list li.active { background: var(--sidebar-hover-bg); color: var(--primary-color); }
.multi-input-row { display: flex; gap: 4px; margin-bottom: 4px; align-items: center; }
.multi-input-row .autocomplete-wrapper { flex: 1; }
.btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; }

/* Group card — dark mode aware */
.group-card {
    background-color: var(--bg-color);
    border: 1px solid var(--border-color) !important;
    border-radius: 8px;
}
.group-card .card-body {
    background-color: var(--bg-color);
    border-radius: 8px;
}
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin mb-4">
  <div>
    <h4 class="mb-1 page-title">Edit Data Rekam Medis</h4>
    <p class="text-muted mb-0">Perbarui informasi dan kelengkapan dokumen RM pasien.</p>
  </div>
  <div class="d-flex align-items-center flex-wrap text-nowrap">
    <a href="{{ route('medical-records.index') }}" class="btn btn-outline-primary btn-icon-text mb-2 mb-md-0">
      <i class="btn-icon-prepend" data-feather="arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('medical-records.update', $medicalRecord->id) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row">
            {{-- SINGLE COLUMN LAYOUT --}}
            <div class="col-12">
              <h6 class="card-title mb-3 border-bottom pb-2">Informasi Identitas</h6>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Billing No</label>
                <input type="text" class="form-control" name="billing_no" value="{{ old('billing_no', $medicalRecord->billing_no) }}">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">No RM</label>
                <input type="text" class="form-control" name="no_rm" value="{{ old('no_rm', $medicalRecord->no_rm) }}">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Nama Pasien</label>
                <input type="text" class="form-control" name="nama_pasien" value="{{ old('nama_pasien', $medicalRecord->nama_pasien) }}">
              </div>

              {{-- Guarantor --}}
              <div class="mb-3">
                <label class="form-label fw-bold">Guarantor</label>
                @php
                  $standardGuarantors = ['BPJS KESEHATAN','BPJS Kesehatan','Swasta','Umum','Kemenkes','ADAS','BIDURI','JASINDO','MANDIRI INHEALTH'];
                  $currentGuarantor   = old('guarantor', $medicalRecord->guarantor);
                  $isOther            = $currentGuarantor && !in_array($currentGuarantor, $standardGuarantors);
                @endphp
                <select id="guarantorSelect" class="form-select mb-2" onchange="handleGuarantorChange(this)">
                  <option value="">-- Pilih Guarantor --</option>
                  @foreach($standardGuarantors as $g)
                    <option value="{{ $g }}" {{ $currentGuarantor == $g ? 'selected' : '' }}>{{ $g }}</option>
                  @endforeach
                  <option value="__other__" {{ $isOther ? 'selected' : '' }}>Lainnya (ketik manual)...</option>
                </select>
                <div id="guarantorCustomWrap" style="{{ $isOther ? '' : 'display:none;' }}">
                  <input type="text" class="form-control" id="guarantorCustomInput"
                         placeholder="Ketik nama guarantor..."
                         value="{{ $isOther ? $currentGuarantor : '' }}"
                         oninput="document.getElementById('guarantorHidden').value = this.value">
                </div>
                <input type="hidden" name="guarantor" id="guarantorHidden" value="{{ $currentGuarantor }}">
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Tanggal Masuk</label>
                  <input type="date" class="form-control" name="tanggal_masuk" value="{{ old('tanggal_masuk', $medicalRecord->tanggal_masuk) }}">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Tanggal Pulang</label>
                  <input type="date" class="form-control" name="tanggal_pulang" value="{{ old('tanggal_pulang', $medicalRecord->tanggal_pulang) }}">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Ruangan Afya</label>
                <input type="text" class="form-control" name="ruangan_afya" value="{{ old('ruangan_afya', $medicalRecord->ruangan_afya) }}">
              </div>

              <div class="row mb-3 mt-4">
                <h6 class="card-title mb-3 border-bottom pb-2">Status Monitoring</h6>
                <div class="col-md-6">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="status_kembali_rm" id="status_kembali_rm" value="1"
                               {{ old('status_kembali_rm', $medicalRecord->status_kembali_rm) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="status_kembali_rm">Kembali ke RM</label>
                    </div>
                    <input type="date" class="form-control form-control-sm" name="tanggal_kembali_rm" id="tanggal_kembali_rm"
                           value="{{ old('tanggal_kembali_rm', $medicalRecord->tanggal_kembali_rm) }}">
                </div>
                <div class="col-md-6">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="status_analisa" id="status_analisa" value="1"
                               {{ old('status_analisa', $medicalRecord->status_analisa) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="status_analisa">Analisa</label>
                    </div>
                    <input type="date" class="form-control form-control-sm" name="tanggal_analisa" id="tanggal_analisa"
                           value="{{ old('tanggal_analisa', $medicalRecord->tanggal_analisa) }}">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">Status Berkas RM</label>
                <select name="is_rm_lengkap" class="form-select">
                    <option value="1" {{ $medicalRecord->is_rm_lengkap ? 'selected' : '' }}>LENGKAP (Ceklis)</option>
                    <option value="0" {{ !$medicalRecord->is_rm_lengkap ? 'selected' : '' }}>TIDAK LENGKAP</option>
                </select>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Laporan Pembedahan</label>
                    <select name="laporan_pembedahan" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="LENGKAP" {{ $medicalRecord->laporan_pembedahan == 'LENGKAP' ? 'selected' : '' }}>LENGKAP</option>
                        <option value="TIDAK LENGKAP" {{ $medicalRecord->laporan_pembedahan == 'TIDAK LENGKAP' ? 'selected' : '' }}>TIDAK LENGKAP</option>
                        <option value="KOSONG" {{ $medicalRecord->laporan_pembedahan == 'KOSONG' ? 'selected' : '' }}>KOSONG (-)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Persetujuan Tindakan</label>
                    <select name="persetujuan_tindakan" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="LENGKAP" {{ $medicalRecord->persetujuan_tindakan == 'LENGKAP' ? 'selected' : '' }}>LENGKAP</option>
                        <option value="TIDAK LENGKAP" {{ $medicalRecord->persetujuan_tindakan == 'TIDAK LENGKAP' ? 'selected' : '' }}>TIDAK LENGKAP</option>
                        <option value="KOSONG" {{ $medicalRecord->persetujuan_tindakan == 'KOSONG' ? 'selected' : '' }}>KOSONG (-)</option>
                    </select>
                </div>
              </div>

              @php
                $riGroups = \App\Models\MedicalRecord::normalizeGroupedFormulir($medicalRecord->formulir_rawat_inap, 'Ruangan Utama');
                $kdGroups = \App\Models\MedicalRecord::normalizeGroupedFormulir($medicalRecord->kelengkapan_dokter, 'Dokter Utama');
              @endphp

              {{-- RAWAT INAP (Grouped by Ruangan) --}}
              <div class="mb-4">
                <h6 class="card-title mb-3 border-bottom pb-2">Rawat Inap (Per Ruangan)</h6>
                <div id="ri_groups_container">
                  {{-- Rendered via JS loop for consistency --}}
                </div>
                <button type="button" class="btn btn-outline-primary btn-icon-text btn-sm" onclick="addGroup('ri')">
                  <i class="btn-icon-prepend" data-feather="plus-circle"></i> Tambah Ruangan (Grup)
                </button>
              </div>

              {{-- KELENGKAPAN DOKTER (Grouped by Dokter) --}}
              <div class="mb-4">
                <h6 class="card-title mb-3 border-bottom pb-2">Kelengkapan Dokter (Per Dokter)</h6>
                <div id="kd_groups_container">
                  {{-- Rendered via JS loop for consistency --}}
                </div>
                <button type="button" class="btn btn-outline-primary btn-icon-text btn-sm" onclick="addGroup('kd')">
                  <i class="btn-icon-prepend" data-feather="plus-circle"></i> Tambah Dokter (Grup)
                </button>
              </div>

              {{-- FORMULIR LAIN-LAIN (Flat) --}}
              <div class="mb-3">
                <label class="form-label fw-bold">Formulir Lain-lain</label>
                <div id="formulirLainContainer">
                  @php
                    $flItems = \App\Models\MedicalRecord::normalizeFormulirItems($medicalRecord->formulir_lain);
                    if (empty($flItems)) $flItems = [['nama' => '', 'status' => 'belum_selesai']];
                  @endphp
                  @foreach($flItems as $idx => $item)
                  <div class="multi-input-row">
                    <div class="autocomplete-wrapper">
                      <input type="text" class="form-control ac-formulir-lain" name="fl_nama[]"
                             id="ac_fl_{{ $idx }}" value="{{ $item['nama'] }}" placeholder="Ketik atau pilih..." autocomplete="off">
                      <ul class="autocomplete-list" id="list_fl_{{ $idx }}"></ul>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-2" style="flex-shrink:0;">
                      <input type="checkbox" class="form-check-input" style="width:20px;height:20px;cursor:pointer;"
                             {{ ($item['status'] ?? '') === 'sudah_selesai' ? 'checked' : '' }}
                             onchange="this.nextElementSibling.value = this.checked ? 'sudah_selesai' : 'belum_selesai'">
                      <input type="hidden" name="fl_status[]" value="{{ ($item['status'] ?? '') === 'sudah_selesai' ? 'sudah_selesai' : 'belum_selesai' }}">
                      <label class="small text-muted mb-0">Selesai</label>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" onclick="this.closest('.multi-input-row').remove()"
                            {{ $loop->first ? 'style=visibility:hidden' : '' }}>
                      <i data-feather="minus" style="width:14px;height:14px;"></i>
                    </button>
                  </div>
                  @endforeach
                </div>
                <button type="button" class="btn btn-outline-secondary btn-xs mt-1" onclick="addRow('formulirLainContainer','ac-formulir-lain','fl_nama','fl_status','formulir_lain')">
                  <i data-feather="plus" style="width:13px;height:13px;"></i> Tambah Formulir Lain
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">Keterangan Tambahan</label>
                <textarea rows="2" class="form-control" name="keterangan_formulir">{{ old('keterangan_formulir', $medicalRecord->keterangan_formulir) }}</textarea>
              </div>
            </div>
          </div>

          <hr class="mt-4 mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary d-flex align-items-center">
               <i data-feather="check-square" class="me-2" style="width: 18px; height: 18px;"></i> Update Catatan RM
            </button>
            
            <button type="button" class="btn btn-outline-danger d-flex align-items-center" onclick="confirmDelete()">
               <i data-feather="trash-2" class="me-2" style="width: 18px; height: 18px;"></i> Hapus Rekam Medis
            </button>
          </div>
        </form>

        {{-- Hidden Delete Form --}}
        <form id="delete-form" action="{{ route('medical-records.destroy', $medicalRecord->id) }}" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
let groupCounter = 0;
let itemCounter = 0;

// Autocomplete engine
const searchUrl = "{{ route('master-data.search') }}";

function initAutocomplete(inputEl, listEl, masterType) {
    if (!inputEl || !listEl) return;
    let debounceTimer;
    let selectedIndex = -1;

    inputEl.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(debounceTimer);
        if (q.length === 0) { listEl.style.display = 'none'; return; }

        debounceTimer = setTimeout(() => {
            fetch(`${searchUrl}?type=${masterType}&q=${encodeURIComponent(q)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(items => {
                listEl.innerHTML = '';
                selectedIndex = -1;
                if (!items.length) { listEl.style.display = 'none'; return; }
                items.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item;
                    li.addEventListener('click', () => {
                        inputEl.value = item;
                        listEl.style.display = 'none';
                    });
                    listEl.appendChild(li);
                });
                listEl.style.display = 'block';
            })
            .catch(() => listEl.style.display = 'none');
        }, 200);
    });

    inputEl.addEventListener('keydown', function(e) {
        const items = listEl.querySelectorAll('li');
        if (!items.length || listEl.style.display === 'none') return;
        if (e.key === 'ArrowDown') { e.preventDefault(); selectedIndex = Math.min(selectedIndex + 1, items.length - 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); selectedIndex = Math.max(selectedIndex - 1, -1); }
        else if (e.key === 'Enter' && selectedIndex >= 0) { e.preventDefault(); inputEl.value = items[selectedIndex].textContent; listEl.style.display = 'none'; selectedIndex = -1; return; }
        else if (e.key === 'Escape') { listEl.style.display = 'none'; return; }
        items.forEach((li, i) => li.classList.toggle('active', i === selectedIndex));
    });

    document.addEventListener('click', function(e) {
        if (!inputEl.contains(e.target) && !listEl.contains(e.target)) listEl.style.display = 'none';
    });
}

function addGroup(type, initialData = null) {
    const container = document.getElementById(type + '_groups_container');
    const gid = groupCounter++;
    const label = type === 'ri' ? 'Ruangan' : 'Nama Dokter';
    const acType = type === 'ri' ? 'ruangan' : 'dokter';
    const fieldPrefix = type === 'ri' ? 'ri_groups' : 'kd_groups';
    const gName = initialData ? (initialData.group_name || '') : '';

    const allDiajukan = initialData && initialData.items && initialData.items.length > 0
        && initialData.items.every(i => i.is_kembali);
    const allLengkap = initialData && initialData.items && initialData.items.length > 0
        && initialData.items.every(i => i.is_lengkap);
    const groupDate = allDiajukan && initialData.items[0].tanggal_kembali
        ? initialData.items[0].tanggal_kembali : '';

    const card = document.createElement('div');
    card.className = 'card mb-3 shadow-none group-card';
    card.innerHTML = `
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1 me-2">
                    <label class="form-label fw-bold mb-1 small">${label}</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" class="form-control form-control-sm" name="${fieldPrefix}[${gid}][group_name]"
                               value="${gName}" placeholder="Ketik ${label.toLowerCase()}..." id="ac_group_${gid}" autocomplete="off">
                        <ul class="autocomplete-list" id="list_group_${gid}"></ul>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2 mt-3 me-2" style="flex-shrink:0;">
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0" style="white-space:nowrap;">Diajukan</label>
                        <input type="checkbox" class="form-check-input"
                               style="width:22px;height:22px;cursor:pointer;flex-shrink:0;"
                               id="group_diajukan_${gid}"
                               ${allDiajukan ? 'checked' : ''}
                               onchange="toggleGroupDiajukan(this, ${gid}, '${fieldPrefix}')">
                        <input type="date" class="form-control form-control-sm"
                               id="group_date_${gid}"
                               value="${groupDate}"
                               style="width:145px; display:${allDiajukan ? 'block' : 'none'};"
                               onchange="applyGroupDate(this, ${gid}, '${fieldPrefix}')">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0" style="white-space:nowrap;">Semua Lengkap</label>
                        <input type="checkbox" class="form-check-input"
                               style="width:22px;height:22px;cursor:pointer;flex-shrink:0;accent-color:#05a34a;"
                               id="group_lengkap_${gid}"
                               ${allLengkap ? 'checked' : ''}
                               onchange="toggleGroupLengkap(this, ${gid}, '${fieldPrefix}')">
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-xs mt-3" onclick="this.closest('.card').remove()">
                    <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                </button>
            </div>
            <div class="items-container" id="items_container_${gid}"></div>
            <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none mt-2" onclick="addItem('${type}', ${gid})">
                <i data-feather="plus" style="width:12px;height:12px;"></i> Tambah Formulir
            </button>
        </div>
    `;
    container.appendChild(card);
    feather.replace();
    initAutocomplete(document.getElementById('ac_group_' + gid), document.getElementById('list_group_' + gid), acType);

    if (initialData && initialData.items) {
        initialData.items.forEach(item => addItem(type, gid, item));
    } else {
        addItem(type, gid);
    }
}

function toggleGroupDiajukan(checkbox, gid, fieldPrefix) {
    const dateInput = document.getElementById('group_date_' + gid);
    const today = new Date().toISOString().split('T')[0];
    if (checkbox.checked) {
        dateInput.style.display = 'block';
        if (!dateInput.value) dateInput.value = today;
        applyGroupDate(dateInput, gid, fieldPrefix);
    } else {
        dateInput.style.display = 'none';
        const container = document.getElementById('items_container_' + gid);
        container.querySelectorAll('input[name*="[is_kembali]"]').forEach(i => { i.value = '0'; });
        container.querySelectorAll('input[name*="[tanggal_kembali]"]').forEach(i => { i.value = ''; });
    }
}

function applyGroupDate(dateInput, gid, fieldPrefix) {
    const date = dateInput.value;
    const container = document.getElementById('items_container_' + gid);
    container.querySelectorAll('input[name*="[is_kembali]"]').forEach(i => { i.value = '1'; });
    container.querySelectorAll('input[name*="[tanggal_kembali]"]').forEach(i => { i.value = date; });
}

function toggleGroupLengkap(checkbox, gid, fieldPrefix) {
    const container = document.getElementById('items_container_' + gid);
    const val = checkbox.checked ? '1' : '0';
    container.querySelectorAll('input[name*="[is_lengkap]"]').forEach(i => { i.value = val; });
    if (checkbox.checked) {
        const diajukanCb = document.getElementById('group_diajukan_' + gid);
        if (diajukanCb && !diajukanCb.checked) {
            diajukanCb.checked = true;
            diajukanCb.dispatchEvent(new Event('change'));
        }
    }
}

function addItem(type, gid, initialData = null) {
    const container = document.getElementById('items_container_' + gid);
    const iid = itemCounter++;
    const fieldPrefix = type === 'ri' ? 'ri_groups' : 'kd_groups';
    const acType = type === 'ri' ? 'rawat_inap' : 'kelengkapan_dokter';

    const iName    = initialData ? (initialData.nama || '') : '';
    const iKembali = initialData && initialData.is_kembali ? '1' : '0';
    const iDate    = initialData && initialData.tanggal_kembali ? initialData.tanggal_kembali : '';
    const iLengkap = initialData && initialData.is_lengkap ? '1' : '0';

    const row = document.createElement('div');
    row.className = 'multi-input-row mb-2';
    row.innerHTML = `
        <div class="autocomplete-wrapper">
            <input type="text" class="form-control form-control-sm" name="${fieldPrefix}[${gid}][items][${iid}][nama]"
                   value="${iName}" placeholder="Nama formulir..." id="ac_item_${iid}" autocomplete="off">
            <ul class="autocomplete-list" id="list_item_${iid}"></ul>
        </div>
        <input type="hidden" name="${fieldPrefix}[${gid}][items][${iid}][is_kembali]" value="${iKembali}">
        <input type="hidden" name="${fieldPrefix}[${gid}][items][${iid}][tanggal_kembali]" value="${iDate}">
        <input type="hidden" name="${fieldPrefix}[${gid}][items][${iid}][is_lengkap]" value="${iLengkap}">
        <button type="button" class="btn btn-outline-danger btn-xs btn-remove-row ms-1" onclick="this.closest('.multi-input-row').remove()">
            <i data-feather="minus" style="width:14px;height:14px;"></i>
        </button>
    `;
    container.appendChild(row);
    feather.replace();
    initAutocomplete(document.getElementById('ac_item_' + iid), document.getElementById('list_item_' + iid), acType);

    if (!initialData) {
        const diajukanCb = document.getElementById('group_diajukan_' + gid);
        const groupDate  = document.getElementById('group_date_' + gid);
        if (diajukanCb && diajukanCb.checked && groupDate) {
            row.querySelector('input[name*="[is_kembali]"]').value = '1';
            row.querySelector('input[name*="[tanggal_kembali]"]').value = groupDate.value;
        }
        const lengkapCb = document.getElementById('group_lengkap_' + gid);
        if (lengkapCb && lengkapCb.checked) {
            row.querySelector('input[name*="[is_lengkap]"]').value = '1';
        }
    }
}

function handleGuarantorChange(select) {
    const customWrap  = document.getElementById('guarantorCustomWrap');
    const customInput = document.getElementById('guarantorCustomInput');
    const hidden      = document.getElementById('guarantorHidden');
    if (select.value === '__other__') {
        customWrap.style.display = '';
        customInput.focus();
        hidden.value = customInput.value;
    } else {
        customWrap.style.display = 'none';
        customInput.value = '';
        hidden.value = select.value;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Checkbox auto-date logic for monitoring
    const checkKembali = document.getElementById('status_kembali_rm');
    const dateKembali  = document.getElementById('tanggal_kembali_rm');
    const checkAnalisa = document.getElementById('status_analisa');
    const dateAnalisa  = document.getElementById('tanggal_analisa');
    const today = new Date().toISOString().split('T')[0];

    checkKembali.addEventListener('change', function() {
        if (this.checked && !dateKembali.value) dateKembali.value = today;
        if (!this.checked) dateKembali.value = '';
    });
    checkAnalisa.addEventListener('change', function() {
        if (this.checked && !dateAnalisa.value) dateAnalisa.value = today;
        if (!this.checked) dateAnalisa.value = '';
    });

    // Load existing groups
    const riData = {!! json_encode($riGroups) !!};
    const kdData = {!! json_encode($kdGroups) !!};

    if (riData.length > 0) {
        riData.forEach(g => addGroup('ri', g));
    } else {
        addGroup('ri');
    }

    if (kdData.length > 0) {
        kdData.forEach(g => addGroup('kd', g));
    } else {
        addGroup('kd');
    }

    // Init existing autocomplete (formulir lain)
    document.querySelectorAll('.ac-formulir-lain').forEach((inp, i) => {
        initAutocomplete(inp, document.getElementById('list_fl_' + i), 'formulir_lain');
    });
});

function confirmDelete() {
    if (confirm('Apakah Anda yakin ingin menghapus data rekam medis ini secara permanen?')) {
        document.getElementById('delete-form').submit();
    }
}

// Row handler for Formulir Lain-lain (Still Flat)
let _flCount = 100; // start high to avoid collision
window.addRow = function(containerId, acClass, namaField, statusField, masterType) {
    const container = document.getElementById(containerId);
    const idx = _flCount++;
    const div = document.createElement('div');
    div.className = 'multi-input-row';
    div.innerHTML = `
        <div class="autocomplete-wrapper">
            <input type="text" class="form-control form-control-sm ${acClass}" name="${namaField}[]"
                   id="ac_fl_${idx}" placeholder="Ketik atau pilih..." autocomplete="off">
            <ul class="autocomplete-list" id="list_fl_${idx}"></ul>
        </div>
        <div class="d-flex align-items-center gap-2 ms-2" style="flex-shrink:0;">
            <input type="checkbox" class="form-check-input" style="width:20px;height:20px;cursor:pointer;"
                   onchange="this.nextElementSibling.value = this.checked ? 'sudah_selesai' : 'belum_selesai'">
            <input type="hidden" name="${statusField}[]" value="belum_selesai">
            <label class="small text-muted mb-0">Selesai</label>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" onclick="this.closest('.multi-input-row').remove()">
            <i data-feather="minus" style="width:14px;height:14px;"></i>
        </button>`;
    container.appendChild(div);
    feather.replace();
    initAutocomplete(document.getElementById('ac_fl_' + idx), document.getElementById('list_fl_' + idx), masterType);
};
</script>
@endsection
