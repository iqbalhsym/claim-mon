@extends('layouts.noble_layout')

@section('title', 'Tambah Data Rekam Medis')

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
.btn-remove-row { flex-shrink: 0; }
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
    <h4 class="mb-1 page-title">Tambah Data Rekam Medis</h4>
    <p class="text-muted mb-0">Masukkan kelengkapan dokumen RM sesuai formulir yang berlaku.</p>
  </div>
  <div class="d-flex align-items-center flex-wrap text-nowrap">
    <a href="{{ route('medical-records.index') }}" class="btn btn-outline-primary btn-icon-text mb-2 mb-md-0">
      <i class="btn-icon-prepend" data-feather="arrow-left"></i>
      Kembali
    </a>
  </div>
</div>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('medical-records.store') }}" method="POST">
          @csrf
          <div class="row">
            {{-- SINGLE COLUMN LAYOUT --}}
            <div class="col-12">
              <h6 class="card-title mb-3 border-bottom pb-2">Informasi Identitas</h6>

              <div class="mb-3">
                <label class="form-label fw-bold">Billing No</label>
                <input type="text" class="form-control" name="billing_no" placeholder="Contoh: 52603220111">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">No RM</label>
                <div class="input-group">
                  <input type="text" class="form-control" name="no_rm" id="no_rm" placeholder="Masukkan Nomor Rekam Medis">
                  <button type="button" class="btn btn-outline-secondary" id="btnCariAfya" title="Cari data pasien di Afya">
                    <i data-feather="search" style="width:15px;height:15px;"></i> Cari Afya
                  </button>
                </div>
                <div id="afya-status" class="form-text"></div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Nama Pasien</label>
                <input type="text" class="form-control" name="nama_pasien" placeholder="Nama Lengkap Pasien">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Guarantor</label>
                <select id="guarantorSelect" class="form-select mb-2" onchange="handleGuarantorChange(this)">
                  <option value="">-- Pilih Guarantor --</option>
                  <option value="BPJS KESEHATAN">BPJS KESEHATAN</option>
                  <option value="BPJS Kesehatan">BPJS Kesehatan</option>
                  <option value="Swasta">Swasta</option>
                  <option value="Umum">Umum</option>
                  <option value="Kemenkes">Kemenkes</option>
                  <option value="ADAS">ADAS</option>
                  <option value="BIDURI">BIDURI</option>
                  <option value="JASINDO">JASINDO</option>
                  <option value="MANDIRI INHEALTH">MANDIRI INHEALTH</option>
                  <option value="__other__">Lainnya (ketik manual)...</option>
                </select>
                <div id="guarantorCustomWrap" style="display:none;">
                  <input type="text" class="form-control" id="guarantorCustomInput"
                         placeholder="Ketik nama guarantor..."
                         oninput="document.getElementById('guarantorHidden').value = this.value">
                </div>
                <input type="hidden" name="guarantor" id="guarantorHidden" value="">
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Tanggal Masuk</label>
                  <input type="date" class="form-control" name="tanggal_masuk">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold">Tanggal Pulang</label>
                  <input type="date" class="form-control" name="tanggal_pulang">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Ruangan Afya</label>
                <input type="text" class="form-control" name="ruangan_afya" placeholder="Cth: RUMPUT MUTIARA KELAS 1">
              </div>

              <div class="row mb-3 mt-4">
                <h6 class="card-title mb-3 border-bottom pb-2">Status Monitoring</h6>
                <div class="col-md-6">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="status_kembali_rm" id="status_kembali_rm" value="1">
                        <label class="form-check-label fw-bold" for="status_kembali_rm">Kembali ke RM</label>
                    </div>
                    <input type="date" class="form-control form-control-sm" name="tanggal_kembali_rm" id="tanggal_kembali_rm">
                </div>
                <div class="col-md-6">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="status_analisa" id="status_analisa" value="1">
                        <label class="form-check-label fw-bold" for="status_analisa">Analisa</label>
                    </div>
                    <input type="date" class="form-control form-control-sm" name="tanggal_analisa" id="tanggal_analisa">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">Status Berkas RM</label>
                <select name="is_rm_lengkap" class="form-select">
                    <option value="1">LENGKAP (Ceklis)</option>
                    <option value="0" selected>TIDAK LENGKAP</option>
                </select>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Laporan Pembedahan</label>
                    <select name="laporan_pembedahan" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="LENGKAP">LENGKAP</option>
                        <option value="TIDAK LENGKAP">TIDAK LENGKAP</option>
                        <option value="KOSONG">KOSONG (-)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Persetujuan Tindakan</label>
                    <select name="persetujuan_tindakan" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="LENGKAP">LENGKAP</option>
                        <option value="TIDAK LENGKAP">TIDAK LENGKAP</option>
                        <option value="KOSONG">KOSONG (-)</option>
                    </select>
                </div>
              </div>

              {{-- RAWAT INAP (Grouped by Ruangan) --}}
              <div class="mb-4">
                <h6 class="card-title mb-3 border-bottom pb-2">Rawat Inap (Per Ruangan)</h6>
                <div id="ri_groups_container">
                  {{-- Groups will be appended here via JS --}}
                </div>
                <button type="button" class="btn btn-outline-primary btn-icon-text btn-sm" onclick="addGroup('ri')">
                  <i class="btn-icon-prepend" data-feather="plus-circle"></i> Tambah Ruangan (Grup)
                </button>
              </div>

              {{-- KELENGKAPAN DOKTER (Grouped by Dokter) --}}
              <div class="mb-4">
                <h6 class="card-title mb-3 border-bottom pb-2">Kelengkapan Dokter (Per Dokter)</h6>
                <div id="kd_groups_container">
                  {{-- Groups will be appended here via JS --}}
                </div>
                <button type="button" class="btn btn-outline-primary btn-icon-text btn-sm" onclick="addGroup('kd')">
                  <i class="btn-icon-prepend" data-feather="plus-circle"></i> Tambah Dokter (Grup)
                </button>
              </div>

              {{-- FORMULIR LAIN-LAIN (Flat) --}}
              <div class="mb-3">
                <label class="form-label fw-bold">Formulir Lain-lain</label>
                <div id="formulirLainContainer">
                  <div class="multi-input-row">
                    <div class="autocomplete-wrapper">
                      <input type="text" class="form-control ac-formulir-lain" name="fl_nama[]"
                             id="ac_fl_0" placeholder="Ketik atau pilih..." autocomplete="off">
                      <ul class="autocomplete-list" id="list_fl_0"></ul>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-2" style="flex-shrink:0;">
                      <input type="checkbox" class="form-check-input" style="width:20px;height:20px;cursor:pointer;"
                             onchange="this.nextElementSibling.value = this.checked ? 'sudah_selesai' : 'belum_selesai'">
                      <input type="hidden" name="fl_status[]" value="belum_selesai">
                      <label class="small text-muted mb-0">Selesai</label>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" onclick="removeRow(this)" style="visibility:hidden">
                      <i data-feather="minus" style="width:14px;height:14px;"></i>
                    </button>
                  </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-xs mt-1" onclick="addRow('formulirLainContainer','ac-formulir-lain','fl_nama','fl_status','formulir_lain')">
                  <i data-feather="plus" style="width:13px;height:13px;"></i> Tambah Formulir Lain
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">Keterangan Tambahan</label>
                <textarea rows="2" class="form-control" name="keterangan_formulir" placeholder="Catatan opsional"></textarea>
              </div>
            </div>
          </div>

          <hr class="mt-4 mb-4">
          <button type="submit" class="btn btn-primary d-flex align-items-center">
             <i data-feather="save" class="me-2" style="width: 18px; height: 18px;"></i> Simpan Catatan RM
          </button>
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

function addGroup(type) {
    const container = document.getElementById(type + '_groups_container');
    const gid = groupCounter++;
    const label = type === 'ri' ? 'Ruangan' : 'Nama Dokter';
    const acType = type === 'ri' ? 'ruangan' : 'dokter';
    const fieldPrefix = type === 'ri' ? 'ri_groups' : 'kd_groups';

    const card = document.createElement('div');
    card.className = 'card mb-3 shadow-none group-card';
    card.innerHTML = `
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1 me-2">
                    <label class="form-label fw-bold mb-1 small">${label}</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" class="form-control form-control-sm" name="${fieldPrefix}[${gid}][group_name]"
                               placeholder="Ketik ${label.toLowerCase()}..." id="ac_group_${gid}" autocomplete="off">
                        <ul class="autocomplete-list" id="list_group_${gid}"></ul>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2 mt-3 me-2" style="flex-shrink:0;">
                    {{-- Checkbox 1: Diajukan --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0" style="white-space:nowrap;">Diajukan</label>
                        <input type="checkbox" class="form-check-input"
                               style="width:22px;height:22px;cursor:pointer;flex-shrink:0;"
                               id="group_diajukan_${gid}"
                               onchange="toggleGroupDiajukan(this, ${gid}, '${fieldPrefix}')">
                        <input type="date" class="form-control form-control-sm"
                               id="group_date_${gid}"
                               style="width:145px; display:none;"
                               onchange="applyGroupDate(this, ${gid}, '${fieldPrefix}')">
                    </div>
                    {{-- Checkbox 2: Semua Lengkap --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0" style="white-space:nowrap;">Semua Lengkap</label>
                        <input type="checkbox" class="form-check-input"
                               style="width:22px;height:22px;cursor:pointer;flex-shrink:0;accent-color:#05a34a;"
                               id="group_lengkap_${gid}"
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
    addItem(type, gid);
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
    // Jika semua lengkap, otomatis juga diajukan
    if (checkbox.checked) {
        const diajukanCb = document.getElementById('group_diajukan_' + gid);
        if (diajukanCb && !diajukanCb.checked) {
            diajukanCb.checked = true;
            diajukanCb.dispatchEvent(new Event('change'));
        }
    }
}

function addItem(type, gid) {
    const container = document.getElementById('items_container_' + gid);
    const iid = itemCounter++;
    const fieldPrefix = type === 'ri' ? 'ri_groups' : 'kd_groups';
    const acType = type === 'ri' ? 'rawat_inap' : 'kelengkapan_dokter';

    const row = document.createElement('div');
    row.className = 'multi-input-row mb-2';
    row.innerHTML = `
        <div class="autocomplete-wrapper">
            <input type="text" class="form-control form-control-sm" name="${fieldPrefix}[${gid}][items][${iid}][nama]"
                   placeholder="Nama formulir..." id="ac_item_${iid}" autocomplete="off">
            <ul class="autocomplete-list" id="list_item_${iid}"></ul>
        </div>
        <input type="hidden" name="${fieldPrefix}[${gid}][items][${iid}][is_kembali]" value="0">
        <input type="hidden" name="${fieldPrefix}[${gid}][items][${iid}][tanggal_kembali]" value="">
        <input type="hidden" name="${fieldPrefix}[${gid}][items][${iid}][is_lengkap]" value="0">
        <button type="button" class="btn btn-outline-danger btn-xs btn-remove-row ms-1" onclick="this.closest('.multi-input-row').remove()">
            <i data-feather="minus" style="width:14px;height:14px;"></i>
        </button>
    `;
    container.appendChild(row);
    feather.replace();
    initAutocomplete(document.getElementById('ac_item_' + iid), document.getElementById('list_item_' + iid), acType);

    // Jika group sudah dicentang, langsung apply ke item baru
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
        dateKembali.value = this.checked ? today : '';
    });
    checkAnalisa.addEventListener('change', function() {
        dateAnalisa.value = this.checked ? today : '';
    });

    // Initial groups
    addGroup('ri');
    addGroup('kd');

    // Afya lookup
    const btnCari    = document.getElementById('btnCariAfya');
    const noRmInput  = document.getElementById('no_rm');
    const statusDiv  = document.getElementById('afya-status');
    const lookupUrl  = "{{ route('medical-records.afya-lookup') }}";

    btnCari.addEventListener('click', async function() {
        const noRm = noRmInput.value.trim();
        if (!noRm) return;
        btnCari.disabled = true;
        btnCari.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
            const resp = await fetch(`${lookupUrl}?no_rm=${encodeURIComponent(noRm)}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
            const json = await resp.json();
            if (json.success && json.data) {
                const data = json.data;
                document.querySelector('[name="nama_pasien"]').value = data.name || data.patientName || '';
                document.querySelector('[name="billing_no"]').value = data.billingNo || data.visitNo || '';
                document.querySelector('[name="ruangan_afya"]').value = data.bedRoomName || data.roomName || '';
                statusDiv.innerHTML = '<span class="text-success">✓ Data Afya ditemukan.</span>';
            } else {
                statusDiv.innerHTML = '<span class="text-warning">⚠ ' + (json.message || 'Data tidak ditemukan.') + '</span>';
            }
        } catch(err) { statusDiv.innerHTML = '<span class="text-danger">✗ Error koneksi Afya.</span>'; }
        finally {
            btnCari.disabled = false;
            btnCari.innerHTML = '<i data-feather="search" style="width:15px;height:15px;"></i> Cari Afya';
            feather.replace();
        }
    });

    noRmInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); btnCari.click(); } });
    
    // Init existing autocomplete (formulir lain)
    initAutocomplete(document.getElementById('ac_fl_0'),  document.getElementById('list_fl_0'),  'formulir_lain');
});

// Row handler for Formulir Lain-lain (Still Flat)
let _flCount = 1;
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
